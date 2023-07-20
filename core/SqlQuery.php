<?php

namespace Core;

use \Core\Database;
use \PDO;
use \PDOException;

/**
 * SqlQuery Class
 *
 * @category  Sql Query Handler
 * @package   SqlQuery
 * @author    Sadiq <sadiq.developer.bd@gmail.com>
 * @copyright Copyright (c) 2022-23
 * @version   2.0
 * @package   Alkane\SqlQuery
 */


class SqlQuery {

    /**
     * @var SORT_ASC
     */
    const SORT_ASC = 0;

    /**
     * @var SORT_DESC
     */
    const SORT_DESC = 1;

    /**
     * @var FETCH_ASSOC
     */
    const FETCH_ASSOC = 'assoc';

    /**
     * @var FETCH_OBJ
     */
    const FETCH_OBJ = 'obj';
     
    /**
     * @var FETCH_NUM
     */
    const FETCH_NUM = 'num';
     
    /**
     * @var $conn
     * object: connection storing property
     */
    private $conn = null;
     
    /**
     * @var $query
     * string: Stores SQL Query
     */
    private $query = '';
     
    /**
     * @var array $param
     * array: Stores param
     */
    private $params = array();
     
    /**
     * @var array $data
     * array: stores data
     */
    private $data = array();

    /**
     * @var $stmt
     * object: Stores PDOStatement
     */
    private $stmt = null;

    /**
     * @var $errorInfo
     * string: Stores Error information
     */
    private $errorInfo = '';

    /**
     * @var int $fetch_no
     * int: fetch number used by @method $this->fetch()
     */
    private $fetch_no = 0;

    /**
     * @var $fetch_data
     * int: fetch data used by @method $this->fetch()
     */
    private $fetch_data = [];

    /**
     * @var $last_insert_id
     * int: Last inserted ID 
     */
    private $last_insert_id = 0;

    /**
     * Constructor of the class
     * @param conn
     * PDO connection property
     */
    public function __construct($conn) {
        if ($conn instanceof Database) {
            $this->conn = $conn->getConnection();
        } elseif ($conn instanceof PDO) {
            $this->conn = $conn;
        } else {
            throw new \Exception('Invalid connection object');
        }
    }

    /**
     * @param q
     * Sets Query
     */
    public function setQuery(string $q) {
        $this->query = $q;
    }

    /**
     * @param q
     * Appends Query
     */
    public function appendQuery(string $q) {
        $this->query .= $q;
    }

    /**
     * apend param data
     */
    public function appendParamData(array $params, array $data) {
        $this->params = array_merge($this->params, $params);
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Selects Data
     * @param array $cols
     * @param string $extraOpt 
     * @param bool $distinct (only different values)
     * @return object $this
     */
    public function select(array $cols = [], string $extaOpt = null, bool $distinct = false) {
        if ($distinct) {
            $this->appendQuery('SELECT DISTINCT ');
        } else {
            $this->appendQuery('SELECT ');
        }
        if ($extaOpt != null && !empty($extaOpt)) {
            $extaOpt = strtolower($extaOpt);
            switch ($extaOpt) {
                case 'count':
                    $this->appendQuery('COUNT(');
                    break;
                case 'avg':
                    $this->appendQuery('AVG(');
                    break;
                case 'sum':
                    $this->appendQuery('SUM(');
                    break;
                default:
                    $this->appendQuery('COUNT(');
                    break;
            }
            if (isset($cols[0])) {
                $this->appendQuery(trim($cols[0]) . ' ');
            }
            $this->appendQuery(') ');
        } else {
            if (empty($cols)) {
                $this->appendQuery('* ');
            } else {
                foreach ($cols as $k => $v) {
                    if ($k == count($cols) - 1) {
                        $this->appendQuery(trim($v) . ' ');
                    } else {
                        $this->appendQuery(trim($v) . ', ');
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param tbl
     * @return this
     */
    public function from(string $tbl) {
        $this->appendQuery('FROM ' . trim($tbl) . ' ');
        return $this;
    }
    
    /**
     * Sql Where
     * @param Cond (Where Conditions)
     * @param data (additional param data)
     * @return this
     */    
    public function where(string $cond, array $data = []) {
        if (!empty($data)) {
            if ((strpos($cond, ':') !== false) && !$this->isAssoc($data)) {
                $con = str_split(trim($cond));
                $param = [];
                $i = 0;
                $addVal = false;
                foreach($con as $k => $v) {
                    if ($v == ':' || $addVal == true) {
                        $addVal = true;
                        if ($v != ':' && $v != ' ' && $v != ',') {
                            if (isset($param[$i])) {
                                $param[$i] .= $v;
                            } else {
                                $param[$i] = $v;
                            }
                        }
                        if (preg_match('/([^a-z0-9])/i', $v) && $v != ':') {
                            $i++;
                            $addVal = false;
                        }
                    }
                }
                $this->appendParamData($param, $data);
            
            } else {

                $data = $this->indexParamData($data);
                $this->appendParamData($data[0], $data[1]);
                
            }
            
            
        }               
        $this->appendQuery('WHERE ' . trim($cond) . ' ');
        return $this;
    }
    
    /**
     * Sort order
     * @param by
     * @param sort
     * @return this
     */
    public function orderby(string $by, int $sort = 0) {
        if ($sort == self::SORT_ASC) {
            $this->appendQuery('ORDER BY ' . trim($by) . ' ASC ');
        } 
        if ($sort == self::SORT_DESC) {
            $this->appendQuery('ORDER BY ' . trim($by) . ' DESC ');
        }
        return $this;
    }

    /**
     * Sets Limit
     * @param max
     * @param offset
     * @return this
     */
    public function limit(int $max, int $offset = 0) {
        $this->appendQuery('LIMIT ' . $max . ' OFFSET ' . $offset . ' ');
        return $this;
    }

    /**
     * Joins tables after @method $this->select()
     * @param tbl2
     * @param cond 
     * @param type (type of Joining table)
     * @return this
     */
    public function join(string $tbl2, string $cond, string $type = 'inner') {
        $query = '';
        $type = strtolower($type);
        switch ($type) {
            case 'inner':
                $query .= 'INNER ';
                break;
            case 'left':
                $query .= 'LEFT ';
                break;
            case 'right':
                $query .= 'RIGHT ';
                break;
            case 'outer':
            case 'full':
            case 'fullouter':
                $query .= 'FULL OUTER ';
                break;
            default:
                $query .= 'INNER ';
                break;
        }
        $query .= 'JOIN ' . trim($tbl2) . ' ON ' . trim($cond) . ' ';
        $this->appendQuery($query);
        return $this; 
    }

    /**
     * Insert Query
     * @param tbl
     * @param cols
     * @return this
     */
    public function insert(string $tbl, array $cols = []) {    
        $this->appendQuery('INSERT INTO ');
        if (!empty($cols)) {
            $this->appendQuery(trim($tbl) . ' (');
            foreach ($cols as $k => $v) {
                if ($k == count($cols) - 1) {
                    $this->appendQuery(trim($v) . ') ');
                } else {
                    $this->appendQuery(trim($v) . ', ');
                }
            }
        } else {
            $this->appendQuery(trim($tbl) . ' ');
        }
        $this->params = $cols;
        return $this;
    }
    
    /**
     * INSERT values
     * @param data
     * @return this
     */
    public function values(array $data = []) {         
        $this->appendQuery('VALUES (');
        if (!empty($data)) {
            if (!empty($this->params)) {
                foreach($this->params as $k => $v) {
                    if ($k == count($this->params) - 1) {
                        $this->appendQuery(':' . trim($v));
                    } else {
                        $this->appendQuery(':' . trim($v) . ', ');        
                    }
                    $this->params[$k] = ':' . $v;
                }
            } else {
                foreach($data as $k => $v) {
                    if ($k == count($data) - 1) {
                        $this->appendQuery('\'' . $v . '\'');
                    } else {
                        $this->appendQuery('\'' . $v . '\', ');        
                    }
                }
            }
            if ($this->isAssoc($data)) {
                $this->params = $this->indexParamData($data)[0];
                $this->data = $this->indexParamData($data)[1];
            } else {
                $this->data = $data;
            }
        }
        $this->appendQuery(') ');
        return $this;
    }

    /**
     * Updates data
     * @param tbl
     * @return this
     */
    public function update(string $tbl) {
        $this->appendQuery('UPDATE ' . trim($tbl) . ' ');
        return $this;
    }

    /**
     * set values after @method $this->update()
     * @param cols
     * @param data
     * @return this
     */
    public function set(array $cols, array $data = []) {
        $this->appendQuery('SET ');
        if ($this->isAssoc($cols)) {
            foreach ($cols as $k => $v) {
                if (array_key_last($cols) === $k) {
                    $this->appendQuery(trim($k) . '=:' . trim($k) . ' ');
                } else {
                    $this->appendQuery(trim($k) . '=:' . trim($k) . ', ');
                }
            }
            $data = $this->indexParamData($cols);
            $this->params = $data[0];
            $this->data = $data[1];
        } else {
            foreach ($cols as $k => $v) {
                if ($k == count($cols) - 1) {
                    $this->appendQuery(trim($v) . '=:' . trim($v) . ' ');
                } else {
                    $this->appendQuery(trim($v) . '=:' . trim($v) . ', ');
                }
            }
            $this->params = $cols;
            $this->data = $data;
        }

        return $this;
    }
    
    /**
     * Delete Query
     * @param tbl
     * @return this
     */
    public function delete(string $tbl) {
        $this->appendQuery('DELETE FROM ' . trim($tbl) . ' ');
        return $this;
    }
    
    /**
     * An implement of PDOStatement::fetch()
     * @param mode (FETCH_MODE)
     * @return data
     */
    public function fetch($mode = null) {
        if (empty($this->fetch_data)){
            if ($mode == null) {
                $this->fetch_data = $this->stmt->fetchAll();
            } else {
                $this->fetch_data = $this->stmt->fetchAll(self::getFetchMode($mode));
            }
        }
        if ($this->fetch_no < count($this->fetch_data)) {
            return $this->fetch_data[$this->fetch_no++];
        } else {
            $this->fetch_no = 0;
            return false;
        }
    }
    
    /**
     * An implement of PDOStatement::fetchColumn()
     * @param col
     */
    public function fetchColumn(int $col = 0) {
        return $this->stmt->fetchColumn($col);
    }
        
    /**
     * An implement of PDOStatement::fetchAll()
     * @param mode (FETCH_MODE)
     * @return data
     */
    public function fetchAll($mode = null) {
        if ($mode == null) {
            return $this->stmt->fetchAll();
        } else {
            return $this->stmt->fetchAll(self::getFetchMode($mode));
        }
    }
        
    /**
     * An impement of PDOStatement::rowCount()
     * @return rowCount :int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }


    protected function getFetchMode(string $mode) {
        switch ($mode) {
            case self::FETCH_ASSOC: 
                $mode = PDO::FETCH_ASSOC;
                break;
            case self::FETCH_OBJ: 
                $mode = PDO::FETCH_OBJ;
                break;
            case self::FETCH_NUM: 
                $mode = PDO::FETCH_NUM;
                break;
            default:
                $this->errorInfo .= 'Invalid fetch mode\n';
                break;
        }
        return $mode;
    }
    
        
    /**
     * Executes Query
     * @param extra_data (Additional Data)
     * @return this
     */
    public function exec(array $extra_data = [], bool $query_reset = false) {
        try {
            $data = $this->assocParamData($this->params, $this->data);
            $conn = $this->conn;
            if ($conn == null || empty($conn)) {
                throw new PDOException('Unable to connect to the Database');
            }
            $conn->beginTransaction();
            $stmt = $conn->prepare($this->query);
            $exec = $stmt->execute(array_merge($data, $extra_data));
            $this->last_insert_id = $conn->lastInsertId();
            $conn->commit();
            $this->stmt = $stmt;
            if ($query_reset) {
                $this->reset();
            }
            return $this;
        } catch (PDOException $e) {
            if ($conn != null) {
                $conn->rollBack();
            }
            $this->errorInfo .= $e->getMessage() . "\n";
        }
    }

    /**
     * Executes Query (Alias of SqlQuery::exec())
     * @param extra_data (Additional Data)
     * @return this
     */
    public function execute(array $extra_data = [], bool $query_reset = false) {
        return $this->exec($extra_data, $query_reset);
    }

            
    /**
     * @return errorInfo
     */
    public function getErrorInfo() {
        return $this->errorInfo;
    }
            
    /**
     * @return lastInsertId
     */
    public function lastInsertId() {
        return $this->last_insert_id;
    } 

    /**
     * Resets everything to reuse object
     */
    public function reset() {
        $this->setQuery('');
        $this->params = [];
        $this->data = [];
        $this->stmt = null;
        $this->errorInfo = '';
        $this->fetch_no = 0;
        $this->fetch_data = [];
        $this->last_insert_id = 0;
    }

        
    /**
     * make an associative array from 2 indexed array
     * @param param
     * @param data
     * @return final_data
     */
    public function assocParamData(array $param, array $data) {
        $final_data = array();
        foreach($param as $k => $v) {
            if (isset($data[$k])) {
                $final_data[$v] = $data[$k];
            }
        }
        return $final_data;
    }

    /**
     * make 2 indexed array from an associative array
     * @param paramData
     */
    public function indexParamData(array $paramData) {
        $param = array();
        $data = array();
        foreach ($paramData as $k => $v) {
            $param[] = $k;
            $data[] = $v;
        }
        return [
            0 => $param,
            1 => $data
        ];
    }

    /**
     * is Assoc Array
     */
    private function isAssoc(array $arr) {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * NOTICE: This method is only for development mode.
     * It should not be used in production mode
     */
    public function checkForDevelopmentMode() {    
        echo $this->query . "\n";
        print_r($this->assocParamData($this->params, $this->data));
    }


}


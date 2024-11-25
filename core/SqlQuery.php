<?php

namespace Core;

use PDO;
use PDOException;

/**
 * SqlQuery Class
 *
 * @category Sql Query Handler
 * @package  SqlQuery
 * @author   Sadiq <sadiq.dev.bd@gmail.com>
 * @version  2.2
 */
class SqlQuery {

    // Constants for sorting and fetching modes
    const SORT_ASC = 0;
    const SORT_DESC = 1;
    const FETCH_ASSOC = PDO::FETCH_ASSOC;
    const FETCH_OBJ = PDO::FETCH_OBJ;
    const FETCH_NUM = PDO::FETCH_NUM;

    // Instance properties
    private $conn;
    private $query = '';
    private $params = [];
    private $data = [];
    private $stmt;
    private $errorInfo = '';
    private $fetchNo = 0;
    private $fetchData = [];
    private $lastInsertId = 0;

    /**
     * Constructor
     *
     * @param PDO|Database $conn Database connection
     * @throws \Exception
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
     * Sets the SQL query string
     *
     * @param string $query SQL query string
     * @return $this
     */
    public function setQuery(string $query) {
        $this->query = $query;
        return $this;
    }

    /**
     * Appends to the SQL query string
     *
     * @param string $query SQL query string to append
     * @return $this
     */
    public function appendQuery(string $query) {
        $this->query .= $query;
        return $this;
    }

    /**
     * Appends parameter data to the query
     *
     * @param array $params Parameters
     * @param array $data Data
     * @return $this
     */
    public function appendParamData(array $params, array $data) {
        $this->params = array_merge($this->params, $params);
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Prepares and executes a SELECT query
     *
     * @param array  $cols     Columns to select
     * @param string $extraOpt Optional aggregation (count, sum, avg, etc.)
     * @param bool   $distinct Whether to apply DISTINCT
     * @return $this
     */
    public function select(array $cols = [], string $extraOpt = null, bool $distinct = false) {
        $this->appendQuery($distinct ? 'SELECT DISTINCT ' : 'SELECT ');
        $this->handleAggregation($extraOpt, $cols);
        return $this;
    }

    /**
     * Handles SQL aggregation (COUNT, AVG, etc.)
     *
     * @param string|null $extraOpt Aggregation type (count, avg, etc.)
     * @param array       $cols     Columns to include
     * @return void
     */
    private function handleAggregation(string $extraOpt = null, array $cols = []) {
        if ($extraOpt) {
            $extraOpt = strtolower($extraOpt);
            $aggregationTypes = ['count', 'avg', 'sum'];
            if (!in_array($extraOpt, $aggregationTypes)) {
                $extraOpt = 'count'; // Default to count
            }
            $this->appendQuery(strtoupper($extraOpt) . '(');
            if (!empty($cols)) {
                $this->appendQuery(trim($cols[0]) . ') ');
            } else {
                $this->appendQuery('* ) ');
            }
        } else {
            $this->appendColumns($cols);
        }
    }

    /**
     * Appends the columns to the query string
     *
     * @param array $cols Columns to append
     * @return void
     */
    private function appendColumns(array $cols) {
        if (empty($cols)) {
            $this->appendQuery('* ');
        } else {
            $this->appendQuery(implode(', ', array_map('trim', $cols)) . ' ');
        }
    }

    /**
     * Specifies the table for the FROM clause
     *
     * @param string $table Table name
     * @return $this
     */
    public function from(string $table) {
        $this->appendQuery('FROM ' . trim($table) . ' ');
        return $this;
    }

    /**
     * Adds a WHERE condition to the query
     *
     * @param string $condition WHERE condition
     * @param array  $data      Additional data for placeholders
     * @return $this
     */
    public function where(string $condition, array $data = []) {
        $this->appendQuery('WHERE ' . trim($condition) . ' ');
        if ($data) {
            $this->appendParamData($this->indexParamData($data));
        }
        return $this;
    }

    /**
     * Adds an ORDER BY clause
     *
     * @param string $by   Column to order by
     * @param int    $sort Sorting order (SORT_ASC or SORT_DESC)
     * @return $this
     */
    public function orderBy(string $by, int $sort = self::SORT_ASC) {
        $this->appendQuery('ORDER BY ' . trim($by) . ' ' . ($sort == self::SORT_ASC ? 'ASC' : 'DESC') . ' ');
        return $this;
    }

    /**
     * Adds a LIMIT clause
     *
     * @param int $max    Maximum number of rows
     * @param int $offset Offset for pagination
     * @return $this
     */
    public function limit(int $max, int $offset = 0) {
        $this->appendQuery('LIMIT ' . $max . ' OFFSET ' . $offset . ' ');
        return $this;
    }

    /**
     * Adds a JOIN clause to the query
     *
     * @param string $table2 The second table to join
     * @param string $cond   The join condition
     * @param string $type   Type of join (inner, left, right, full, etc.)
     * @return $this
     */
    public function join(string $table2, string $cond, string $type = 'inner') {
        $this->appendQuery(strtoupper($type) . ' JOIN ' . trim($table2) . ' ON ' . trim($cond) . ' ');
        return $this;
    }

    /**
     * Prepares and executes an INSERT query
     *
     * @param string $table Table name
     * @param array  $cols  Columns to insert
     * @return $this
     */
    public function insert(string $table, array $cols = []) {
        $this->appendQuery('INSERT INTO ' . trim($table) . ' (' . implode(', ', $cols) . ') ');
        $this->params = $cols;
        return $this;
    }

    /**
     * Adds values to the INSERT query
     *
     * @param array $data Values to insert
     * @return $this
     */
    public function values(array $data = []) {
        $this->appendQuery('VALUES (' . implode(', ', array_map(function($value) {
            return '\'' . $value . '\'';
        }, $data)) . ') ');
        $this->data = $data;
        return $this;
    }

    /**
     * Prepares and executes an UPDATE query
     *
     * @param string $table Table name
     * @return $this
     */
    public function update(string $table) {
        $this->appendQuery('UPDATE ' . trim($table) . ' ');
        return $this;
    }

    /**
     * Sets the columns for the UPDATE query
     *
     * @param array $cols Column-value pairs to update
     * @param array $data Additional data for placeholders
     * @return $this
     */
    public function set(array $cols, array $data = []) {
        $this->appendQuery('SET ' . implode(', ', array_map(function($key, $value) {
            return trim($key) . ' = :' . trim($key);
        }, array_keys($cols), $cols)) . ' ');
        $this->params = array_merge($this->params, $cols);
        $this->data = $data;
        return $this;
    }

    /**
     * Prepares and executes a DELETE query
     *
     * @param string $table Table name
     * @return $this
     */
    public function delete(string $table) {
        $this->appendQuery('DELETE FROM ' . trim($table) . ' ');
        return $this;
    }

    /**
     * Executes the prepared query
     *
     * @param array $extraData Additional data for placeholders
     * @param bool  $queryReset Whether to reset query after execution
     * @return $this
     * @throws PDOException
     */
    public function exec(array $extraData = [], bool $queryReset = false) {
        try {
            if ($this->conn === null) {
                throw new PDOException('Unable to connect to the Database');
            }
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare($this->query);
            $stmt->execute(array_merge($this->params, $extraData));
            $this->stmt = $stmt;
            $this->lastInsertId = $this->conn->lastInsertId();
            $this->conn->commit();

            if ($queryReset) {
                $this->resetQuery();
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * Resets the query, params, and data
     *
     * @return void
     */
    private function resetQuery() {
        $this->query = '';
        $this->params = [];
        $this->data = [];
        $this->stmt = null;
    }
}

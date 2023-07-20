<?php
namespace Core;
use \Core\Database;
use \Core\SqlQuery;

/**
 * Class Model
 *
 * @category  Parent Model
 * @package   Model
 * @author    Sadiq <sadiq.developer.bd@gmail.com>
 * @copyright Copyright (c) 2022-23
 * @version   2.0
 * @package   Alkane\Model
 */


class Model extends Database {


    protected $db = null;


    protected $table = '';

    protected $error = '';
    

    public function __construct() {

        $this->db = self::getInstance();    

    }

    public function getList(string $cond = null, array $data = [], string $orderBy = null, int $sort = 0, int $limit = -1, int $offset = 0) {
       
        $sql = new SqlQuery($this->db);
        
        $sql->select()
            ->from($this->table);
        
        if ($cond !== null) {
            $sql->where($cond, $data);
        }

        if ($orderBy !== null) {
            $sql->orderby($orderBy, $sort);
        }

        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }
        
        if ($sql->exec()) {
            return $sql->fetchAll();
        } else {
            $this->error .= $sql->getErrorInfo();
        }

    }
    
    public function getBy(string $by, $data, string $orderBy = null, int $sort = 0) {

        return $this->getList($by . ' = :id', array(
            'id' => $data
        ), $orderBy, $sort);

    }

    public function insert(array $data) {

        $sql = new SqlQuery($this->db);

        $sql->insert($this->table, $sql->indexParamData($data)[0])
            ->values($sql->indexParamData($data)[1]);

        if ($sql->exec()) {
            return $sql->lastInsertId();
        } else {
            $this->error .= $sql->getErrorInfo();
        }


    }


    public function update(string $cond, array $condData, array $updateData) {

        $sql = new SqlQuery($this->db);

        $sql->update($this->table)
            ->set($updateData)
            ->where($cond, $condData);

        if ($sql->exec()) {
            return true;
        } else {
            $this->error .= $sql->getErrorInfo();
        }
        
    }


    public function delete(string $cond, array $data = []) {

        $sql = new SqlQuery($this->db);

        $sql->delete($this->table)
            ->where($cond, $data);

        if ($sql->exec()) {
            return true;
        } else {
            $this->error .= $sql->getErrorInfo();
        }
        

    }

    public function deleteBy(string $by, $data) {

        return $this->delete($by . ' = :id', [
            'id' => $data
        ]);

    }

    public function totalRowCount(string $cond = null, array $condData = []) {
        $sql = new SqlQuery($this->db);

        $sql->select(['*'], 'count')
            ->from($this->table);
        if ($cond !== null) {
            $sql->where($cond, $condData);
        }
        if ($sql->exec()) {
            return $sql->fetchColumn();
        } else {
            $this->error .= $sql->getErrorInfo();
        }
    }

    public function getErrorMessage() {
        return $this->error;
    } 

}


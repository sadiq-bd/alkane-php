<?php

namespace Core;

use \PDO;
use \PDOException;

/**
 * Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Sadiq <sadiq.developer.bd@gmail.com>
 * @copyright Copyright (c) 2022-23
 * @version   2.0
 * @package   Alkane\Database
 */

class Database {

    /**
     * @var $config
     * Default configurations are stored in an associative array
     */
    private static $config = [
        'type' => 'mysql',
        'host' => 'localhost',
        'user' => '',
        'password' => '',
        'dbname' => '',
        'port' => 3306,
        'charset' => 'utf8mb4',
        'errmode' => 'exception',
        'fetch_mode' => 'obj',
        'emulate_prepares' => false
    ];

    /**
     * custom configurations
     * @var array
     */
    private static $custom_configs = [];

    /**
     * @var $pdo
     * connection storing property
     */
    private static $pdo = array();

    /**
     * @var $current_connection
     * current connection name
     */
    private $current_connection = 0;

    /**
     * @var on_error_conn
     * callback function
     */
    private static $on_error_conn = null;

    /**
     * Error Modes in an associative array
     */
    protected static $errMode = [
        'silent' => PDO::ERRMODE_SILENT,
        'warning' => PDO::ERRMODE_WARNING,
        'exception' => PDO::ERRMODE_EXCEPTION
    ];
  
    /**
     * Fetch Modes in an associative array
     */
    protected static $fetchMode = [
        'assoc' => PDO::FETCH_ASSOC,
        'obj' => PDO::FETCH_OBJ,
        'num' => PDO::FETCH_NUM
    ];

    /**
     * @var errorInfo
     * Stores error informations
     */
    private $errorInfo = '';

    /**
     * Constructor of the class
     * @param info
     * Addition configurations can be pass through it
     */
    public function __construct(string $coustom_config_name = '') {
        // set current connection name
        $this->current_connection = $coustom_config_name !== '' ? $coustom_config_name : 0;

        // Connect to the database
        $this->connect($coustom_config_name);
    }

    /**
     * set host
     */
    public static function setHost($host) {
        self::$config['host'] = $host;
    }

    /**
     * set user
     */
    public static function setUser($user) {
        self::$config['user'] = $user;
    }

    /**
     * set password
     */

    public static function setPassword($password) {
        self::$config['password'] = $password;

    }

    /**
     * set dbname
     */

    public static function setDbname($dbname) {
        self::$config['dbname'] = $dbname;

    }

    /**
     * set port
     */

    public static function setPort($port) {
        self::$config['port'] = $port;
    }

    /**
     * set charset
     */

    public static function setCharset($charset) {
        self::$config['charset'] = $charset;

    }

    /**
     * set errmode
     */

    public static function setErrmode($errmode) {
        self::$config['errmode'] = $errmode;
    }

    /**
     * set fetch_mode
     */
    
    public static function setFetchMode($fetchMode) {
        self::$config['fetch_mode'] = $fetchMode;
    }
    
    /**
     * set emulate_prepares
     */

    public static function setEmulatePrepares($emulate_prepares) {
        self::$config['emulate_prepares'] = $emulate_prepares;
    }

    /**
     * @param name
     * Name (key) of the config
     * @param value
     * Value (value) of the config
     */
    public static function setConfig(string $name, $value) {
        if (isset(self::$config[$name])) {
            self::$config[$name] = $value;
        }
    }

    /**
     * Add custom configurations
     */
    public static function addCustomConfig(string $name, array $config) {
        self::$custom_configs[$name] = $config;
    }

    /**
     * @param name
     * Name (key) of the config
     * @return value
     * Returns Config Value
     */
    public static function getConfig(string $name) {
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        } else {
            return false;
        }
    }

    /**
     * @param $info
     * create config array
     */
    public function createConfig($info) {
        $config = [];
        foreach (self::$config as $key => $val) {
            if (isset($info[$key])) {
                $config[$key] = $info[$key];
            } else {
                $config[$key] = $val;
            }
        }
        return $config;
    }

    /**
     * checks config
     * @param $config
     */
    public static function checkConfig($config) {
        if (
            isset($config['type']) && 
            isset($config['host']) && 
            isset($config['user']) && 
            isset($config['password'])
            ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Creates a database connection with PDO
     * @param $coustom_config_name
     * Custom configurations can be pass through it
     */
    public function connect(string $coustom_config_name = '') {
        // Setup the config
        if (!empty($coustom_config_name)) {
            if (!isset(self::$custom_configs[$coustom_config_name])) {
                trigger_error('Database custom configuration \'' . $coustom_config_name . '\' not found', E_USER_ERROR);
            }
            $config = $this->createConfig(self::$custom_configs[$coustom_config_name]);
            $i = $coustom_config_name;
        } else {
            $config = $this->createConfig(self::$config);
            $i = 0;
        }

        // Check if config is valid
        if (!self::checkConfig($config)) {
            trigger_error('Incomplete Database Configuration', E_USER_ERROR);
        }

        // if everything is ok then ready to connect
        try {

            $dsn = $config['type'];
            $dsn .= ':host=' . $config['host']; 
            $dsn .= ';port=' . $config['port']; 
            if (!empty($config['dbname']) && $config['dbname'] != '*') {
                $dsn .= ';dbname=' . $config['dbname'];
            }
            $dsn .= ';charset=' . $config['charset'];
            
            // Create a new PDO instance
            self::$pdo[$i] = new PDO($dsn, $config['user'], $config['password']);
            
            self::$pdo[$i]->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE , 
                self::$fetchMode[
                    $config['fetch_mode']
                ]);
            self::$pdo[$i]->setAttribute(
                PDO::ATTR_ERRMODE , 
                self::$errMode[
                    $config['errmode']
                ]);
            self::$pdo[$i]->setAttribute(
                PDO::ATTR_EMULATE_PREPARES , 
                $config[
                    'emulate_prepares'
                ]);

        } catch (PDOException $e) {
            if (self::$on_error_conn != null) {
                if (is_callable(self::$on_error_conn)) {
                    call_user_func_array(self::$on_error_conn, [
                        $e->getMessage()
                    ]);  
                }
            } 
        }
    }

    /**
     * @return object $pdo
     */
    public function getConnection(string $name = '') {
        if (!empty($name)) {
            return self::$pdo[$name];
        } else {
            return self::$pdo[$this->current_connection];
        }
    }

    /**
     * get Database class instance
     * @param $coustom_config_name
     * @return object
     */
    public static function getInstance($coustom_config_name = '') {
        return new self($coustom_config_name);
    }

    /**
     * Executes Custom Query
     * @param string $sql
     * Sql Query
     * @param array $data
     * additional Param data
     */
    public function query_exec(string $sql, array $data = []) {
        try {
            $conn = $this->getConnection();
            $conn->beginTransaction();
            $stmt = $conn->prepare($sql);
            $exec = $stmt->execute($data);
            $conn->commit();
            return $stmt;
        } catch (PDOException $e) {
            $conn->rollBack();
            $this->errorInfo .= $e->getMessage();
            return false;
        }
    }
    
    /**
     * @param $func
     * sets event on error connection
     */
    public static function onErrorConnection($func) {
        if (is_callable($func)) {
            self::$on_error_conn = $func;
        }
    }
           
    /**
     * @return string $errorInfo
     */
    public function getErrorInfo() {
        return $this->errorInfo;
    }
         
    /**
     * Closes PDO connection
     */
    public function closeConnection() {
        self::$pdo[$this->current_connection] = null;
    }

    /**
     * Destructor of the class
     */
    public function __destruct() {
        $this->closeConnection();
    }

}

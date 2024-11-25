<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Sadiq <sadiq.dev.bd@gmail.com>
 * @version   2.2
 */
class Database {

    /**
     * Default configurations.
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
     * Custom configurations.
     */
    private static $custom_configs = [];

    /**
     * Stores PDO connections.
     */
    private static $pdo = [];

    /**
     * Current connection name.
     */
    private $current_connection = 0;

    /**
     * Error callback.
     */
    private static $on_error_conn = null;

    /**
     * Error modes map.
     */
    protected static $errMode = [
        'silent' => PDO::ERRMODE_SILENT,
        'warning' => PDO::ERRMODE_WARNING,
        'exception' => PDO::ERRMODE_EXCEPTION
    ];

    /**
     * Fetch modes map.
     */
    protected static $fetchMode = [
        'assoc' => PDO::FETCH_ASSOC,
        'obj' => PDO::FETCH_OBJ,
        'num' => PDO::FETCH_NUM
    ];

    /**
     * Stores error information.
     */
    private $errorInfo = '';

    /**
     * Constructor to initialize the connection.
     */
    public function __construct(string $custom_config_name = '') {
        $this->current_connection = $custom_config_name ?: 0;
        $this->connect($custom_config_name);
    }

    /**
     * Set configuration dynamically.
     */
    public static function setConfig(string $name, $value): void {
        if (array_key_exists($name, self::$config)) {
            self::$config[$name] = $value;
        }
    }

    /**
     * Add custom configuration.
     */
    public static function addCustomConfig(string $name, array $config): void {
        self::$custom_configs[$name] = $config;
    }

    /**
     * Retrieve a configuration value.
     */
    public static function getConfig(string $name) {
        return self::$config[$name] ?? false;
    }

    /**
     * Validate a configuration array.
     */
    private static function validateConfig(array $config): bool {
        return isset($config['type'], $config['host'], $config['user'], $config['password']);
    }

    /**
     * Create a merged configuration array.
     */
    private function createConfig(array $custom_config): array {
        return array_merge(self::$config, $custom_config);
    }

    /**
     * Establish a PDO connection.
     */
    private function connect(string $custom_config_name = ''): void {
        $config = $custom_config_name 
            ? ($this->createConfig(self::$custom_configs[$custom_config_name] ?? []))
            : $this->createConfig(self::$config);

        if (!self::validateConfig($config)) {
            throw new PDOException('Incomplete Database Configuration.');
        }

        try {
            $dsn = "{$config['type']}:host={$config['host']};port={$config['port']};charset={$config['charset']}";
            if (!empty($config['dbname']) && $config['dbname'] !== '*') {
                $dsn .= ";dbname={$config['dbname']}";
            }

            self::$pdo[$this->current_connection] = new PDO($dsn, $config['user'], $config['password']);
            $pdo = self::$pdo[$this->current_connection];
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, self::$fetchMode[$config['fetch_mode']]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, self::$errMode[$config['errmode']]);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $config['emulate_prepares']);
        } catch (PDOException $e) {
            if (self::$on_error_conn && is_callable(self::$on_error_conn)) {
                call_user_func(self::$on_error_conn, $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Retrieve the active PDO connection.
     */
    public function getConnection(string $name = ''): ?PDO {
        return self::$pdo[$name ?: $this->current_connection] ?? null;
    }

    /**
     * Static method to retrieve an instance.
     */
    public static function getInstance(string $custom_config_name = ''): self {
        return new self($custom_config_name);
    }

    /**
     * Execute a SQL query with parameters.
     */
    public function executeQuery(string $sql, array $params = []): ?PDOStatement {
        try {
            $conn = $this->getConnection();
            if (!$conn) {
                throw new PDOException('Database connection not established.');
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->errorInfo .= $e->getMessage();
            return null;
        }
    }

    /**
     * Set a custom error callback.
     */
    public static function onErrorConnection(callable $func): void {
        self::$on_error_conn = $func;
    }

    /**
     * Retrieve the error information.
     */
    public function getErrorInfo(): string {
        return $this->errorInfo;
    }

    /**
     * Test if the connection is active.
     */
    public function testConnection(): bool {
        try {
            $conn = $this->getConnection();
            return $conn && $conn->query('SELECT 1');
        } catch (PDOException $e) {
            $this->errorInfo .= $e->getMessage();
            return false;
        }
    }

    /**
     * Close a specific connection.
     */
    public function closeConnection(string $name = ''): void {
        self::$pdo[$name ?: $this->current_connection] = null;
    }

    /**
     * Destructor to clean up.
     */
    public function __destruct() {
        $this->closeConnection();
    }
}

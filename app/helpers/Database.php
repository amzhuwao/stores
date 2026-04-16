<?php
/**
 * Database Connection Class
 * Singleton pattern for database connection
 */

class Database {
    private static $instance = null;
    private $connection = null;

    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->connection->connect_error) {
                throw new Exception('Database Connection Failed: ' . $this->connection->connect_error);
            }

            $this->connection->set_charset('utf8mb4');
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    /**
     * Get database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get raw connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute SELECT query
     */
    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result) {
            die('Query Error: ' . $this->connection->error . ' Query: ' . $sql);
        }
        return $result;
    }

    /**
     * Execute prepared statement (safe against SQL injection)
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    /**
     * Get last inserted ID
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * Get affected rows
     */
    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->connection->rollback();
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

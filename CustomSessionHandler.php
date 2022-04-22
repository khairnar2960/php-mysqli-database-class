<?php
/**
 * CustomSessionHandler
 * 
 * Database Table
 * --------------------------------------------------------------------------------------
 * CREATE TABLE `sessions` (
 *  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
 *  `session_expires` bigint unsigned DEFAULT NULL,
 *  `session_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
 *  PRIMARY KEY (`session_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 * --------------------------------------------------------------------------------------
 */

class CustomSessionHandler implements SessionHandlerInterface{
    
    public $conn;

    /**
     * Database connection constructor
     */
    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "test");
        if ($this->conn->connect_error) {
          die("Connection failed: " . $this->conn->connect_error);
        }
        session_set_save_handler($this, true);
    }

    /**
     * Open Session
     */
    public function open($path, $name) {
        if ($this->conn) {
            return true;
        }else{
            return false;
        }
        
    }
    /**
     * Close Session
     */
    public function close() {
        $this->gc(get_cfg_var("session.gc_maxlifetime"));
        if ($this->conn->close()) {
            return true;
        }else{
            return false;
        }
    }
    /**
     * Read Session
     */
    public function read($session_id) {
        $stmt = $this->conn->prepare("SELECT session_data FROM sessions WHERE session_id= ?");
        $stmt->bind_param("s", $session_id);
        if ($stmt->execute()) {
            $stmt->bind_result($session_data);
            $row = $stmt->fetch();
            $stmt->close();
            return (string) $session_data;
        }else{
            return "";
        }
    }
    /**
     * Write Update Session
     */
    public function write($session_id, $session_data) {
        $stmt = $this->conn->prepare("REPLACE INTO sessions VALUES (?, ?, ?)");
        $session_expires = time();
        $stmt->bind_param("sss", $session_id, $session_expires, $session_data);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }else{
            return false;
        }
    }
    /**
     * Destroy Session
     */
    public function destroy($session_id) {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->bind_param("s", $session_id);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }else{
            return false;
        }
    }
    /**
     * Garbage Collection
     */
    public function gc($max_lifetime) {
        $old = time() - $max_lifetime;
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE session_expires < ?");
        $stmt->bind_param("i", $old);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }else{
            return false;
        }
    }
}
new CustomSessionHandler;
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "harshal";
    $_SESSION['email'] = "harshal@email.com";
    $_SESSION['password'] = "harshal@123";
}
// session_destroy();
var_dump($_SESSION);


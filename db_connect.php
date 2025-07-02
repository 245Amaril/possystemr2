<?php
// File: db_connect.php
// OOP-style database connection

class Database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "kasir";
    public $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        if ($this->conn->connect_error) {
            echo "<script>console.error('Koneksi ke database gagal: ".addslashes($this->conn->connect_error)."');</script>";
            die("Koneksi ke database gagal. Silakan cek konfigurasi koneksi pada file db_connect.php");
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}

?>
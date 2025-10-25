<?php
class Database
{
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "college_fee_system";
    private $connection;

    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8";
            $this->connection = new PDO($dsn, $this->user, $this->pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
?>
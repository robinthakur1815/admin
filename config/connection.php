<?php
class Database{
  
    #specify your own database credentials
    private $host = "localhost";
    private $db_name = "safedev_cybermediaservices.in";
    private $username = "safedev_cybermediaservices";
    private $password = "safedev%cybermediaservices";
    public $conn;
    #MongoDb
    private $usernameMongoDb = "cmrsl2";
    private $passwordMongoDb = "w5PtJ7KpwjOeLZ5";
    public $connMongoDb;

    #get the database connection
    public function getConnection(){
  
        $this->conn = null;
  
       $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
        
        if ($this->conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}

        return $this->conn;
    }

    #get the database connection
    public function getConnectionMongoDb(){
  
        $this->connMongoDb = null;
  
       $this->connMongoDb = new MongoDB\Driver\Manager("mongodb://qtdbs.cyberads.io", array("username" => $this->usernameMongoDb, "password" => $this->passwordMongoDb));

        return $this->connMongoDb;
    }
}
?>
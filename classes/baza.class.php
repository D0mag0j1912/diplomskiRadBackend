<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Content-Type: application/json; charset=UTF-8");

class Baza{
    private $servername;
    private $username;
    private $password;
    private $dbname;

    public function spojiSBazom(){
        $this->servername = "localhost";
        $this->username = "root";
        $this->password = "root";
        $this->dbname = "ambulantatest";

        $conn = new mysqli($this->servername,$this->username,$this->password,$this->dbname);
        $conn -> set_charset("utf8");

        if($conn->connect_error){
            return $conn->connect_error;
        }

        return $conn;
    }
}
?>
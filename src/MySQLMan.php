<?php

use MySQLMan\Manager;

class MySQLMan extends Manager{
    protected $conn;

    /**
     * @param PDO $conn
     * @throws Exception
     */
    public function __construct(PDO &$conn){
        if($conn->getAttribute(PDO::ATTR_DRIVER_NAME)!=='mysql'){
            throw new Exception('Invalid connection driver');
        }
        $this->conn=&$conn;
    }

    protected function quote(string $string){
        return $this->conn->quote($string);
    }

    /**
     * @return PDO
     */
    function conn(){
        return $this->conn;
    }

}
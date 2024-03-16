<?php

use SQLiteMan\Functions;
use SQLiteMan\ManagerBase;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQLiteManPDO extends ManagerBase{
    use Functions;
    /**
     * @var PDO
     */
    protected $conn;
    private static $tbMaster='`sqlite_master`';

    /**
     * @param PDO $conn
     * @throws Exception
     */
    public function __construct(PDO $conn){
        if($conn->getAttribute(PDO::ATTR_DRIVER_NAME)!=='sqlite'){
            throw new Exception('Invalid connection driver');
        }
        $this->conn=&$conn;
    }

    public function timeout(int $sec){
        $this->conn->setAttribute(PDO::ATTR_TIMEOUT, $sec);
    }

    protected function quoteVal(string $value): string{
        if(strpos($value, "\0")!==false){
            return 'x'.$this->conn->quote(bin2hex($value));
        }
        return $this->conn->quote($value);
    }

    public function exec(string $sql): bool{
        $stmt=$this->conn->prepare($sql);
        if(!$stmt) return false;
        return $stmt->execute();
    }

    public function query(string $sql){
        return $this->conn->query($sql);
    }

    public function lastInsertID(){
        $this->conn->lastInsertId();
    }

    public function throwExceptions(bool $enable){
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, $enable?PDO::ERRMODE_EXCEPTION:PDO::ERRMODE_SILENT);
    }

    public function lastError(): \SQLiteMan\Exception{
        return \SQLiteMan\Exception::fromPDOConn($this->conn);
    }

    /**
     * @return PDO
     */
    public function conn(){
        return $this->conn;
    }

    public static function columnIndex(PDOStatement $stmt, string $column): ?int{
        $i=-1;
        $count=$stmt->columnCount();
        while(++$i<$count){
            if($stmt->getColumnMeta($i)['name']==$column) return $i;
        }
        return null;
    }

}
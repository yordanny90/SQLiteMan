<?php

use SQLiteMan\Functions;
use SQLiteMan\ManagerBase;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQLiteMan extends ManagerBase{
    use Functions;
    /**
     * @var SQLite3
     */
    protected $conn;
    protected $fetchMode=SQLITE3_ASSOC;
    private static $tbMaster='`sqlite_master`';

    /**
     * @param SQLite3 $conn
     * @throws Exception
     */
    public function __construct(SQLite3 $conn){
        $this->conn=&$conn;
    }

    public function timeout(int $sec){
        $this->conn->busyTimeout($sec*1000);
    }

    public function timeout_ms(int $ms){
        $this->conn->busyTimeout($ms);
    }

    public function fetchMode(int $mode=SQLITE3_ASSOC){
        $this->fetchMode=$mode;
    }

    protected function quoteVal(string $value): string{
        if(strpos($value, "\0")!==false){
            return "x'".SQlite3::escapeString(bin2hex($value))."'";
        }
        return "'".SQlite3::escapeString($value)."'";
    }

    public function exec(string $sql): bool{
        $stmt=$this->conn->prepare($sql);
        if(!$stmt) return false;
        $res=$stmt->execute();
        return $res?true:false;
    }

    public function query(string $sql){
        return $this->result($this->conn->query($sql));
    }

    public function lastInsertID(){
        $this->conn->lastInsertRowID();
    }

    public function throwExceptions(bool $enable){
        $this->conn->enableExceptions($enable);
    }

    public function lastError(): \SQLiteMan\Exception{
        return \SQLiteMan\Exception::fromSQLiteConn($this->conn);
    }

    /**
     * @return SQLite3
     */
    public function conn(){
        return $this->conn;
    }

    public static function columnIndex(SQLite3Result $stmt, string $column): ?int{
        $i=-1;
        $count=$stmt->numColumns();
        while(++$i<$count){
            if($stmt->columnName($i)==$column) return $i;
        }
        return null;
    }

    /**
     * @param SQLite3Result|false $res
     * @return \SQLiteMan\Result|false
     */
    public function result($res){
        if(is_a($res, SQLite3Result::class)) return new \SQLiteMan\Result($res, $this->fetchMode);
        return false;
    }
}
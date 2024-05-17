<?php

use SQLiteMan\Manager;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQLiteMan extends Manager{
    /**
     * @var PDO
     */
    protected $conn;

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

    public function version(){
        $this->conn->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    public function timeout(int $sec): bool{
        return $this->conn->setAttribute(PDO::ATTR_TIMEOUT, $sec);
    }

    protected function quoteVal(string $value): string{
        if(strpos($value, "\0")!==false) return $this->value_hex($value)->_concat('').'';
        return $this->conn->quote($value);
    }

    protected function quoteHex(string $value): string{
        return 'x'.$this->conn->quote(bin2hex($value));
    }

    protected function prepare(string $sql, ?array $params=null): ?PDOStatement{
        $stmt=$this->conn->prepare($sql);
        if(!$stmt) return null;
        if($params){
            foreach($params AS $p=>$v){
                $stmt->bindValue($p, $v);
            }
        }
        return $stmt;
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return bool|int|null
     */
    public function exec(string $sql, ?array $params=null){
        $stmt=$this->prepare($sql, $params);
        if(!$stmt) return null;
        return $stmt->execute()?$stmt->rowCount():false;
    }

    public function query(string $sql, ?array $params=null){
        $stmt=$this->prepare($sql, $params);
        if(!$stmt) return null;
        $stmt->execute();
        return new \SQLiteMan\Result($stmt);
    }

    public function lastInsertID(){
        $this->conn->lastInsertId();
    }

    /**
     * @param int $fetchMode Valores sugeridos: {@see PDO::FETCH_ASSOC}, {@see PDO::FETCH_NUM}, {@see PDO::FETCH_BOTH}, {@see PDO::FETCH_OBJ}, {@see PDO::FETCH_NAMED}
     * @return bool
     */
    public function fetchMode(int $fetchMode): bool{
        return $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);
    }

    /**
     * @return int
     * @see SQLiteMan::fetchMode()
     */
    public function getFetchMode(): int{
        return intval($this->conn->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function throwExceptions(bool $enable){
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, $enable?PDO::ERRMODE_EXCEPTION:PDO::ERRMODE_SILENT);
    }

    public function lastError(): ?\SQLiteMan\Exception{
        return \SQLiteMan\Exception::fromPDOConn($this->conn);
    }

    /**
     * @return PDO
     */
    public function conn(){
        return $this->conn;
    }
}
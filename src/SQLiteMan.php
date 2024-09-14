<?php

use SQLiteManager\Manager_base;
use SQLiteManager\Manager_adds;
use SQLiteManager\Result;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
class SQLiteMan{
	use Manager_base;
	use Manager_adds;

	const SCHEMA_MAIN='main';
	const SCHEMA_TEMP='temp';
	const TYPE_INTEGER='INTEGER';
	const TYPE_REAL='REAL';
	const TYPE_TEXT='TEXT';
	const TYPE_BLOB='BLOB';
	const TYPE_NUMERIC='NUMERIC';
	const TYPES=[
		self::TYPE_INTEGER,
		self::TYPE_REAL,
		self::TYPE_TEXT,
		self::TYPE_BLOB,
		self::TYPE_NUMERIC,
	];

	/**
	 * Ver {@link https://www.sqlite.org/datatype3.html}
	 *
	 * Si la columna no tiene un tipo, su afinidad es {@see SQLiteMan::TYPE_BLOB}
	 *
	 * Si el tipo no coincide con la lista {@see SQLiteMan::TYPES},
	 * la afinidad de la columna se asocia al primer valor cuyo índice sea parte del tipo de la columna.
	 *
	 * Si no coincide con ninguno de la lista, su afinidad es {@see SQLiteMan::TYPE_NUMERIC}
	 *
	 * Ejemplos:
	 * - "FLOATING POINT" tendrá afinidad con {@see SQLiteMan::TYPE_INTEGER} ya que contiene "INT", y está antes que con "FLOA" en la lista
	 * - "DECIMAL", "DATE", "BOOL" y "STRING" tendrán afinidad con {@see SQLiteMan::TYPE_NUMERIC} ya que no contienen ningun índice de la lista
	 * @see SQLiteMan::typeColumnAffinity()
	 */
	const TYPES_AFFINITY=[
		'INT'=>self::TYPE_INTEGER,
		'CHAR'=>self::TYPE_TEXT,
		'CLOB'=>self::TYPE_TEXT,
		'TEXT'=>self::TYPE_TEXT,
		'BLOB'=>self::TYPE_BLOB,
		'REAL'=>self::TYPE_REAL,
		'FLOA'=>self::TYPE_REAL,
		'DOUB'=>self::TYPE_REAL,
	];

	const JOIN_INNER='INNER';
	const JOIN_LEFT='LEFT';
	const JOIN_LEFT_OUTER='LEFT OUTER';
	const JOIN_RIGTH='RIGHT';
	const JOIN_RIGTH_OUTER='RIGHT OUTER';
	const JOIN_FULL='FULL';
	const JOIN_FULL_OUTER='FULL OUTER';
	const JOINS=[
		self::JOIN_INNER,
		self::JOIN_LEFT,
		self::JOIN_LEFT_OUTER,
		self::JOIN_RIGTH,
		self::JOIN_RIGTH_OUTER,
		self::JOIN_FULL,
		self::JOIN_FULL_OUTER,
	];

	const OR_ABORT='ABORT';
	const OR_FAIL='FAIL';
	const OR_IGNORE='IGNORE';
	const OR_REPLACE='REPLACE';
	const OR_ROLLBACK='ROLLBACK';

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
     * @return Result|null
     */
    public function query(string $sql, ?array $params=null): ?Result{
        $stmt=$this->prepare($sql, $params);
        if(!$stmt || !$stmt->execute()) return null;
        return new Result($stmt);
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

    public function lastError(): ?\SQLiteManager\Exception{
        return \SQLiteManager\Exception::fromPDOConn($this->conn);
    }

    /**
     * @return PDO
     */
    public function conn(){
        return $this->conn;
    }
}
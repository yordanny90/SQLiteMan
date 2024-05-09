<?php

namespace SQLiteMan;

use PDO;
use PDOStatement;

class Result implements \IteratorAggregate{
    /**
     * @var \PDOStatement|null
     */
    protected $res;

    public function __construct(PDOStatement $result){
        $this->res=&$result;
    }

    /**
     * @return int
     * @see PDOStatement::columnCount()
     */
    public function columnCount(){
        return $this->res->columnCount();
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function getColumnIndex(string $name): ?int{
        $i=-1;
        $c=$this->columnCount();
        while(++$i<$c){
            if($this->getColumnName($i)==$name) return $i;
        }
        return null;
    }

    /**
     * @param int $column
     * @return string|null
     * @see Result::getColumnMeta()
     */
    public function getColumnName(int $column): ?string{
        return $this->getColumnMeta($column)['name'] ?? null;
    }

    /**
     * @param int $column
     * @return string|null
     * @see Result::getColumnMeta()
     */
    public function getColumnType(int $column): ?string{
        return $this->getColumnMeta($column)['sqlite:decl_type'] ?? null;
    }

    /**
     * Ver {@see PDOStatement::getColumnMeta()}
     * @param int $column
     * @return array|null
     */
    public function getColumnMeta(int $column): ?array{
        return $this->res->getColumnMeta($column)?:null;
    }

    public function fetch(?int $mode=null){
        return $this->res->fetch($mode);
    }

    public function fetchColumn(int $column){
        return $this->res->fetchColumn($column);
    }

    /**
     * Ver {@see PDOStatement::fetchAll()}
     * @param int $mode
     * @param ...$fetchArgs
     * @return array|false
     */
    public function fetchAll(int $mode, ...$fetchArgs){
        return $this->res->fetchAll($mode, ...$fetchArgs);
    }

    public function getIterator(): \Traversable{
        return $this->res;
    }
}
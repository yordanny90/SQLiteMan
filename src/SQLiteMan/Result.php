<?php

namespace SQLiteMan;

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
     * Conteo de filas afectadas. Válido para sentencias que alteran los datos, como update, delete, insert
     * @return int|null
     */
    public function affectedRows(): ?int{
        return $this->columnCount()===0?$this->res->rowCount():null;
    }

    /**
     * @return int
     * @see PDOStatement::columnCount()
     */
    public function columnCount(){
        return $this->res->columnCount();
    }

    /**
     * @return array
     */
    public function getColumnNames(): array{
        $names=[];
        $i=-1;
        $c=$this->columnCount();
        while(++$i<$c){
            $names[$i]=$this->getColumnName($i);
        }
        return $names;
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
     * @param int $column
     * @return string|null
     * @see Result::getColumnMeta()
     */
    public function getColumnNativeType(int $column): ?string{
        return $this->getColumnMeta($column)['native_type'] ?? null;
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
        if($mode===null) return $this->res->fetch();
        return $this->res->fetch($mode);
    }

    public function fetchColumn(int $column){
        return $this->res->fetchColumn($column);
    }

    /**
     * Ver {@see PDOStatement::fetchAll()}
     * @param int|null $mode
     * @param ...$fetchArgs
     * @return array
     */
    public function fetchAll(?int $mode=null, ...$fetchArgs): array{
        if($mode===null) return $this->res->fetchAll()?:[];
        return $this->res->fetchAll($mode, ...$fetchArgs)?:[];
    }

    public function getIterator(): \Traversable{
        return $this->res;
    }
}
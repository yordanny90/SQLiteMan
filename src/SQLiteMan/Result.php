<?php

namespace SQLiteMan;

use SQLite3Result;

class Result implements \Countable, \Iterator, \IteratorAggregate{
    /**
     * @var SQLite3Result
     */
    protected $res;
    /**
     * @var ?int
     */
    protected $mode=SQLITE3_ASSOC;
    protected $k=-1;
    /**
     * @var false|array
     */
    protected $v=false;

    public function __construct(SQLite3Result $result, int $mode=SQLITE3_ASSOC){
        $this->res=$result;
        $this->fetchMode($mode);
        $this->res->reset();
    }

    /**
     * @param int $mode Posibles valores: {@see SQLITE3_ASSOC}, {@see SQLITE3_NUM}, {@see SQLITE3_BOTH}
     * @return void
     */
    public function fetchMode(int $mode){
        $this->mode=$mode;
    }

    /**
     * @return bool
     * @see SQLite3Result::reset()
     */
    public function reset(){
        $this->k=-1;
        $this->v=false;
        return $this->res->reset();
    }

    /**
     * @param int $mode Default: {@see SQLITE3_BOTH}
     * @return array|false
     * @see SQLite3Result::fetchArray()
     */
    public function fetchArray(int $mode=SQLITE3_BOTH){
        if(isset($this->count) && $this->k>=$this->count) $this->k=-1;
        ++$this->k;
        $this->v=$this->res->fetchArray($mode);
        if(!$this->valid() && $this->k>=0) $this->count=$this->k;
        return $this->v;
    }

    /**
     * Ver {@see Result::fetchMode()}
     * @return array|false
     */
    public function fetch(){
        return $this->fetchArray($this->mode);
    }

    /**
     * @return int
     * @see SQLite3Result::numColumns()
     */
    public function numColumns(){
        return $this->res->numColumns();
    }

    /**
     * @param int $column
     * @return false|string
     * @see SQLite3Result::columnName()
     */
    public function columnName(int $column){
        return $this->res->columnName($column);
    }

    /**
     * @param int $column
     * @return false|int
     * @see SQLite3Result::columnType()
     */
    public function columnType(int $column){
        return $this->res->columnType($column);
    }

    public function finalize(){
        $this->k=-1;
        $this->v=false;
        return $this->res->finalize();
    }

    private $count;

    public function count(){
        if(is_int($this->count)) return $this->count;
        if(!$this->res->reset()) return 0;
        $c=0;
        while($this->res->fetchArray(SQLITE3_NUM)!==false){
            ++$c;
        }
        $this->count=$c;
        if(!$this->res->reset()) return $this->count;
        $k=-2;
        while($this->k>++$k){
            $this->res->fetchArray(SQLITE3_NUM);
        }
        return $this->count;
    }

    public function current(){
        return $this->v;
    }

    public function next(){
        $this->fetch();
    }

    public function key(){
        return $this->k;
    }

    public function valid(){
        return is_array($this->v);
    }

    public function rewind(){
        $this->reset();
        $this->next();
    }

    public function getIterator(){
        return $this;
    }
}
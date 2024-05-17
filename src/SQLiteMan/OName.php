<?php

namespace SQLiteMan;

class OName implements SelfEscape{
    private $n;

    public function __construct(string $name){
        $this->n=$name;
    }

    public function toSQLite(Manager &$man): SQL{
        return $man->name($this->n);
    }

    public function __toString(){
        return $this->n;
    }
}
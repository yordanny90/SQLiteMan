<?php

namespace SQLiteMan;

/**
 * Dato previamente escapado
 *
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
class OSQL implements SelfEscape{
    private $n;

    public function __construct(string $sql){
        $this->n=$sql;
    }

    public function toSQLite(Manager &$man): SQL{
        return $man->sql($this->n);
    }

    public function __toString(){
        return $this->n;
    }
}
<?php

namespace SQLiteMan;

/**
 * Dato previamente escapado
 *
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
class OParam implements SelfEscape{
    private $n;

    private function __construct(string $name){
        $this->n=$name;
    }

    /**
     * @param string $param
     * @return static|null
     */
    public static function make(string $param): ?self{
        if(!preg_match('/^\:\w+$/', $param)) return null;
        return new self($param);
    }

    public function toSQLite(Manager &$man): SQL{
        return $man->sql($this->n);
    }

    public function __toString(){
        return $this->n;
    }
}
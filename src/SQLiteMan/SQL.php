<?php

namespace SQLiteMan;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQL extends Data{
    protected function __construct(string $data){
        parent::__construct($data);
    }

    function &not(): self{
        $this->d='NOT '.$this->d;
        return $this;
    }

    public function &parentheses(): self{
        $this->d='('.$this->d.')';
        return $this;
    }

    public function &add(string $sql): self{
        $this->d.=' '.$sql;
        return $this;
    }

    public function __toString(){
        return $this->data();
    }
}
<?php

namespace SQTypes;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQL extends \SQData{
    protected function __construct(string $data){
        parent::__construct($data);
    }

    public function getType(): int{
        return static::TYPE_SQL;
    }

    public function __toString(){
        return $this->getData();
    }
}
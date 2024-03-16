<?php

namespace SQTypes;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class Value extends \SQData{
    protected function __construct($data){
        if(!is_scalar($data) && $data!==null) $data=strval($data);
        parent::__construct($data);
    }

    public function getType(): int{
        return static::TYPE_VALUE;
    }
}
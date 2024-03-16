<?php

namespace SQTypes;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class Name extends \SQData{
    protected function __construct(string $data){
        parent::__construct($data);
    }

    public function getType(): int{
        return static::TYPE_NAME;
    }
}
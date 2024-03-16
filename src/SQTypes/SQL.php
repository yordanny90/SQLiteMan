<?php

namespace SQTypes;

/**
 * #SQValue
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQL extends \SQVar{
    public function getType(): int{
        return static::TYPE_SQL;
    }

    public function __toString(){
        return $this->getData();
    }
}
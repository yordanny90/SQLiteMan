<?php

namespace SQTypes;

/**
 * #SQValue
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class Value extends \SQVar{
    public function getType(): int{
        return static::TYPE_VALUE;
    }
}
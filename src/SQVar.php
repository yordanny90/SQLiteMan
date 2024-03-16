<?php

use SQTypes\Value;

/**
 * #SQVar
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
abstract class SQVar{
    const TYPE_VALUE=0;
    const TYPE_NAME=1;
    const TYPE_SQL=2;

    /**
     * @var scalar|null
     */
    protected $d;

    protected function __construct($data){
        $this->d=$data;
    }

    /**
     * - {@see SQVar::TYPE_VALUE}
     *
     * - {@see SQVar::TYPE_NAME}
     *
     * - {@see SQVar::TYPE_SQL}
     *
     * @return int
     */
    abstract public function getType(): int;

    /**
     * El dato se debe escapar como un valor
     * @param scalar|null $value
     * @return static
     */
    public static function v($value){
        if(!is_scalar($value) && $value!==null) $value=strval($value);
        return new Value($value);
    }

    /**
     * El dato se debe escapar como un nombre
     * @param string $name
     * @return static
     */
    public static function n(string $name){
        return new SQTypes\Name($name);
    }

    /**
     * El dato SQL no se debe escapar
     * @param string $sql
     * @return static
     */
    public static function s(string $sql){
        return new \SQTypes\SQL($sql);
    }

    public function getData(){
        return $this->d;
    }
}
<?php

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
abstract class SQData{
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
     * - {@see SQData::TYPE_VALUE}
     *
     * - {@see SQData::TYPE_NAME}
     *
     * - {@see SQData::TYPE_SQL}
     *
     * @return int
     */
    abstract public function getType(): int;

    /**
     * El dato se debe escapar como un valor
     * @param scalar|null $value
     * @return \SQTypes\Value
     */
    public static function v($value){
        return new \SQTypes\Value($value);
    }

    /**
     * El dato se debe escapar como un nombre
     * @param string $name
     * @return \SQTypes\Name
     */
    public static function n(string $name){
        return new SQTypes\Name($name);
    }

    /**
     * El dato SQL no se debe escapar
     * @param string $sql
     * @return \SQTypes\SQL
     */
    public static function s(string $sql){
        return new \SQTypes\SQL($sql);
    }

    public function getData(){
        return $this->d;
    }
}
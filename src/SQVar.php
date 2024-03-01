<?php

/**
 * #SQVar
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class SQVar{
    const TYPE_VALUE=0;
    const TYPE_NAME=1;
    const TYPE_SQL=2;

    /**
     * @var scalar|array|null
     */
    private $d;
    private $t;

    private function __construct($data, int $type){
        $this->d=$data;
        $this->t=$type;
    }

    /**
     * El dato se debe escapar como un valor
     * @param scalar|null $value
     * @return static
     */
    public static function v($value){
        if(!is_scalar($value) && $value!==null) $value=strval($value);
        return new static($value, self::TYPE_VALUE);
    }

    /**
     * El dato se debe escapar como un nombre
     * @param array|string $name
     * @return static
     */
    public static function n($name){
        if(!is_array($name)) $name=strval($name);
        return new static($name, self::TYPE_NAME);
    }

    /**
     * El dato SQL no se debe escapar
     * @param string $sql
     * @return static
     */
    public static function s(string $sql){
        return new static($sql, self::TYPE_SQL);
    }

    public function getType(): int{
        return $this->t;
    }

    public function getData(){
        return $this->d;
    }
}
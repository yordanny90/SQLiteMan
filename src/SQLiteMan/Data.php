<?php

namespace SQLiteMan;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
abstract class Data{
    /**
     * @var scalar|null
     */
    protected $d;

    protected function __construct($data){
        $this->d=$data;
    }

    /**
     * El dato se debe escapar como un valor binario
     * @param string $value
     * @return Binary
     */
    public static function bin(string $value){
        return new Binary($value);
    }

    /**
     * El dato se debe escapar como un valor. Se eliminan los caracteres null ("\0")
     * @param scalar|null $value
     * @return Value
     */
    public static function val($value){
        return new Value($value);
    }

    /**
     * El dato se debe escapar como un nombre
     * @param string $name
     * @return Name
     */
    public static function name(string $name){
        return new Name($name);
    }

    /**
     * El dato SQL no se debe escapar
     * @param string $sql
     * @return SQL
     */
    public static function sql(string $sql){
        return new SQL($sql);
    }

    /**
     * @return bool|float|int|string|null
     */
    public function data(){
        return $this->d;
    }

}
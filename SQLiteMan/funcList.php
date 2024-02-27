<?php

namespace SQLiteMan;

use \SQVar;

/**
 * <b>IMPORTANTE:</b>
 * <p>La funciones que se declaren aquí, deben apegarse a la documentación oficial de sqlite.</p>
 * <br>
 * Las funciones siempre se deben documentar siguiendo los estándares.
 *
 * El nombre de las funciones siempre debe iniciar con un guión bajo (_)
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 *
 * @link https://sqlite.org/lang_corefunc.html
 */
trait funcList{

    /**
     * Concatena valores
     * @param $name
     * @param ...$names
     * @return SQVar
     */
    public function _CONCAT($name, ...$names){
        $sql=[$this->nameVar($name)];
        foreach($names as $n){
            $sql[]=$this->nameVar($n);
        }
        $sql=implode('||', $sql);
        return SQVar::s($sql);
    }
}

<?php

namespace SQLiteMan;

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
trait Functions{

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en valores</b>
     * @param string $fn Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return SQL
     */
    public function &fn_values(string $fn, ...$params): SQL{
        return $this->sql($fn)->add($this->values($params)->parentheses());
    }

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en nombres</b>
     * @param string $fn Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return SQL
     */
    public function &fn_names(string $fn, ...$params): SQL{
        return $this->sql($fn)->add($this->nameList($params, false)->parentheses());
    }

    public function cond_equal($name, $val){
        if($val===null) return $this->cond_is($name, $val);
        return $this->name($name)->add('<>')->add($this->value($val));
    }

    public function &cond_diff($name, $val){
        if($val===null) return $this->cond_is_not($name, $val);
        return $this->name($name)->add('<>')->add($this->value($val));
    }

    public function cond_is($name, $val){
        return $this->name($name)->add('IS')->add($this->value($val));
    }

    public function &cond_is_not($name, $val){
        return $this->name($name)->add('IS NOT')->add($this->value($val));
    }

    public function &cond_greater($name, $val){
        return $this->name($name)->add('>')->add($this->value($val));
    }

    public function &cond_not_greater($name, $val){
        return $this->name($name)->add('!>')->add($this->value($val));
    }

    public function &cond_less($name, $val){
        return $this->name($name)->add('<')->add($this->value($val));
    }

    public function &cond_not_less($name, $val){
        return $this->name($name)->add('!<')->add($this->value($val));
    }

    public function &cond_less_equal($name, $val){
        return $this->name($name)->add('<=')->add($this->value($val));
    }

    public function &cond_greater_equal($name, $val){
        return $this->name($name)->add('>=')->add($this->value($val));
    }

    public function &cond_between($name, $val_init, $val_end){
        return $this->name($name)->add('BETWEEN')->add($this->value($val_init))->add('AND')->add($this->value($val_end));
    }

    public function &cond_not_between($name, $val_init, $val_end){
        return $this->name($name)->add('NOT BETWEEN')->add($this->value($val_init))->add('AND')->add($this->value($val_end));
    }

    public function &cond_inlist($name, array $values){
        return $this->name($name)->add('IN')->add($this->values($values)->parentheses());
    }

    public function &cond_not_inlist($name, $val){
        return $this->name($name)->add('NOT IN')->add($this->values($val)->parentheses());
    }

    public function &cond_like($name, $val){
        return $this->name($name)->add('LIKE')->add($this->value($val));
    }

    public function &cond_not_like($name, $val){
        return $this->name($name)->add('NOT LIKE')->add($this->value($val));
    }

    public function &cond_begins($name, $val){
        return $this->cond_like($name, $this->_CONCAT($val,'%'));
    }

    public function &cond_ends($name, $val){
        return $this->cond_like($name, $this->_CONCAT('%', $val));
    }

    public function &cond_contains($name, $val){
        return $this->cond_like($name, $this->_CONCAT('%', $val, '%'));
    }

    public function &cond_not_begins($name, $val){
        return $this->cond_not_like($name, $this->_CONCAT($val,'%'));
    }

    public function &cond_not_ends($name, $val){
        return $this->cond_not_like($name, $this->_CONCAT('%', $val));
    }

    public function &cond_not_contains($name, $val){
        return $this->cond_not_like($name, $this->_CONCAT('%', $val, '%'));
    }

    /**
     * Genera el SQL de un CASE utilizable dentro de otra sentencia SQL.<br>
     * El objetivo es obtener un valor según la condición que se cumpla.
     * @param bool|Data|string $case_name Valor inicial que se comparará con las condiciones
     * @param array $when_list Lista de condiciones y los valores correspondientes.<br>
     * En este array, las llaves deben ser condiciones (sentencias SQL) y los valores se escaparán si no son sentencias SQL
     * @param null $else_value Valor que se devolverá si ninguna condición se cumple
     * @return SQL
     */
    /**
     * @param $case_name
     * @param ...$when_list
     * @return SQL
     */
    public function &case_clause($case_name, array $when_list, $else_value): SQL{
        $res='CASE';
        if($case_name!==true){
            $res.=' '.$this->name($case_name);
        }
        foreach($when_list as $when){
            $res.=' '.$this->when_clause($when);
        }
        if($else_value!==null){
            $res.=' ELSE '.$this->value($else_value);
        }
        $res.=' END';
        $res=$this->sql($res);
        return $res;
    }

    public function &when_clause($when): SQL{
        $res=$this->sql('WHEN');
        if(is_array($when)){
            if(count($when)<=1){
                $res->add($this->value(array_keys($when)[0]??null))->add('THEN')->add($this->value(array_values($when)[0]??null));
            }
            else{
                $res->add($this->value(array_values($when)[0]))->add('THEN')->add($this->value(array_values($when)[1]));
            }
        }
        return $res;
    }

    public function _CAST($val, $type): SQL{
        return $this->fn_names('CAST', $this->value($val)->add('AS')->add($type));
    }

    /**
     * Concatena valores
     * @param $val
     * @param ...$values
     * @return SQL
     */
    public function _CONCAT($val, ...$values): SQL{
        $sql=[$this->value($val)];
        foreach($values as $v){
            $sql[]=$this->value($v);
        }
        $sql=implode('||', $sql);
        return $this->sql($sql);
    }
}

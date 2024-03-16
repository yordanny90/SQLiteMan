<?php

namespace MySQLMan;
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
 * @link https://dev.mysql.com/doc/refman/5.7/en/sql-function-reference.html
 */
trait Functions{
    /**
     * Funcion random
     * @return static
     */
    function &_RAND(){
        return $this->fn('RAND');
    }

    /**
     * Devuelve el conteo de los valores en la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_COUNT($var='*'){
        return $this->fn_names('COUNT', $var);
    }

    /**
     * Devuelve el conteo de los valores (Distintos) en la(s) columna(s)
     * @param string|array $vars Nombre de la columna o lista de columnas
     * @return static
     */
    function &_COUNT_distinct($vars){
        if(is_array($vars)) $vars=array_values($vars);
        $res=$this->fn('COUNT', $this->sql('DISTINCT')->add_names($vars));
        return $res;
    }

    /**
     * Devuelve el promedio (media) de los valores en la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_AVG($var){
        $res=$this->fn_names('AVG', $var);
        return $res;
    }

    /**
     * Devuelve el promedio (media) de los valores (Distintos) en la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_AVG_distinct($var){
        $res=$this->fn('AVG', $this->sql('DISTINCT')->add_name($var));
        return $res;
    }

    /**
     * Devuelve la sumatoria de los valores en la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_SUM($var){
        $res=$this->fn_names('SUM', $var);
        return $res;
    }

    /**
     * Devuelve el valor negativo a positivo de una columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_ABS($var){
        $res=$this->fn_names('ABS', $var);
        return $res;
    }

    /**
     * Devuelve el valor mínimo en la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_MIN($var){
        $res=$this->fn_names('MIN', $var);
        return $res;
    }

    /**
     * Devuelve el valor máximo en la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_MAX($var){
        $res=$this->fn_names('MAX', $var);
        return $res;
    }

    /**
     * Devuelve el valor redondeado en la columna
     * @param string $var Nombre de la columna
     * @param int $decimals Cantidad de decimales
     * @return static
     */
    function &_ROUND($var, $decimals=null){
        $params=[$this->n($var)];
        if(!is_null($decimals)){
            $params[]=$this->v($decimals, null, true);
        }
        $res=$this->fn_values('ROUND', ...$params);
        return $res;
    }

    /**
     * Devuelve la concatenación de los valores no nulos de la columna
     * @param string|array $vars Nombre de la columna o lista de columnas
     * @param null|array $orderby Columnas de ordenamiento
     * @param null|string $separator Separador del concatenado
     * @return static
     */
    function &_GROUP_CONCAT($vars, $orderby=null, $separator=null){
        if(is_array($vars)) $vars=array_values($vars);
        $p1=$this->names($vars);
        if($orderby!==null){
            $p1->add('ORDER BY')->add_orderby($orderby);
        }
        if($separator!==null){
            $p1->add('SEPARATOR')->add_value($separator);
        }
        $res=$this->fn('GROUP_CONCAT', $p1);
        return $res;
    }

    /**
     * Devuelve la concatenación de los valores (Distintos) no nulos de la columna
     * @param string|array $vars Nombre de la columna o lista de columnas
     * @param null|array $orderby Columnas de ordenamiento
     * @param null|string $separator Separador del concatenado
     * @return static
     */
    function &_GROUP_CONCAT_distinct($vars, $orderby=null, $separator=null){
        if(is_array($vars)) $vars=array_values($vars);
        return $this->_GROUP_CONCAT($this->sql('DISTINCT')->add_names($vars), $orderby, $separator);
    }

    /**
     * Devuelve la concatenación de los valores de las columnas.<br>
     * Retorna NULL si al menos uno de los valores es NULL.
     * @param ...$vars string Lista de columnas
     * @return static
     */
    function &_CONCAT(...$vars){
        $res=$this->fn_names('CONCAT', $vars);
        return $res;
    }

    /**
     * Devuelve la concatenación de los valores de las columnas, con separador
     * @param string $separator Valor de separación
     * @param array $vars Lista de columnas
     * @return static
     */
    function &_CONCAT_WS($separator, ...$vars){
        $res=$this->fn_names('CONCAT_WS', $this->v($separator), ...$vars);
        return $res;
    }

    /**
     * Devuelve la posición del valor en el SET de valores separadas por comas.<br>
     * Es usado comúnmente para validar la existencia del valor dentro de un SET.
     * @param string $val_search Valor que se buscará
     * @param string $var Nombre de la columna (SET de valores separados por comas)
     * @return static
     */
    function &_FIND_IN_SET($val_search, $var){
        $res=$this->fn_values('FIND_IN_SET', $val_search, $this->n($var));
        return $res;
    }

    /**
     * Devuelve uno de los valores según la condición.
     * @param string $expr Nombre de la columna o condición del IF
     * @param string $then Valor devuelto si la condición es TRUE
     * @param string $else Valor devuelto si la condición es FALSE
     * @return static
     */
    function &_IF($expr, $then, $else){
        $res=$this->fn_values('IF', $this->n($expr), $then, $else);
        return $res;
    }

    /**
     * Devuelve otro valor si el primero es NULL, de lo contratrio devuelve el primero
     * @param string $var Nombre de la columna (primero)
     * @param string $then Valor devuelto si el primero es NULL
     * @return static
     */
    function &_IFNULL($var, $then){
        $res=$this->fn_values('IFNULL', $this->n($var), $then);
        return $res;
    }

    /**
     * Devuelve el valor en la columna sin espacios al inicio
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_LTRIM($var){
        $res=$this->fn_names('LTRIM', $var);
        return $res;
    }

    /**
     * Devuelve el valor en la columna sin espacios al final
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_RTRIM($var){
        $res=$this->fn_names('RTRIM', $var);
        return $res;
    }

    /**
     * Devuelve el valor en la columna sin espacios al inicio ni al final
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_TRIM($var){
        return $this->fn_names('TRIM', $var);
    }

    /**
     * Devuelve el valor en la columna con el string removido del inicio y del final
     * @param string $var Nombre de la columna
     * @param string $remstr String que se removerá del valor
     * @return static
     */
    function &_TRIM_both($var, $remstr){
        return $this->fn('TRIM', $this->sql('BOTH')->add_value($remstr)->add('FROM')->add_name($var));
    }

    /**
     * Devuelve el valor en la columna con el string removido del inicio
     * @param string $var Nombre de la columna
     * @param string $remstr String que se removerá del valor
     * @return static
     */
    function &_TRIM_leading($var, $remstr){
        return $this->fn('TRIM', $this->sql('LEADING')->add_value($remstr)->add('FROM')->add_name($var));
    }

    /**
     * Devuelve el valor en la columna con el string removido del final
     * @param string $var Nombre de la columna
     * @param string $remstr String que se removerá del valor
     * @return static
     */
    function &_TRIM_trailing($var, $remstr){
        return $this->fn('TRIM', $this->sql('TRAILING')->add_value($remstr)->add('FROM')->add_name($var));
    }

    /**
     * Devuelve el substring en la columna utilizando un string delimitador y su número
     * @param string $var Nombre de la columna
     * @param string $delim String delimitador
     * @param int $count Número del delimitador a utilizar.<br>
     * Si es positivo, devuelve el substring a la izquierda del delimitador contado desde la izquierda.<br>
     * Si es negativo, devuelve el substring a la derecha del delimitador contado desde la derecha.<br>
     * @return static
     */
    function &_SUBSTRING_INDEX($var, $delim, $count){
        $res=$this->fn('SUBSTRING_INDEX', array(
            $this->n($var),
            $delim,
            $this->v($count, null, true)
        ), true);
        return $res;
    }

    /**
     * Devuelve el substring en la columna por medio de una posición y longitud
     * @param string $var Nombre de la columna
     * @param int $start Posición inicial. La posición del primer caracter es 1.<br>
     * Si es negativo, la posición se cuenta desde la derecha.
     * @param null|int $length Longitud máxima del substring. Si es NULL, se extrae hasta el final del string
     * @return static
     */
    function &_SUBSTR($var, $start, $length=null){
        $params=array(
            $this->n($var),
            $this->v($start, null, true),
        );
        if(!is_null($length)){
            $params[]=$this->v($length, null, true);
        }
        $res=$this->fn('SUBSTR', $params, true);
        return $res;
    }

    /**
     * Devuelve la posición del substring en la columna.<br>
     * Si no se encuentra el substring, el resultado será 0.
     * @param string $string String a localizar
     * @param string $var Nombre de la columna
     * @param int|null $start Si se especifica, indica la posición en la que se inicia la búsqueda.<br>
     * La posición del primer caracter es 1.<br>
     * @return static
     */
    function &_LOCATE($string, $var, $start=null){
        $params=array(
            $this->v($string),
            $this->n($var),
        );
        if(!is_null($start)){
            $params[]=$this->v($start, null, true);
        }
        $res=$this->fn('LOCATE', $params, true);
        return $res;
    }

    /**
     * Codifica a BASE64
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_TO_BASE64($var){
        $res=$this->fn_names('TO_BASE64', $var);
        return $res;
    }

    /**
     * Decodifica de BASE64
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_FROM_BASE64($var){
        $res=$this->fn_names('FROM_BASE64', $var);
        return $res;
    }

    /**
     * Convierte en mayúsculas
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_UPPER($var){
        $res=$this->fn_names('UPPER', $var);
        return $res;
    }

    /**
     * Convierte en minúculas
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_LOWER($var){
        $res=$this->fn_names('LOWER', $var);
        return $res;
    }

    /**
     * Completa el valor a la longitud indicada, con llenado a la derecha
     * @param string $var Nombre de la columna
     * @param int $len Longitud a completar
     * @param string $padstr String de relleno
     * @return static
     */
    function &_RPAD($var, $len, $padstr){
        $res=$this->fn_names('RPAD', $var, $this->v($len), $this->v($padstr));
        return $res;
    }

    /**
     * Completa el valor a la longitud indicada, con llenado a la izquierda
     * @param string $var Nombre de la columna
     * @param int $len Longitud a completar
     * @param string $padstr String de relleno
     * @return static
     */
    function &_LPAD($var, $len, $padstr){
        $res=$this->fn_names('LPAD', $var, $this->v($len), $this->v($padstr));
        return $res;
    }

    /**
     * Devuelve un array JSON con los valores indicados
     * @param array $list Lista de valores
     * @return static
     */
    function &_JSON_ARRAY(array $list){
        $res=$this->fn('JSON_ARRAY', $list, true);
        return $res;
    }

    /**
     * Devuelve un objeto JSON basado en la lista indicado. La longitud de la lista debe ser par.<br>
     * Las posiciones pares seran las llaves y las impares serán los valores. Ejemplo:<br>
     * La lista <code>array('a','val1','b',50)</code> generará el resultado <code>'{"a": "val1", "b": 50}'</code>
     * @param array $list Lista de valores
     * @return static
     */
    function &_JSON_OBJECT(array $list){
        $res=$this->fn('JSON_OBJECT', $list, true);
        return $res;
    }

    /**
     * Escapa el valor de la columna como un valor JSON
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_JSON_QUOTE($var){
        $res=$this->fn('JSON_QUOTE', array($this->n($var)), true);
        return $res;
    }

    /**
     * Revierte el escapado del valor JSON de la columna
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_JSON_UNQUOTE($var){
        $res=$this->fn('JSON_UNQUOTE', array($this->n($var)), true);
        return $res;
    }

    /**
     * Verifica si el valor de la columna es un JSON válido
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_JSON_VALID($var){
        $res=$this->fn('JSON_VALID', array($this->n($var)), true);
        return $res;
    }

    /**
     * Devuelve el tipo de dato del JSON de la columna.<br>
     * Si no es un JSON válido genera un error SQL. Si no esta seguro de que el valor sea un JSON válido, use {@see Functions::_JSON_VALID()} antes
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_JSON_TYPE($var){
        $res=$this->fn('JSON_TYPE', array($this->n($var)), true);
        return $res;
    }

    /**
     * Extrae uno o más datos del JSON de la columna usando una o varias rutas.<br>
     * Ejemplo de rutas: "$[1]", "$[0].*", "$.prop"
     * @param string $var Nombre de la columna
     * @param string|array $paths Rutas
     * @return static
     */
    function &_JSON_EXTRACT($var, $paths){
        if(!is_array($paths)) $paths=array($paths);
        $params=array_values($paths);
        array_unshift($params, $this->n($var));
        $res=$this->fn('JSON_EXTRACT', $params, true);
        return $res;
    }

    /**
     * Comprueba si el valor es NULL
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_ISNULL($var){
        $res=$this->fn_names('ISNULL', $var);
        return $res;
    }

    /**
     * Da formato de fecha
     * @param string $var Nombre de la columna
     * @return static
     */
    function &_DATE($var){
        $res=$this->fn_names('DATE', $var);
        return $res;
    }

    /**
     * Da formato de fecha personalizado
     * @param string $var Nombre de la columna
     * @param string $format Formato de fecha
     * @return static
     */
    function &_DATE_FORMAT($var, $format){
        $res=$this->fn_names('DATE_FORMAT', $var, $this->v($format));
        return $res;
    }

    /**
     * Cálcula la diferencias en días entre dos fechas.<br>
     * La fórmula es: <code>$var1-$var2</code>
     * @param string $var1 Nombre de la columna
     * @param string $var2 Nombre de la columna
     * @return static
     */
    function &_DATEDIFF($var1, $var2){
        $res=$this->fn_names('DATEDIFF', $var1, $var2);
        return $res;
    }

    /**
     * Suma un intérvalo de tiempo a una fecha/hora
     * @param string $var Nombre de la columna
     * @param string $cant Cantidad a aplicar
     * @param string $unit Unidad de intervalo temporal
     * {@link https://dev.mysql.com/doc/refman/8.0/en/expressions.html#temporal-intervals}
     * @return static
     * @see Functions::_ADD_INTERVAL()
     * @see Functions::_DATE_SUB()
     */
    function &_DATE_ADD($var, $cant, $unit){
        $res=$this->fn_names('DATE_ADD', $var, $this->sql('INTERVAL')->add_value($cant)->add($unit));
        return $res;
    }

    /**
     * Resta un intérvalo de tiempo a una fecha/hora
     * @param string $var Nombre de la columna
     * @param string $cant Cantidad a aplicar
     * @param string $unit Unidad de intervalo temporal
     * {@link https://dev.mysql.com/doc/refman/8.0/en/expressions.html#temporal-intervals}
     * @return static
     * @see Functions::_SUB_INTERVAL()
     * @see Functions::_DATE_ADD()
     */
    function &_DATE_SUB($var, $cant, $unit){
        $res=$this->fn_names('DATE_SUB', $var, $this->sql('INTERVAL')->add_value($cant)->add($unit));
        return $res;
    }

    /**
     * Suma un intérvalo de tiempo a una fecha/hora
     * @param string $cant Cantidad a aplicar
     * @param string $unit Unidad de intervalo temporal
     * {@link https://dev.mysql.com/doc/refman/8.0/en/expressions.html#temporal-intervals}
     * @return static
     * @see Functions::_DATE_ADD()
     * @see Functions::_SUB_INTERVAL()
     */
    function &_ADD_INTERVAL($cant, $unit){
        return $this->add('+')->add($this->sql('INTERVAL')->add_value($cant)->add($unit));
    }

    /**
     * Resta un intérvalo de tiempo a una fecha/hora
     * @param string $cant Cantidad a aplicar
     * @param string $unit Unidad de intervalo temporal
     * {@link https://dev.mysql.com/doc/refman/8.0/en/expressions.html#temporal-intervals}
     * @return static
     * @see Functions::_DATE_SUB()
     * @see Functions::_ADD_INTERVAL()
     */
    function &_SUB_INTERVAL($cant, $unit){
        return $this->add('-')->add($this->sql('INTERVAL')->add_value($cant)->add($unit));
    }

}

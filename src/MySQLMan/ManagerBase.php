<?php

namespace MySQLMan;

use Exception;
use PDO;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
abstract class ManagerBase{

    const ENGINE_MYISAM='MyISAM';
    const ENGINE_INNODB='InnoDB';
    const ENGINE_MEMORY='MEMORY';

    const INDEX_TYPE_FULLTEXT='FULLTEXT';
    const INDEX_TYPE_SPATIAL='SPATIAL';
    const INDEX_TYPE_UNIQUE='UNIQUE';

    const INDEX_METHOD_BTREE='BTREE';
    const INDEX_METHOD_HASH='HASH';

    const LOGIC_OP_AND='AND';
    const LOGIC_OP_OR='OR';
    const LOGIC_OP_XOR='XOR';

    protected $conn;

    protected $value=array();

    /**
     * @param PDO $conn
     * @throws Exception
     */
    public function __construct(PDO &$conn){
        if($conn->getAttribute(PDO::ATTR_DRIVER_NAME)!=='mysql'){
            throw new Exception('Invalid connection driver');
        }
        $this->conn=&$conn;
    }

    protected function quote(string $string){
        return $this->conn->quote($string);
    }

    /**
     * @return PDO
     */
    function conn(){
        return $this->conn;
    }

    private static function isThis(&$var){
        $c=static::class;
        return is_a($var, static::class);
    }

    protected function escapeAlias($var){
        if(static::isThis($var)) return $var;
        else{
            return $this->sql(("`".str_replace('`', '', $var)."`"));
        }
    }

    protected function escapeName($var){
        if(static::isThis($var)) return $var;
        else{
            if(is_array($var)){
                $var=(implode('.', $var));
            }
            if($var==='*'){
                return $this->sql($var);
            }
            if(substr_count($var, '.')>0){
                $var=explode('.', $var);
                foreach($var as $k=>&$v){
                    $var[$k]=$this->escapeName($v);
                }
                return $this->sql(implode('.', $var));
            }
            return $this->sql(("`".str_replace('`', '', $var)."`"));
        }
    }

    /**
     * @param mixed $var
     * @param bool $numeric Si es TRUE, escapa los valores numéricos sin comillas
     * @return string
     */
    protected function escapeValue($var, $numeric){
        if($var===NULL){
            return 'NULL';
        }
        elseif(is_bool($var)){
            return ($var?'TRUE':'FALSE');
        }
        elseif(is_int($var)){
            return strval($var);
        }
        elseif(is_float($var) && is_finite($var)){
            return strval($var);
        }
        elseif(is_object($var)){
            if(static::isThis($var)){
                return strval($var);
            }
            elseif(method_exists($var, '__toString')){
                return $this->quote(strval($var));
            }
            else{
                $var=get_object_vars($var);
                if(count($var)==0) return '';
                return $this->quote(implode(',', $var));
            }
        }
        elseif(is_array($var)){
            if(count($var)==0) return '';
            return $this->quote(implode(',', $var));
        }
        if($numeric && is_numeric($var)){
            return strval($var);
        }
        return $this->quote($var);
    }

    /**
     * Agrega texto a la sentencia SQL actual. NO ESCAPA LOS VALORES
     * @param mixed $var
     * @return $this
     */
    function &add($var){
        if($var===''){
            return $this;
        }
        elseif($var===NULL){
            $this->value[]='NULL';
            return $this;
        }
        elseif(is_bool($var)){
            $this->value[]=($var?'TRUE':'FALSE');
            return $this;
        }
        elseif(is_object($var)){
            if(static::isThis($var)){
                $var=strval($var);
            }
            elseif(method_exists($var, '__toString')){
                $var=strval($var);
            }
            else{
                $var=implode(',', get_object_vars($var));
            }
        }
        elseif(is_array($var)){
            $var=implode(',', $var);
        }
        if(strlen($var)>0){
            $this->value[]=strval($var);
        }
        return $this;
    }

    /**
     * Crea un nuevo objecto para la construccion del SQL
     * @param string|mixed $sql
     * @return static
     */
    function &sql($sql=''){
        $res=new static($this->conn);
        return $res->add($sql);
    }

    /**
     * Comprobar si la columna es NULL o vacía ('')
     * @param string|mixed $var Nombre de la columna
     * @return static
     */
    function &is_empty($var){
        $res=$this->_ISNULL($var)->_or($this->name($var)->cond_equal(''))->parenthesis();
        return $res;
    }

    /**
     * Comprobar si la columna (numérica o de fecha) es NULL o vacía (0)
     * @param string $var Nombre de la columna
     * @return static
     */
    function &is_empty_num($var){
        $res=$this->_ISNULL($var)->_or($this->name($var)->cond_equal(0))->parenthesis();
        return $res;
    }

    function &cond_equal($val){
        if($val===null){
            return $this->add('IS NULL');
        }
        else{
            return $this->add('=')->add_value($val);
        }
    }

    function &cond_like($val){
        return $this->add('LIKE')->add_value($val);
    }

    function &cond_not_like($val){
        return $this->add('NOT LIKE')->add_value($val);
    }

    function &cond_diff($val){
        if($val===null){
            return $this->add('IS NOT NULL');
        }
        else{
            return $this->add('<>')->add_value($val);
        }
    }

    function &cond_greater($val){
        return $this->add('>')->add_value($val);
    }

    function &cond_less($val){
        return $this->add('<')->add_value($val);
    }

    function &cond_less_equal($val){
        return $this->add('<=')->add_value($val);
    }

    function &cond_greater_equal($val){
        return $this->add('>=')->add_value($val);
    }

    function &cond_between($val_init, $val_end){
        return $this->add('BETWEEN')->add_value($val_init)->add(self::LOGIC_OP_AND)->add_value($val_end);
    }

    function &cond_inlist($val){
        return $this->add('IN')->add_parenthesis($this->values($val));
    }

    function &cond_not_inlist($val){
        return $this->add('NOT IN')->add_parenthesis($this->values($val));
    }

    function &cond_begins($val){
        return $this->cond_like($val.'%');
    }

    function &cond_ends($val){
        return $this->cond_like('%'.$val);
    }

    function &cond_contains($val){
        return $this->cond_like('%'.$val.'%');
    }

    function &cond_not_begins($val){
        return $this->cond_not_like($val.'%');
    }

    function &cond_not_ends($val){
        return $this->cond_not_like('%'.$val);
    }

    function &cond_not_contains($val){
        return $this->cond_not_like('%'.$val.'%');
    }

    /**
     * Encapsula el código SQL entre paréntesis
     * @return $this
     */
    function &parenthesis(){
        $sql='('.$this.')';
        $this->clear()->add($sql);
        return $this;
    }

    /**
     * Encapsula el código SQL entre paréntesis
     * @param mixed $sql
     * @return static
     */
    function &sql_parenthesis($sql=''){
        return $this->sql($sql)->parenthesis();
    }

    /**
     * Agrega el código SQL encapsulado entre paréntesis
     * @param mixed $sql
     * @return static
     */
    function &add_parenthesis($sql=''){
        return $this->add($this->sql($sql)->parenthesis());
    }

    /**
     * Agrega una negación al inicio de la sentencia actual.<br>
     * <b>Solo usarlo para negar código sql que devuelve un resultado booleano</b><br>
     * Si se llama como una función estática, devolverá el operador lógico NOT. Y si recibe un parámetro, negará ese valor
     * @return static
     * @see ManagerBase::_and()
     * @see ManagerBase::_or()
     * @see ManagerBase::_xor()
     * @see ManagerBase::_not()
     * @see ManagerBase::not()
     */
    function &not(){
        $this->parenthesis();
        array_unshift($this->value, 'NOT');
        return $this;
    }

    /**
     * @param $var
     * @return $this
     */
    function &_not($var){
        $res=$this->fn_names('NOT', $var);
        return $res;
    }

    function &add_value($var, $alias=null, $numeric=false){
        return $this->add($this->value($var, $alias, $numeric));
    }

    function &add_value_not_null($var, $alias=null, $numeric=false){
        return $this->add($this->value_notnull($var, $alias, $numeric));
    }

    function &add_values($vars, $numeric=false){
        return $this->add($this->values($vars, $numeric));
    }

    function &add_name($var, $alias=null){
        return $this->add($this->name($var, $alias));
    }

    function &add_names($vars){
        return $this->add($this->names($vars));
    }

    function &add_join($tabla, ?array $on, $join=null){
        if(!$join) $join='JOIN';
        $this->add($join)->add_names($tabla);
        if($on!=null){
            $this->add('ON')->add_on($on);
        }
        return $this;
    }

    function &add_inner_join($tabla, ?array $on){
        return $this->add_join($tabla, $on, 'INNER JOIN');
    }

    function &add_left_join($tabla, ?array $on){
        return $this->add_join($tabla, $on, 'LEFT JOIN');
    }

    function &add_right_join($tabla, ?array $on){
        return $this->add_join($tabla, $on, 'RIGHT JOIN');
    }

    function &add_left_outer_join($tabla, ?array $on){
        return $this->add_join($tabla, $on, 'LEFT OUTER JOIN');
    }

    function &add_right_outer_join($tabla, ?array $on){
        return $this->add_join($tabla, $on, 'RIGHT OUTER JOIN');
    }

    /**
     * @param array|mixed $where
     * @return static
     * @see ManagerBase::where_AND()
     */
    function &add_where($where){
        $this->add($this->where_AND($where));
        return $this;
    }

    function &add_orderby($orderby, $type=null){
        return $this->add($this->orderby($orderby, $type));
    }

    function &add_on($on){
        $this->add($this->on($on));
        return $this;
    }

    /**
     * Agrega el operador AND a la sentencia actual.<br> Y si se especifica, agrega una sentencia SQL
     * @param null $sql Opcional. Sentencia SQL
     * @return static
     * @see ManagerBase::_and()
     * @see ManagerBase::_or()
     * @see ManagerBase::_xor()
     * @see ManagerBase::not()
     */
    function &_and($sql=null){
        $this->add(self::LOGIC_OP_AND);
        if(!is_null($sql)){
            $this->add($sql);
        }
        return $this;
    }

    /**
     * Agrega el operador OR a la sentencia actual.<br> Y si se especifica, agrega una sentencia SQL
     * @param null $sql Opcional. Sentencia SQL
     * @return static
     * @see ManagerBase::_and()
     * @see ManagerBase::_or()
     * @see ManagerBase::_xor()
     * @see ManagerBase::not()
     */
    function &_or($sql=null){
        $this->add(self::LOGIC_OP_OR);
        if(!is_null($sql)){
            $this->add($sql);
        }
        return $this;
    }

    /**
     * Agrega el operador XOR a la sentencia actual.<br> Y si se especifica, agrega una sentencia SQL
     * @param null $sql Opcional. Sentencia SQL
     * @return static
     * @see ManagerBase::_and()
     * @see ManagerBase::_or()
     * @see ManagerBase::_xor()
     * @see ManagerBase::_not()
     * @see ManagerBase::not()
     */
    function &_xor($sql=null){
        $this->add(self::LOGIC_OP_XOR);
        if(!is_null($sql)){
            $this->add($sql);
        }
        return $this;
    }

    /**
     * Limpia la sentencia SQL actual
     * @return static
     */
    function &clear(){
        $this->value=array();
        return $this;
    }

    function toString(){
        return $this->__toString();
    }

    function __toString(){
        return implode(' ', $this->value);
    }

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en valores</b>
     * @param string $name Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return static
     */
    function &fn_values($name, ...$params){
        return $this->sql($name.$this->value($params)->parenthesis());
    }

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en nombres</b>
     * @param string $name Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return static
     */
    function &fn_names($name, ...$params){
        return $this->sql($name.$this->names(array_values($params))->parenthesis());
    }

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en nombres</b>
     * @param string $name Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return static
     */
    function &fn($name, ...$params){
        return $this->sql($name.$this->sql(implode(', ', array_values($params)))->parenthesis());
    }

    /**
     * Genera el SQL de un CASE utilizable dentro de otra sentencia SQL.<br>
     * El objetivo es obtener un valor según la condición que se cumpla.
     * @param bool|static|string $case_value Valor inicial que se comparará con las condiciones
     * @param array $when_values Lista de condiciones y los valores correspondientes.<br>
     * En este array, las llaves deben ser condiciones (sentencias SQL) y los valores se escaparán si no son sentencias SQL
     * @param null $else_value Valor que se devolverá si ninguna condición se cumple
     * @return static
     */
    function &case_when($case_value, array $when_values, $else_value=null){
        if(count($when_values)==0){
            return $this->value(null);
        }
        $res=$this->sql('CASE');
        if($case_value!==true){
            $res->add_value($case_value);
        }
        foreach($when_values as $when=>&$then){
            $res->add('WHEN')->add($when)->add('THEN')->add_value($then);
        }
        if($else_value!==null){
            $res->add('ELSE')->add_value($else_value);
        }
        $res->add('END');
        return $res;
    }

    /**
     * Alias de {@see SQLiteManPDO::value()}
     * @param $var
     * @param null|string $alias
     * @param bool $numeric
     * @return static
     */
    function &v($var, $alias=null, $numeric=false){
        return $this->value($var, $alias, $numeric);
    }

    /**
     * Alias de {@see ManagerBase::value_notnull()}
     * @param $var
     * @param null|string $alias
     * @param bool $numeric
     * @return static
     */
    function &v_nn($var, $alias=null, $numeric=false){
        return $this->value_notnull($var, $alias, $numeric);
    }

    /**
     * Alias de {@see ManagerBase::value_numeric()}
     * @param $var
     * @param null|string $alias
     * @return static
     */
    function &v_num($var, $alias=null){
        return $this->value_numeric($var, $alias);
    }

    /**
     * Alias de {@see SQLiteManPDO::name()}
     * @param $var
     * @param null|string $alias
     * @return static
     */
    function &n($var, $alias=null){
        return $this->name($var, $alias);
    }

    /**
     * Escapa un valor literal para ser utilizado en una sentencia SQL
     * @param mixed $var
     * @param null|string $alias Nombre del alias que se le asignará
     * @param bool $numeric Default=FALSE. Si es TRUE, escapa los valores numéricos sin comillas
     * @return static
     */
    public function &value($var, $alias=null, $numeric=false){
        $var=$this->escapeValue($var, $numeric);
        if(is_string($alias) && strlen($alias)>0){
            $alias=$this->escapeAlias($alias);
        }
        $res=$this->sql($var);
        if(static::isThis($alias)){
            $res->add('AS')->add($alias);
        }
        return $res;
    }

    /**
     * Escapa un valor literal para ser utilizado en una sentencia SQL.<br>
     * Si es NULL, se tratará como un string vacío
     * @param mixed $var
     * @param null|string $alias Nombre del alias que se le asignará
     * @param bool $numeric Default=FALSE. Si es TRUE, escapa los valores numéricos sin comillas
     * @return static
     */
    function &value_notnull($var, $alias=null, $numeric=false){
        return $this->value((is_null($var)?'':$var), $alias, $numeric);
    }

    /**
     * Escapa un valor literal para ser utilizado en una sentencia SQL.<br>
     * Si no es numérico, se forzará el valor a <b>0</b> (cero).<br>
     * Además, se habilita el escapado numérico.
     * @param mixed $var
     * @param null|string $alias Nombre del alias que se le asignará
     * @return static
     */
    function &value_numeric($var, $alias=null){
        return $this->value((!is_numeric($var)?0:$var), $alias, true);
    }

    /**
     * Escapa un valor hexadecimal para ser utilizado en una sentencia SQL.<br>
     * Si no es un numérico hexadecimal, se devuelve NULL.<br>
     * @param mixed $var
     * @return static|null
     */
    function hex($hex){
        if(preg_match('/^[0123456789abcdefABCDEF]+$/', $hex)) return $this->sql('0x'.$hex);
        return null;
    }

    /**
     * @param $tabla1
     * @param $tabla2
     * @param $on
     * @param null $join
     * @return static
     */
    function &join($tabla1, $tabla2, $on, $join=null){
        $res=$this->names($tabla1);
        $res->add_join($tabla2, $on, $join);
        return $res;
    }

    function &inner_join($tabla1, $tabla2, $on){
        return $this->join($tabla1, $tabla2, $on, 'INNER JOIN');
    }

    function &left_join($tabla1, $tabla2, $on){
        return $this->join($tabla1, $tabla2, $on, 'LEFT JOIN');
    }

    function &right_join($tabla1, $tabla2, $on){
        return $this->join($tabla1, $tabla2, $on, 'RIGHT JOIN');
    }

    function &left_outer_join($tabla1, $tabla2, $on){
        return $this->join($tabla1, $tabla2, $on, 'LEFT OUTER JOIN');
    }

    function &right_outer_join($tabla1, $tabla2, $on){
        return $this->join($tabla1, $tabla2, $on, 'RIGHT OUTER JOIN');
    }

    function &values($vars, $numeric=false){
        if(!is_array($vars)){
            return $this->value($vars, null, $numeric);
        }
        foreach($vars as $k=>&$var){
            $vars[$k]=&$this->value($var, $k, $numeric);
        }
        return $this->sql($vars);
    }

    function &values_notnull($vars, $numeric=false){
        if(!is_array($vars)){
            return $this->value_notnull($vars, null, $numeric);
        }
        foreach($vars as $k=>&$var){
            $vars[$k]=&$this->value_notnull($var, $k, $numeric);
        }
        return $this->sql($vars);
    }

    /**
     * Escapa un valor como un nombre de base datos, tabla o columna para ser utilizado en una sentencia SQL
     * @param $var
     * @param null|string $alias Nombre del alias que se le asignará
     * @return static
     */
    function &name($var, $alias=null){
        $var=$this->escapeName($var);
        if(is_string($alias) && strlen($alias)>0 && substr($var, strlen($var)-1)!=='*'){
            $alias=$this->escapeAlias($alias);
        }
        $res=$this->sql($var);
        if(static::isThis($alias)){
            $res->add('AS')->add($alias);
        }
        return $res;
    }

    function &names($vars){
        if(!is_array($vars)){
            return $this->name($vars);
        }
        $res=array();
        foreach($vars as $k=>&$var){
            $res[]=&$this->name($var, $k);
        }
        return $this->sql($res);
    }

    function &sets(array $var){
        if(count($var)==0){
            return $this->sql();
        }
        $sets=array();
        foreach($var as $k=>&$val){
            if(static::isThis($val) && !is_string($k)){
                $sets[]=&$val;
            }
            else{
                $sets[]=$this->name($k)->add('=')->add_value($val);
            }
        }
        if(count($sets)==1) return $sets[0];
        return $this->sql($sets);
    }

    function &orderby($orderby, $type=null){
        $res=$this->sql();
        if(static::isThis($orderby)){
            $res->add($orderby);
        }
        elseif(is_array($orderby)){
            $orders=array();
            foreach($orderby as $k=>&$v){
                if(is_string($k)){
                    $orders[]=$this->orderby($k, $v);
                }
                else{
                    $orders[]=$this->orderby($v, $type);
                }
            }
            $res->add($orders);
        }
        else{
            $res->add_name($orderby);
            if(strtoupper($type)=='ASC' || strtoupper($type)=='DESC'){
                $res->add($type);
            }
        }
        return $res;
    }

    function &where_AND($var){
        return $this->where($var, self::LOGIC_OP_AND);
    }

    function &where_OR($var){
        return $this->where($var, self::LOGIC_OP_OR);
    }

    function &where_XOR($var){
        return $this->where($var, self::LOGIC_OP_XOR);
    }

    /**
     * @param array|mixed $var
     * @param string $op
     * @return mixed|static
     * @see where_AND
     * @see where_OR
     * @see where_XOR
     */
    private function &where($var, $op){
        if(!is_array($var)){
            return $this->value($var);
        }
        if(count($var)==0){
            return $this->value(true);
        }
        $where=array();
        foreach($var as $k=>&$val){
            if(static::isThis($val) && !is_string($k)){
                $where[]=&$val;
            }
            elseif(is_string($k)){
                $where[]=$this->name($k)->cond_equal($val);
            }
            else{
                $where[]=$this->sql($val);
            }
        }
        if(count($where)==1) return $where[0];
        $sql=$this->sql(array_shift($where));
        while(($w=array_shift($where))!==null){
            $sql->add($op)->add($w);
        }
        return $sql->parenthesis();
    }

    /**
     * @param array|mixed $on
     * @return $this|static|mixed
     */
    function &on($on){
        if(!is_array($on)){
            return $this->value($on);
        }
        if(count($on)==0){
            return $this->sql(true);
        }
        $where=array();
        foreach($on as $k=>&$val){
            if(static::isThis($val) && !is_string($k)){
                $where[]=&$val;
            }
            elseif(is_string($k)){
                $where[]=$this->name($k)->cond_equal($this->name($val));
            }
            else{
                $where[]=$this->sql($val);
            }
        }
        if(count($where)==1) return $where[0];
        $sql=$this->sql(array_shift($where));
        while(($w=array_shift($where))!==null){
            $sql->add(self::LOGIC_OP_AND)->add($w);
        }
        return $sql->parenthesis();
    }

    /**
     * Genera la sentencia SQL para realizar un INSERT de una fila
     * @param $table
     * @param array $row
     * @param array|null $columns
     * @param array|null $on_duplicate_key_update Datos que se actualizan en caso de que un registro ya exista
     * @return static
     */
    function &sql_insert($table, array $row, $columns=null, $on_duplicate_key_update=null){
        $res=$this->sql('INSERT INTO')->add_name($table);
        if(!is_array($columns)) $columns=array_keys($row);
        $res->add_parenthesis($this->names($columns))->add(PHP_EOL.'VALUES');
        $vals=array();
        foreach($columns as &$col){
            $vals[]=($row[$col]);
        }
        $res->add_parenthesis($this->values($vals));
        if(is_array($on_duplicate_key_update) && count($on_duplicate_key_update)>0){
            $res->add('ON DUPLICATE KEY UPDATE')->add($this->sets($on_duplicate_key_update));
        }
        return $res;
    }

    /**
     * Genera la sentencia SQL para realizar un INSERT de varias filas
     * @param $table
     * @param array $data Matriz de datos, cada dato dentro de este array debe ser otro array que se convertirá en una fila
     * @param array|null $columns
     * @param array|null $on_duplicate_key_update Datos que se actualizan en caso de que un registro ya exista
     * @return static
     */
    function &sql_insert_multi($table, array $data, $columns=null, $on_duplicate_key_update=null){
        $res=$this->sql('INSERT INTO')->add_name($table);
        if(!is_array($columns)) $columns=array_keys($data[0]);
        $res->add_parenthesis($this->names($columns))->add(PHP_EOL.'VALUES');
        $rows=array();
        foreach($data as &$row){
            $vals=array();
            foreach($columns as &$col){
                $vals[]=($row[$col]);
            }
            $rows[]=$this->values($vals)->parenthesis();
        }
        $res->add($rows);
        if(is_array($on_duplicate_key_update) && count($on_duplicate_key_update)>0){
            $res->add('ON DUPLICATE KEY UPDATE')->add($this->sets($on_duplicate_key_update));
        }
        return $res;
    }

    /**
     * Genera la sentencia SQL para realizar un INSERT a partir de una consulta
     * @param $table
     * @param array $columns
     * @param static|string $select
     * @param array|null $on_duplicate_key_update Datos que se actualizan en caso de que un registro ya exista
     * @return static
     */
    function &sql_insert_select($table, array $columns, $select, $on_duplicate_key_update=null){
        $res=$this->sql('INSERT INTO')->add_name($table);
        $res->add_parenthesis($this->names($columns));
        $res->add($select);
        if(is_array($on_duplicate_key_update) && count($on_duplicate_key_update)>0){
            $res->add('ON DUPLICATE KEY UPDATE')->add($this->sets($on_duplicate_key_update));
        }
        return $res;
    }

    /**
     * Genera la sentencia SQL para realizar un UPDATE
     * @param $table
     * @param array $sets
     * @param null $where
     * @param null $orderby
     * @param null $limit
     * @param null $offset
     * @return static
     */
    function &sql_update($table, array $sets, $where=null, $orderby=null, $limit=null, $offset=null){
        $res=$this->sql('UPDATE')->add_names($table);
        $res->add('SET')->add($this->sets($sets));
        if($where!==null){
            $res->add('WHERE')->add_where($where);
        }
        if(is_array($orderby) && !count($orderby)) $orderby=null;
        if($orderby!==null){
            $res->add('ORDER BY')->add_orderby($orderby);
        }
        if(is_numeric($limit)){
            $limit=(int)$limit;
            $res->add('LIMIT');
            if(is_numeric($offset)){
                $offset=(int)$offset;
                $res->add($offset.','.$limit);
            }
            else{
                $res->add($limit);
            }
        }
        return $res;
    }

    function &sql_delete($table, $where=null, $orderby=null, $limit=null){
        $res=$this->sql('DELETE FROM')->add_names($table);
        if($where!==null){
            $res->add('WHERE')->add_where($where);
        }
        if(is_array($orderby) && !count($orderby)) $orderby=null;
        if($orderby!==null){
            $res->add('ORDER BY')->add_orderby($orderby);
        }
        if(is_numeric($limit)){
            $limit=(int)$limit;
            $res->add('LIMIT');
            $res->add($limit);
        }
        return $res;
    }

    function &sql_delete_from($tables, $from, $where=null){
        $res=$this->sql('DELETE')->add_names($tables);
        $res->add('FROM')->add_names($from);
        if($where!==null){
            $res->add('WHERE')->add_where($where);
        }
        return $res;
    }

    /**
     * Agrega un bloqueo en modo compartido a una consulta dentro de una transaccion.<br>
     * IMPORTANTE: Solo aplicable al final de un {@see ManagerBase::sql_select()}.<br>
     * {@see ManagerBase::LOCK_IN_SHARE_MODE()} y {@see ManagerBase::FOR_UPDATE()} son mutuamente excluyentes.
     * @link https://dev.mysql.com/doc/refman/5.5/en/innodb-locking-reads.html
     * @return static
     */
    function &LOCK_IN_SHARE_MODE(){
        return $this->add('LOCK IN SHARE MODE');
    }

    /**
     * Agrega un bloqueo exclusivo a una consulta dentro de una transaccion.<br>
     * IMPORTANTE: Solo aplicable al final de un {@see ManagerBase::sql_select()}.<br>
     * {@see ManagerBase::LOCK_IN_SHARE_MODE()} y {@see ManagerBase::FOR_UPDATE()} son mutuamente excluyentes.
     * @link https://dev.mysql.com/doc/refman/5.5/en/innodb-locking-reads.html
     * @return $this
     */
    function &FOR_UPDATE(){
        return $this->add('FOR UPDATE');
    }

    /**
     * Crea una sentencia SELECT SQL
     * @param $select
     * @param null $from
     * @param null $where
     * @param null $groupby
     * @param null $having
     * @param null $orderby
     * @param null $limit
     * @param null $offset
     * @return static
     * @see ManagerBase::LOCK_IN_SHARE_MODE()
     * @see ManagerBase::FOR_UPDATE()
     */
    function &sql_select($select, $from=null, $where=null, $groupby=null, $having=null, $orderby=null, $limit=null, $offset=null){
        $res=$this->sql('SELECT')->add_names($select);
        if($from!==null){
            $res->add('FROM')->add_names($from);
        }
        if($where!==null){
            $res->add('WHERE')->add_where($where);
        }
        if(is_array($groupby) && !count($groupby)) $groupby=null;
        if($groupby!==null){
            $res->add('GROUP BY')->add_names($groupby);
        }
        if(is_array($having) && !count($having)) $having=null;
        if($having!==null){
            $res->add('HAVING')->add_where($having);
        }
        if(is_array($orderby) && !count($orderby)) $orderby=null;
        if($orderby!==null){
            $res->add('ORDER BY')->add_orderby($orderby);
        }
        if(is_numeric($limit)){
            $limit=(int)$limit;
            $res->add('LIMIT');
            if(is_numeric($offset)){
                $offset=(int)$offset;
                $res->add($offset.','.$limit);
            }
            else{
                $res->add($limit);
            }
        }
        return $res;
    }

    /**
     * Genera el SQL de la llamada a un procedimiento almacenado
     * @param string $name Nombre del procedimiento almacenado
     * @param array $params Lista de parametros del procedimiento
     * @param bool $param_values Default: TRUE.<br>
     * Si es true, los parámetros se convertirán en valores. De lo contrario se convertirán en nombres
     * @return static
     */
    function &sql_procedure($name, ...$params){
        return $this->sql('CALL')->add($this->fn_values($name, ...$params));
    }

    /**
     * Genera la sentencia SQL para realizar un DROP TEMPORARY TABLE IF EXISTS
     * @param string $table_name Nombre de la tabla temporal
     * @return static
     */
    function &drop_temp_table($table_name){
        $res=$this->sql('DROP TEMPORARY TABLE IF EXISTS')->add_name($table_name);
        return $res;
    }

    /**
     * Genera la sentencia SQL para realizar un CREATE TEMPORARY TABLE
     * @param string $table_name Nombre de la tabla temporal
     * @param string $sql_select Consulta SQL
     * @param null|string $engine Default: <b>NULL</b><br>
     * <u>Para optimizar la creación de las tablas temporales, se recomienda utilizar el ENGINE=MyISAM</u><br>
     * Los valores permitidos son:
     * <ul>
     * <li>{@see ManagerBase::ENGINE_MYISAM}</li>
     * <li>{@see ManagerBase::ENGINE_INNODB}</li>
     * <li>{@see ManagerBase::ENGINE_MEMORY}</li>
     * <li><b>NULL</b></li>
     * </ul>
     * @return static
     */
    function &create_temp_table($table_name, $sql_select, $engine=null){
        $res=$this->sql('CREATE TEMPORARY TABLE')->add_name($table_name);
        if(in_array($engine, array(
            self::ENGINE_MYISAM,
            self::ENGINE_INNODB,
            self::ENGINE_MEMORY
        ))){
            $res->add('ENGINE='.$engine);
        }
        $res->add($sql_select);
        return $res;
    }

    /**
     * Genera la sentencia SQL para realizar un CREATE TEMPORARY TABLE
     * @param string $table_name Nombre de la tabla a la que se agregará el índice
     * @param string $index_name Nombre del índice que se creará
     * @param string|array $columns Nombre o listado de nombres de columnas que se agregaran al índice
     * @param null|string $index_type Default: <b>NULL</b><br>
     * Los valores permitidos son:
     * <ul>
     * <li>{@see ManagerBase::INDEX_TYPE_FULLTEXT}. Disponible solamente para Engine=INNODB ó ENGINE=MYISAM</li>
     * <li>{@see ManagerBase::INDEX_TYPE_SPATIAL}. Disponible solamente para Engine=INNODB ó ENGINE=MYISAM</li>
     * <li>{@see ManagerBase::INDEX_TYPE_UNIQUE}</li>
     * <li><b>NULL</b> Si se recibe este valor, no se le agrega el tipo de INDICE [UNIQUE | FULLTEXT | SPATIAL]</li>
     * </ul>
     * @param null|string $index_method Default: <b>NULL</b><br>
     * Los valores permitidos son:
     * <ul>
     * <li>{@see ManagerBase::INDEX_METHOD_BTREE}</li>
     * <li>{@see ManagerBase::INDEX_METHOD_HASH}</li>
     * <li>{@see NULL} Si se recibe este valor, no se le agrega USING {BTREE | HASH}  </li>
     * </ul>
     * @return static
     */
    function &create_index($table_name, $index_name, $columns, $index_type=null, $index_method=null){
        $res=$this->sql('CREATE');
        if(in_array($index_type, array(
            self::INDEX_TYPE_FULLTEXT,
            self::INDEX_TYPE_SPATIAL,
            self::INDEX_TYPE_UNIQUE
        ))){
            $res->add($index_type);
        }
        $res->add('INDEX')->add_name($index_name);
        $res->add('ON')->add_name($table_name);
        $res->add_parenthesis($this->names($columns));
        if(in_array($index_method, array(
            self::INDEX_METHOD_BTREE,
            self::INDEX_METHOD_HASH
        ))){
            $res->add('USING')->add($index_method);
        }
        return $res;
    }

}
//TODO Cambiar el comportamiento y estructura basado en las clases de SQLiteMan
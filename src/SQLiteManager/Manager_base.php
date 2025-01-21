<?php

namespace SQLiteManager;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
trait Manager_base{

    /**
     * @param string|SQL $sql
     * @return SQL
     */
    public function sql($sql): SQL{
        return new SQL($sql, $this);
    }

    /**
     * Escapa un nombre de tabla o columna
     * @param string $name
     * @return string
     */
    public static function quoteName(string $name): string{
        return '"'.str_replace('"', '""', $name).'"';
    }

    /**
     * Escapa varios nombres de tabla o columna y lo devuelve concatenados por puntos
     * @param string|null ...$name
     * @return string|null
     */
    public static function quoteNames(?string ...$name): ?string{
        $r=[];
        foreach($name AS $n){
            if($n!==null) $r[]=self::quoteName($n);
        }
        if(count($r)===0) return null;
        $r=implode('.', $r);
        return $r;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function quoteNameParts(string $name): string{
        if($name==='*') return $name;
        if(preg_match('/^("(?:[^"]|"")+"|`(?:[^`]|``)+`|\'(?:[^\']|\'\')+\'|\[(?:[^\]])+\]|[^"`\'\[\.][^\.]*)(?:\.(.*))?$/', $name, $m)){
            $r=strval($m[1]);
            if($r[0]=='"'){}
            elseif($r[0]=='[') $r=self::quoteName(substr($r, 1, -1));
            elseif($r[0]=='`') $r=self::quoteName(str_replace('``', '`', substr($r, 1, -1)));
            elseif($r[0]=="'") $r=self::quoteName(str_replace("''", "'", substr($r, 1, -1)));
            else $r=self::quoteName($r);
            if(isset($m[2])) $r.='.'.self::quoteNameParts($m[2]);
            return $r;
        }
        return self::quoteName($name);
    }

    /**
     * Escapa el nombre o nombres indicados
     * @param string|SQL $name
     * @param string|null $alias
     * @return SQL
     */
    public function name($name, ?string $alias=null): SQL{
        if(is_a($name, SQL::class)) $sql=$this->sql($name);
        else $sql=$this->sql(static::quoteNameParts($name));
        if($alias!==null) $sql->_as($alias);
        return $sql;
    }

    /**
     * @param string|SQL $name
     * @param string|null $alias
     * @param false|string|null $indexedBy
     * @return SQL
     * @link https://www.sqlite.org/syntax/qualified-table-name.html
     */
    public function qualified_name($name, ?string $alias=null, $indexedBy=null): SQL{
        $res=$this->name($name, $alias);
        if(is_string($indexedBy)) $res->_("INDEXED BY")->_name($indexedBy);
        elseif($indexedBy===false) $res->_("NOT INDEXED");
        return $res;
    }

    /**
     * Escapa las lista de nombres o expresiones separados por comas por medio de {@see \SQLiteMan::name()}
     * @param array|string|SQL $names Lista de nombres o expresiones. Si el indice es un string, se usa como alias
     * @param bool $alias
     * @return SQL
     * @link https://www.sqlite.org/syntax/expr.html
     * @link https://www.sqlite.org/syntax/result-column.html
     */
    public function names($names, bool $alias=true): SQL{
        if(!is_array($names)) return $this->name($names);
        $sql=null;
        foreach($names as $as=>$name){
            if($sql===null){
                $sql=$this->name($name);
            }
            else{
                $sql->_comma_name($name);
            }
            if($alias && is_string($as)) $sql->_as($as);
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    /**
     * Escapa valores solo si son null|bool|int|float
     * @param null|scalar $value
     * @return SQL|null
     */
    private function altEscape($value): ?SQL{
        if($value===null) return $this->sql('NULL');
        if(is_bool($value)) return $this->sql($value?'1':'0');
        if(is_int($value) || is_float($value)) return $this->sql(strval($value));
        return null;
    }

    /**
     * Escapa valores. Escapa el texto como un valor hexadecimal si contiene el caracter NULL ("\0")
     * @param null|scalar|SQL $value
     * @return SQL
     */
    public function value($value): SQL{
        if(is_a($value, SQL::class)) return $this->sql($value);
        return $this->altEscape($value)??$this->sql($this->quoteVal($value));
    }

    /**
     * Escapa valores. Elimina el caracter NULL ("\0") del texto
     * @param null|scalar|SQL $value
     * @return SQL
     */
    public function value_text($value): SQL{
        if(is_a($value, SQL::class)) return $this->sql($value);
        return $this->altEscape($value)??$this->sql($this->quoteVal(str_replace("\0", '', $value)));
    }

    /**
     * Escapa valores. Escapa el texto como un valor hexadecimal
     * @param null|scalar|SQL $value
     * @return SQL
     */
    public function value_hex($value): SQL{
        if(is_a($value, SQL::class)) return $this->sql($value);
        return $this->altEscape($value)??$this->sql($this->quoteHex(strval($value)));
    }

    /**
     * @param null|scalar|array|SQL $values
     * @return SQL
     */
    public function values($values): SQL{
        if(!is_array($values)) return $this->value($values);
        $sql=null;
        foreach($values as $val){
            if($sql===null){
                $sql=$this->value($val);
            }
            else{
                $sql->_comma_value($val);
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    public function values_text($values): SQL{
        if(!is_array($values)) return $this->value_text($values);
        $sql=null;
        foreach($values as $val){
            if($sql===null){
                $sql=$this->value_text($val);
            }
            else{
                $sql->_comma($this->value_text($val));
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    public function values_hex($values): SQL{
        if(!is_array($values)) return $this->value_hex($values);
        $sql=null;
        foreach($values as $val){
            if($sql===null){
                $sql=$this->value_hex($val);
            }
            else{
                $sql->_comma($this->value_hex($val));
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    public function pragma_list(){
        return $this->query('PRAGMA pragma_list');
    }

    public function database_list(){
        return $this->query('PRAGMA database_list');
    }

    public function function_list(){
        return $this->query('PRAGMA function_list');
    }

    public function shrink_memory(){
        $sql='PRAGMA shrink_memory';
        return $this->query($sql);
    }

    public function query_only(?bool $val=null){
        $sql='PRAGMA query_only';
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    public function table_info(string $table=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'table_info').'('.self::quoteName($table).')';
        return $this->query($sql);
    }

    public function table_xinfo(string $table=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'table_xinfo').'('.self::quoteName($table).')';
        return $this->query($sql);
    }

    public function index_info(string $index=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'index_info').'('.self::quoteName($index).')';
        return $this->query($sql);
    }

    public function index_xinfo(string $index=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'index_xinfo').'('.self::quoteName($index).')';
        return $this->query($sql);
    }

    /**
     * @param string|null $val 0 | OFF | 1 | NORMAL | 2 | FULL | 3 | EXTRA
     * @param string|null $schema
     * @return Result|null
     */
    public function synchronous(?string $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'synchronous');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    /**
     * @param string|null $val DELETE | TRUNCATE | PERSIST | MEMORY | WAL | OFF
     * @param string|null $schema
     * @return Result|null
     */
    public function journal_mode(?string $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'journal_mode');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    public function journal_size_limit(?int $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'journal_size_limit');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    /**
     * @param string|null $val NORMAL | EXCLUSIVE
     * @param string|null $schema
     * @return Result|null
     */
    public function locking_mode(?string $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'locking_mode');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    /**
     * @param int|null $val
     * @return Result|null
     */
    public function wal_autocheckpoint(?int $val=null){
        $sql='PRAGMA wal_autocheckpoint';
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    /**
     * @param string|null $val PASSIVE | FULL | RESTART | TRUNCATE
     * @param string|null $schema
     * @return Result|null
     */
    public function wal_checkpoint(?string $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'wal_checkpoint');
        if($val!==null) $sql.='('.$this->value($val).')';
        return $this->query($sql);
    }

    public function user_version(?int $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'user_version');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    public function application_id(?int $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'application_id');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    public function page_size(?int $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'page_size');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    public function page_count(?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'page_count');
        return $this->query($sql);
    }

    public function freelist_count(?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'freelist_count');
        return $this->query($sql);
    }

    /**
     * @param string|null $val 0 | NONE | 1 | FULL | 2 | INCREMENTAL
     * @param string|null $schema
     * @return Result|null
     */
    public function auto_vacuum(?string $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'auto_vacuum');
        if($val!==null) $sql.='='.$this->value($val);
        return $this->query($sql);
    }

    /**
     * @param int|null $val
     * @param string|null $schema
     * @return Result|null
     */
    public function incremental_vacuum(?int $val=null, ?string $schema=null){
        $sql='PRAGMA '.self::quoteNames($schema, 'incremental_vacuum');
        if($val!==null) $sql.='('.$this->value($val).')';
        return $this->query($sql);
    }

    public function vacuum(?string $schema=null, ?string $toFile=null){
        $sql='VACUUM '.$this->quoteNames($schema);
        if(is_string($toFile)) $sql.=' INTO '.$this->value($toFile);
        return $this->query($sql);
    }

    public function attach_database(string $file, string $as){
        $sql=$this->sql('ATTACH DATABASE :file')->_as($as);
        return $this->query($sql, [':file'=>$file]);
    }

    public function detach_database(string $as){
        $sql='DETACH DATABASE '.$this->name($as);
        return $this->query($sql);
    }

    /**
     * @param string $type
     * @return string Uno de los valores de {@see SQLiteMan::TYPES}
     */
    public static function typeColumnAffinity(string $type){
        if($type==='') return self::TYPE_BLOB;
        $type=strtoupper($type);
        if(in_array($type, static::TYPES)) return $type;
        foreach(self::TYPES_AFFINITY as $s=>$t){
            if(strpos($type, $s)!==false) return $t;
        }
        return self::TYPE_NUMERIC;
    }

    /**
     * Genera la definición de una columna.
     *
     * Ver {@link https://www.sqlite.org/datatype3.html#determination_of_column_affinity}
     * @param string $colName
     * @param array $colDef Propiedades esperadas:
     * <ul>
     * <li>type: Tipo de dato de la columna. Lista de posibles valores: {@see \SQLiteMan::TYPES}</li>
     * <li>notnull: {bool} Indica si la columna no admite nulos</li>
     * <li>unique: {bool} Indica si los valores de la columna son únicos</li>
     * <li>default/defaultExpr: El valor o expresión por defecto</li>
     * <li>stored/virtual: {string} Expresión que genera el valor de la columna</li>
     * <li>pk: {int|bool} Indica si la columna es parte del primary key. Si se compone de varias columnas, debe indicar la posición dentro del primary key [1|2|3|...]</li>
     * <li>ai: {bool} Indica si la columna es autoincremental</li>
     * </ul>
     * @return string
     */
    public function columnDef(string $colName, array $colDef): string{
        $def=static::quoteName($colName);
        if(isset($colDef['type'])) $def.=' '.$this->value($colDef['type']);
        if(($colDef['notnull'] ?? false)) $def.=' NOT NULL';
        if(($colDef['unique'] ?? false)) $def.=' UNIQUE';

        if(isset($colDef['default'])) $def.=' DEFAULT '.$this->value($colDef['default']);
        elseif(isset($colDef['defaultExpr'])) $def.=' DEFAULT ('.$colDef['defaultExpr'].')';

        if(isset($colDef['stored'])) $def.=' AS ('.$colDef['stored'].') STORED';
        elseif(isset($colDef['virtual'])) $def.=' AS ('.$colDef['virtual'].') VIRTUAL';

        if(($colDef['pk'] ?? false) && ($colDef['ai'] ?? false)) $def.=' PRIMARY KEY AUTOINCREMENT';
        return $def;
    }

    /**
     * # Esta función es experimental:
     * ## Su comportamiento y resultados pueden cambiar en futuras versiones
     *
     * Estos son los cambios detectados
     * ```PHP
     * [
     *   "add"=>[], // Lista de columnas por agregar (faltan en la tabla)
     *   "drop"=>[], // Lista de columnas por eliminar (opcional)
     *   "change"=>[], // Lista de columnas cuya definición no coindice por completo con lo indicado
     * ]
     * ```
     * @param $table
     * @param array $columns
     * @return array|false
     */
    public function detectColumnDiff($table, array $columns){
        $defs=$this->table_info($table);
        if(!$defs) return false;
        $drop=[];
        $change=[];
        foreach($defs as $col){
            if(isset($columns[$col['name']])){
                if(strtoupper($columns[$col['name']]['type'] ?? self::TYPE_BLOB)!=strtoupper($col['type'] ?? self::TYPE_BLOB)){
                    $change[$col['name']]=$col;
                }
                elseif(($columns[$col['name']]['notnull'] ?? 0)!=$col['notnull']){
                    $change[$col['name']]=$col;
                }
                elseif(($columns[$col['name']]['pk'] ?? 0)!=$col['pk']){
                    $change[$col['name']]=$col;
                }
                elseif($this->value($columns[$col['name']]['default'] ?? null)!=($col['dflt_value'] ?? 'NULL')){
                    $change[$col['name']]=$col;
                }
            }
            else{
                $drop[$col['name']]=$col;
            }
            unset($columns[$col['name']]);
        }
        $res=[
            'add'=>&$columns,
            'drop'=>&$drop,
            'change'=>$change,
        ];
        return $res;
    }

}

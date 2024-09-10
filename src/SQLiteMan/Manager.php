<?php

namespace SQLiteMan;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
abstract class Manager{
    use Manager_adds;

    const SCHEMA_MAIN='main';
    const SCHEMA_TEMP='temp';
    const TYPE_INTEGER='INTEGER';
    const TYPE_REAL='REAL';
    const TYPE_TEXT='TEXT';
    const TYPE_BLOB='BLOB';
    const TYPE_NUMERIC='NUMERIC';
    const TYPES=[
        self::TYPE_INTEGER,
        self::TYPE_REAL,
        self::TYPE_TEXT,
        self::TYPE_BLOB,
        self::TYPE_NUMERIC,
    ];

    /**
     * Ver {@link https://www.sqlite.org/datatype3.html}
     *
     * Si la columna no tiene un tipo, su afinidad es {@see Manager::TYPE_BLOB}
     *
     * Si el tipo no coincide con la lista {@see Manager::TYPES},
     * la afinidad de la columna se asocia al primer valor cuyo índice sea parte del tipo de la columna.
     *
     * Si no coincide con ninguno de la lista, su afinidad es {@see Manager::TYPE_NUMERIC}
     *
     * Ejemplos:
     * - "FLOATING POINT" tendrá afinidad con {@see Manager::TYPE_INTEGER} ya que contiene "INT", y está antes que con "FLOA" en la lista
     * - "DECIMAL", "DATE", "BOOL" y "STRING" tendrán afinidad con {@see Manager::TYPE_NUMERIC} ya que no contienen ningun índice de la lista
     * @see Manager::typeColumnAffinity()
     */
    const TYPES_AFFINITY=[
        'INT'=>self::TYPE_INTEGER,
        'CHAR'=>self::TYPE_TEXT,
        'CLOB'=>self::TYPE_TEXT,
        'TEXT'=>self::TYPE_TEXT,
        'BLOB'=>self::TYPE_BLOB,
        'REAL'=>self::TYPE_REAL,
        'FLOA'=>self::TYPE_REAL,
        'DOUB'=>self::TYPE_REAL,
    ];

    const JOIN_INNER='INNER';
    const JOIN_LEFT='LEFT';
    const JOIN_LEFT_OUTER='LEFT OUTER';
    const JOIN_RIGTH='RIGHT';
    const JOIN_RIGTH_OUTER='RIGHT OUTER';
    const JOIN_FULL='FULL';
    const JOIN_FULL_OUTER='FULL OUTER';
    const JOINS=[
        self::JOIN_INNER,
        self::JOIN_LEFT,
        self::JOIN_LEFT_OUTER,
        self::JOIN_RIGTH,
        self::JOIN_RIGTH_OUTER,
        self::JOIN_FULL,
        self::JOIN_FULL_OUTER,
    ];

    const OR_ABORT='ABORT';
    const OR_FAIL='FAIL';
    const OR_IGNORE='IGNORE';
    const OR_REPLACE='REPLACE';
    const OR_ROLLBACK='ROLLBACK';

    abstract public function version();

    abstract public function timeout(int $sec): bool;

    abstract protected function quoteVal(string $value): string;

    abstract protected function quoteHex(string $value): string;

	/**
	 * @param string $sql
	 * @param array|null $params
	 * @return Result|null
	 */
    abstract public function query(string $sql, ?array $params=null): ?Result;

    abstract public function lastInsertID();

    abstract public function throwExceptions(bool $enable);

    abstract public function lastError(): ?Exception;

    /**
     * @param string|SQL|SelfEscape $sql
     * @return SQL
     */
    public function sql($sql): SQL{
        if(is_a($sql, SelfEscape::class)) return $sql->toSQLite($this);
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

    const REGEXP_ESCAPED_NAMEPART='/^("(?:[^"]|"")+"|`(?:[^`]|``)+`|\'(?:[^\']|\'\')+\'|\[(?:[^\]])+\]|[^"`\'\[\.][^\.]*)(?:\.(.*))?$/';

    /**
     * @param string $name
     * @return string
     */
    public static function quoteNameParts(string $name): string{
        if($name==='*') return $name;
        if(preg_match(static::REGEXP_ESCAPED_NAMEPART, $name, $m)){
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
     * @param string|SQL|SelfEscape $name
     * @param string|null $alias
     * @return SQL
     */
    public function name($name, ?string $alias=null): SQL{
        if(is_a($name, SQL::class)) $sql=$this->sql($name);
        elseif(is_a($name, SelfEscape::class)) $sql=$name->toSQLite($this);
        else $sql=$this->sql(static::quoteNameParts($name));
        if($alias!==null) $sql->_as($alias);
        return $sql;
    }

    /**
     * @param string|SQL|SelfEscape $name
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
     * Escapa las lista de nombres o expresiones separados por comas por medio de {@see Manager::name()}
     * @param array|string|SQL|SelfEscape $names Lista de nombres o expresiones. Si el indice es un string, se usa como alias
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
     * @param null|scalar|SelfEscape $value
     * @return SQL|null
     */
    private function altEscape($value): ?SQL{
        if($value===null) return $this->sql('NULL');
        if(is_bool($value)) return $this->sql($value?'1':'0');
        if(is_int($value) || is_float($value)) return $this->sql(strval($value));
        if(is_a($value, SelfEscape::class)) return $value->toSQLite($this);
        return null;
    }

    /**
     * Escapa valores. Escapa el texto como un valor hexadecimal si contiene el caracter NULL ("\0")
     * @param null|scalar|SQL|SelfEscape $value
     * @return SQL
     */
    public function value($value): SQL{
        if(is_a($value, SQL::class)) return $this->sql($value);
        return $this->altEscape($value)??$this->sql($this->quoteVal($value));
    }

    /**
     * Escapa valores. Elimina el caracter NULL ("\0") del texto
     * @param null|scalar|SQL|SelfEscape $value
     * @return SQL
     */
    public function value_text($value): SQL{
        if(is_a($value, SQL::class)) return $this->sql($value);
        return $this->altEscape($value)??$this->sql($this->quoteVal(str_replace("\0", '', $value)));
    }

    /**
     * Escapa valores. Escapa el texto como un valor hexadecimal
     * @param null|scalar|SQL|SelfEscape $value
     * @return SQL
     */
    public function value_hex($value): SQL{
        if(is_a($value, SQL::class)) return $this->sql($value);
        return $this->altEscape($value)??$this->sql($this->quoteHex(strval($value)));
    }

    /**
     * @param null|scalar|array|SQL|SelfEscape $values
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

    public function vacuum(?string $schema=null, ?string $toFile=null){
        $sql='VACUUM';
        if(is_string($schema)) $sql.=' '.$this->quoteName($schema);
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
     * @return string Uno de los valores de {@see Manager::TYPES}
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
     * <li>type: Tipo de dato de la columna. Lista de posibles valores: {@see Manager::TYPES}</li>
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

    public function schemaList(){
        return $this->query('PRAGMA database_list');
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
        $defs=$this->query($this->sql_tableInfo($table));
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
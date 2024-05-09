<?php

namespace SQLiteMan;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
abstract class ManagerBase{

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
     * Si la columna no tiene un tipo, su afinidad es {@see ManagerBase::TYPE_BLOB}
     *
     * Si el tipo no coincide con la lista {@see ManagerBase::TYPES},
     * la afinidad de la columna se asocia al primer valor cuyo índice sea parte del tipo de la columna.
     *
     * Si no coincide con ninguno de la lista, su afinidad es {@see ManagerBase::TYPE_NUMERIC}
     *
     * Ejemplos:
     * - "FLOATING POINT" tendrá afinidad con {@see ManagerBase::TYPE_INTEGER} ya que contiene "INT", y está antes que con "FLOA" en la lista
     * - "DECIMAL", "DATE", "BOOL" y "STRING" tendrán afinidad con {@see ManagerBase::TYPE_NUMERIC} ya que no contienen ningun índice de la lista
     * @see ManagerBase::typeColumnAffinity()
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

    const OR_ABORT='ABORT';
    const OR_FAIL='FAIL';
    const OR_IGNORE='IGNORE';
    const OR_REPLACE='REPLACE';
    const OR_ROLLBACK='ROLLBACK';
    static $INSERT_UPDATE_OR_LIST=[
        self::OR_ABORT,
        self::OR_FAIL,
        self::OR_IGNORE,
        self::OR_REPLACE,
        self::OR_ROLLBACK,
    ];

    private static $tbMaster='`sqlite_master`';

    abstract public function version();

    abstract public function timeout(int $sec): bool;

    abstract protected function quoteVal(string $value): string;

    abstract protected function quoteBin(string $value): string;

    /**
     * @param string $sql
     * @param array|null $params
     * @return bool|int|null
     */
    abstract public function exec(string $sql, ?array $params=null);

    abstract public function query(string $sql, ?array $params=null);

    abstract public function lastInsertID();

    abstract public function throwExceptions(bool $enable);

    abstract public function lastError(): ?Exception;

    public function esc(Data $value): string{
        if(is_a($value, SQL::class)){
            return strval($value);
        }
        elseif(is_a($value, Binary::class)){
            return strval($this->value_bin($value->data()));
        }
        elseif(is_a($value, Value::class)){
            return strval($this->value($value->data()));
        }
        elseif(is_a($value, Name::class)){
            return strval($this->name($value->data()));
        }
        return "";
    }

    public function escSQL(Data $value): SQL{
        if(is_a($value, SQL::class)){
            return $value;
        }
        elseif(is_a($value, Binary::class)){
            return $this->value_bin($value->data());
        }
        elseif(is_a($value, Value::class)){
            return $this->value($value->data());
        }
        elseif(is_a($value, Name::class)){
            return $this->name($value->data());
        }
        return $this->sql('');
    }

    /**
     * @param Data ...$value
     * @return SQL
     */
    public function escList(Data ...$value): SQL{
        $sql=implode(',', array_map([$this, 'esc'], $value));
        return $this->sql($sql);
    }

    /**
     * @param string|Data $sql
     * @return SQL
     */
    public function sql($sql): SQL{
        if(is_a($sql, Data::class)) return $this->escSQL($sql);
        return Data::sql($sql);
    }

    /**
     * Escapa un nombre de tabla o columna
     * @param string $name
     * @return string
     */
    public static function quoteName(string $name): string{
        return '`'.str_replace('`', '``', $name).'`';
    }

    const REGEXP_ESCAPED_NAMEPART='/^(`(?:[^`]|``)+`)(?:\.(.*))?$/';
    const REGEXP_UNESCAPED_NAMEPART='/^([^`\.][^\.]*)(?:\.(.*))?$/';

    /**
     * Escapa el nombre o nombres indicados
     * @param string|Data $name
     * @return SQL
     */
    public function name($name): SQL{
        if(is_a($name, Data::class)) return $this->escSQL($name);
        return $this->sql($this->name_($name));
    }

    /**
     * @param string $name
     * @param string|null $alias
     * @param false|string|null $indexedBy
     * @return SQL
     */
    public function qualified_name($name, ?string $alias=null, $indexedBy=null): SQL{
        if(is_a($name, Data::class)) return $this->escSQL($name);
        $sql=$this->name_($name);
        if($alias!==null) $sql.=" AS ".$this->name_($alias);
        if(is_string($indexedBy)) $sql.=" INDEXED BY ".$this->name_($indexedBy);
        elseif($indexedBy===false) $sql.=" NOT INDEXED";
        return $this->sql($sql);
    }

    /**
     * @param string $name
     * @return string
     */
    private function name_(string $name): string{
        if($name==='*') return $name;
        if(preg_match(static::REGEXP_ESCAPED_NAMEPART, $name, $m)){
            $r=strval($m[1]);
            if(isset($m[2])) $r.='.'.self::name_($m[2]);
            return $r;
        }
        elseif(preg_match(static::REGEXP_UNESCAPED_NAMEPART, $name, $m)){
            $r=self::quoteName($m[1]);
            if(isset($m[2])) $r.='.'.self::name_($m[2]);
            return $r;
        }
        return self::quoteName($name);
    }

    /**
     * Escapa el nombre o nombres indicados, con excepciones
     *
     * Se hace una excepción si se detecta que el valor string recibido ya esta escapado como nombre o es un parametro que inicia con `:` (parámetros), o es un texto entre paréntesis `()`
     *
     * ## CUIDADO: Este método no es seguro para datos desconocidos.
     * ###Si el origen del nombre es externo al código (como variables POST, GET, HEADERS, etc), debe utilizar primero la función {@see \SQLiteMan\ManagerBase::name()} sobre esos valores
     * @param string|array|Data $name
     * @return SQL
     */
    public function nameVar($name): SQL{
        if(is_string($name) && preg_match('/^\:\w+$/', $name)){
            return $this->sql($name);
        }
        else{
            return $this->name($name);
        }
    }

    /**
     * Escapa las lista de nombres o expresiones separados por comas por medio de {@see Esc::escapeName()}
     * @param array $list Lista de nombres o expresiones. Si el indice es un string, se usa como alias
     * @param bool $alias
     * @return SQL
     * @link https://www.sqlite.org/syntax/expr.html
     * @link https://www.sqlite.org/syntax/result-column.html
     */
    public function nameList(array $list, bool $alias=true): SQL{
        $sql='';
        $first=true;
        foreach($list as $as=>$name){
            if($first) $first=false;
            else $sql.=", ";
            $sql.=static::name($name).($alias && is_string($as)?' AS '.static::quoteName($as):'');
        }
        return $this->sql($sql);
    }

    /**
     * Escapa las lista de nombres o expresiones separados por comas por medio de {@see Esc::escapeNameVar()}
     * @param array $list Lista de nombres o expresiones. Si el indice es un string, se usa como alias
     * @param bool $alias
     * @return SQL
     * @link https://www.sqlite.org/syntax/expr.html
     * @link https://www.sqlite.org/syntax/result-column.html
     */
    public function nameVarList(array $list, bool $alias=true): SQL{
        $sql='';
        $first=true;
        foreach($list as $as=>$name){
            if($first) $first=false;
            else $sql.=", ";
            $sql.=$this->nameVar($name).($alias && is_string($as)?' AS '.static::quoteName($as):'');
        }
        return $this->sql($sql);
    }

    /**
     * @param null|scalar|Data $value
     * @return SQL
     */
    public function value($value): SQL{
        if(is_a($value, Data::class)) return $this->escSQL($value);
        if($value===null) return $this->sql('NULL');
        if(is_bool($value)) return $this->sql($value?'1':'0');
        if(is_int($value)||is_float($value)) return $this->sql(strval($value));
        return $this->sql($this->quoteVal(str_replace("\0", "", $value)));
    }

    /**
     * @param null|scalar|Data $value
     * @return SQL
     */
    public function value_bin($value): SQL{
        if(is_a($value, Data::class)) return $this->escSQL($value);
        if($value===null) return $this->sql('NULL');
        if(is_bool($value)) return $this->sql($value?'1':'0');
        if(is_int($value)||is_float($value)) return $this->sql($value);
        return $this->sql($this->quoteBin(strval($value)));
    }

    public function values(array $value): SQL{
        $sql=implode(',', array_map([$this, 'value'], $value));
        return $this->sql($sql);
    }

    public function values_bin(array $value): SQL{
        $sql=implode(',', array_map([$this, 'value_bin'], $value));
        return $this->sql($sql);
    }

    public function vacuum(?string $schema=null, ?string $toFile=null){
        $sql='VACUUM';
        if(is_string($schema)) $sql.=' '.static::quoteName($schema);
        if(is_string($toFile)) $sql.=' INTO '.$this->value($toFile);
        return $this->exec($sql);
    }

    public function attach_database(string $file, string $as){
        $sql='ATTACH DATABASE '.static::value($file).' AS '.static::name($as);
        return $this->exec($sql);
    }

    public function detach_database(string $as){
        $sql='DETACH DATABASE '.static::name($as);
        return $this->exec($sql);
    }

    /**
     * @param string $type
     * @return string Uno de los valores de {@see ManagerBase::TYPES}
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
     * @param null|string|array $colDef Si es string se toma como el tipo de la columna.
     *
     * En caso de un array, estas son las propiedades esperadas:
     * <ul>
     * <li>type: Tipo de dato de la columna. Lista de posibles valores: {@see ManagerBase::TYPES}</li>
     * <li>notnull: {bool} Indica si la columna no admite nulos</li>
     * <li>unique: {bool} Indica si los valores de la columna son únicos</li>
     * <li>default/defaultExpr: El valor o expresión por defecto</li>
     * <li>stored/virtual: {string} Expresión que genera el valor de la columna</li>
     * <li>pk: {int|bool} Indica si la columna es parte del primary key. Si se compone de varias columnas, debe indicar la posición dentro del primary key [1|2|3|...]</li>
     * <li>ai: {bool} Indica si la columna es autoincremental</li>
     * </ul>
     * @return string
     */
    public function columnDef(string $colName, $colDef){
        if(is_string($colDef)) $colDef=[
            'type'=>$colDef
        ];
        if(!is_array($colDef)) $colDef=[];
        $def=$this->name_($colName);
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

    public function sql_functionList(): SQL{
        $sql='PRAGMA function_list';
        return $this->sql($sql);
    }

    public function sql_indexList(?string $schema=null, ?string $table=null): SQL{
        if(is_string($schema)) $schema=static::quoteName($schema).'.';
        $sql="SELECT `name`, `tbl_name` FROM ".$schema.static::$tbMaster.' WHERE type="index"';
        if(!is_null($table)) $sql.=' AND tbl_name='.$this->value($table);
        return $this->sql($sql);
    }

    public function sql_tableDDL(string $table, ?string $schema=null): SQL{
        if(is_string($schema)) $schema=static::quoteName($schema).'.';
        $sql='SELECT `sql` FROM '.$schema.self::$tbMaster.' WHERE type="table" AND name='.$this->value($table);
        return $this->sql($sql);
    }

    public function sql_tableList(?string $schema=null): SQL{
        if(is_string($schema)) $schema=static::quoteName($schema).'.';
        $sql='SELECT `name` FROM '.$schema.self::$tbMaster.' WHERE type='.$this->value('table');
        return $this->sql($sql);
    }

    public function sql_viewList(?string $schema=null): SQL{
        if(is_string($schema)) $schema=static::quoteName($schema).'.';
        $sql='SELECT `name` FROM '.$schema.self::$tbMaster.' WHERE type='.$this->value('view');
        return $this->sql($sql);
    }

    public function sql_tableInfo(string $table, ?string $schema=null): SQL{
        if(is_string($schema)) $schema=static::quoteName($schema).'.';
        $sql='pragma '.$schema.'table_info('.$this->name_($table).')';
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param array $columns Las llaves son los nombres y el valor es la info de la columna. {@see ManagerBase::columnDef()}
     * @param array|null $constraints
     * @param array $options
     * Estas son las propiedades esperadas:
     * <ul>
     * <li>temp: {bool} Default FALSE. Se crea una tabla temporal</li>
     * <li>if: {bool} Default FALSE. La tabla se intenta crear solo si no existe</li>
     * <li>strict: {bool} Default: FALSE. Los tipos de datos de la tabla son estrictos. Disponible desde la V.3.37.0 {@link https://www.sqlite.org/stricttables.html#backwards_compatibility}</li>
     * <li>without_rowid: {bool} Default: FALSE: La tabla tiene ROW ID. Disponible desde la V.3.8.2 {@link https://www.sqlite.org/withoutrowid.html#compatibility}</li>
     * </ul>
     * @return false|string
     */
    public function sql_createTable(string $table, array $columns, ?array $constraints=null, array $options=[]): SQL{
        $sql="CREATE ".(($options['temp']??false)?"TEMP ":'')."TABLE ";
        if($options['if']??false) $sql.="IF NOT EXISTS ";
        $sql.=$this->name_($table)."(\n";
        $pk=[];
        $first=true;
        foreach($columns as $colName=>$colDef){
            if($first) $first=false;
            else $sql.=",\n";
            if(!($sql.=$this->columnDef($colName, $colDef))) return false;
            if(($colDef['pk'] ?? false) && !($colDef['ai'] ?? false)){
                $pk[$colDef['pk']]=$colName;
            }
        }
        if(count($pk)){
            ksort($pk);
            $sql.=",\nPRIMARY KEY(".$this->nameList($pk, false).")";
        }
        if(is_array($constraints) && count($constraints)>0) $sql.=",\n".implode(",\n", array_map([$this, 'sql'], $constraints));
        $sql.="\n)";
        if($options['without_rowid']??false) $sql.="\nWITHOUT ROWID";
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param array $columns Las llaves son los nombres y el valor es la info de la columna. {@see ManagerBase::columnDef()}
     * @param array|null $constraints
     * @param array $options
     * Estas son las propiedades esperadas:
     * <ul>
     * <li>temp: {bool} Default FALSE. Se crea una tabla temporal</li>
     * <li>if: {bool} Default FALSE. La tabla se intenta crear solo si no existe</li>
     * </ul>
     * @return false|string
     */
    public function sql_createTableSelect(string $table, string $select, array $options=[]): SQL{
        $sql="CREATE ".(($options['temp']??false)?"TEMP ":'')."TABLE ";
        if($options['if']??false) $sql.="IF NOT EXISTS ";
        $sql.=$this->name_($table);
        $sql.=" AS ".$select;
        return $this->sql($sql);
    }

    public function sql_renameTable(string $table, string $newTable): SQL{
        $sql="ALTER TABLE ".$this->name_($table)."\nRENAME TO ".$this->name_($newTable);
        return $this->sql($sql);
    }

    public function sql_dropTable(string $table, $if_exists=true): SQL{
        if($if_exists){
            $sql="DROP TABLE IF EXISTS ".$this->name_($table);
        }
        else{
            $sql="DROP TABLE ".$this->name_($table);
        }
        return $this->sql($sql);
    }

    public function sql_renameColumn(string $table, string $column, string $newColumn): SQL{
        $sql="ALTER TABLE ".$this->name_($table)."\nRENAME COLUMN ".$this->name_($column)." TO ".$this->name_($newColumn);
        return $this->sql($sql);
    }

    public function sql_addColumn(string $table, string $column, $colDef): SQL{
        $sql="ALTER TABLE ".$this->name_($table)."\nADD COLUMN ".$this->columnDef($column, $colDef);
        return $this->sql($sql);
    }

    public function whereAND_clause(array $list, $op='='): SQL{
        $sql='';
        $first=true;
        foreach($list as $col=>$var){
            if($first) $first=false;
            else $sql.=" AND\n";
            $sql.=(is_string($col)?$this->nameVar($col).$op:'').$this->value($var);
        }
        return $this->sql($sql);
    }

    public function whereOR_clause(array $list, $op='='): SQL{
        $sql='';
        $first=true;
        foreach($list as $col=>$var){
            if($first) $first=false;
            else $sql.=" OR\n";
            $sql.=(is_string($col)?$this->nameVar($col).$op:'').$this->value($var);
        }
        return $this->sql($sql);
    }

    public function selectValues_sql(array $val, array ...$values): SQL{
        $sql="VALUES (".$this->values($val).')';
        foreach($values as $row){
            $sql.=",\n(".$this->values($row).')';
        }
        return $this->sql($sql);
    }

    public function set_clause(array $list): SQL{
        $sql='';
        $first=true;
        foreach($list as $name=>$var){
            if($first) $first=false;
            else $sql.=",\n";
            if(!is_string($name) && is_a($var, Data::class)) $sql.=$this->esc($var);
            else $sql.=$this->nameVar($name).'='.$this->value($var);
        }
        return $this->sql($sql);
    }

    /**
     * Ver {@link https://www.sqlite.org/syntax/ordering-term.html}
     */
    const REGEXP_ORDER='/^(:?(?:ASC|DESC)|(?:ASC\s+|DESC\s+)?NULLS\s+(?:FIRST|LAST))$/i';

    /**
     * Ver {@link https://www.sqlite.org/syntax/ordering-term.html}
     *
     * Ver {@link https://www.sqlite.org/syntax/indexed-column.html}
     */
    const REGEXP_ORDER_SIMPLE='/^(?:ASC|DESC)$/i';

    public function orderBy_clause(array $list, bool $simple=false): SQL{
        $sql='';
        $first=true;
        foreach($list as $name=>$ord){
            if($first) $first=false;
            else $sql.=",\n";
            if(is_string($name)){
                $ord=strtoupper($ord);
                $sql.=static::name_($name).(($simple?preg_match(self::REGEXP_ORDER_SIMPLE, $ord):preg_match(self::REGEXP_ORDER, $ord))?' '.$ord:'');
            }
            else{
                $sql.=static::name($ord);
            }
        }
        return $this->sql($sql);
    }

    public function select_sql(array $columns, $from=null, ?array $where=null, ?array $groupBy=null, ?array $having=null, ?string $window=null, ?array $orderBy=null, $limit=null, $offset=null): SQL{
        $sql="SELECT ".$this->nameVarList($columns);
        if(is_array($from)) $sql.="\nFROM ".$this->nameVarList($from);
        elseif($from!==null) $sql.="\nFROM ".$this->nameVar($from);
        if($where!==null) $sql.="\nWHERE ".$this->whereAND_clause($where);
        if($groupBy!==null) $sql.="\nGROUP BY ".$this->nameVarList($groupBy, false);
        if($having!==null) $sql.="\nHAVING ".$this->whereAND_clause($having);
        if($window!==null) $sql.="\nWINDOW ".$window;
        if($orderBy!==null) $sql.="\nORDER BY ".$this->orderBy_clause($orderBy);
        if($limit!==null) $sql.="\nLIMIT ".$this->value($limit);
        if($offset!==null) $sql.="\nOFFSET ".$this->value($offset);
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param string|null $or Valores: {@see ManagerBase::$INSERT_UPDATE_OR_LIST}
     * @param array|null $returning
     * @return SQL
     */
    public function insert_default_sql(string $table, ?string $or=null, ?array $returning=null): SQL{
        $sql="INSERT";
        if(is_string($or) && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" INTO ".$this->name_($table)."\nDEFAULT VALUES";
        if($returning!==null) $sql.="\nRETURNING ".$this->nameVarList($returning);
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param array $data Array asociativo con los datos a insertar <code>
     * [
     *   "columnaA"=>"valorA",
     *   "columnaB"=>"valorB",
     * ]</code>
     * @param string|null $or Valores: {@see ManagerBase::$INSERT_UPDATE_OR_LIST}
     * @param string|null $upsert {@see ManagerBase::upsert_clause()}
     * @param array|null $returning
     * @return SQL
     */
    public function insert_sql(string $table, array $data, ?string $or=null, ?string $upsert=null, ?array $returning=null): SQL{
        $sql="INSERT";
        if(is_string($or) && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" INTO ".$this->name_($table);
        $sql.="(".$this->nameList(array_keys($data), false).")";
        $sql.=" VALUES(".$this->values($data).")";
        if(is_string($upsert)) $sql.="\n".$upsert;
        if($returning!==null) $sql.="\nRETURNING ".$this->nameVarList($returning);
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param array|null $columns
     * @param string $select
     * @param string|null $or Valores: {@see ManagerBase::$INSERT_UPDATE_OR_LIST}
     * @param string|null $upsert {@see ManagerBase::upsert_clause()}
     * @param array|null $returning
     * @return SQL
     */
    public function insert_select_sql(string $table, ?array $columns, string $select, ?string $or=null, ?string $upsert=null, ?array $returning=null): SQL{
        $sql="INSERT";
        if(is_string($or) && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" INTO ".$this->name_($table);
        if($columns) $sql.=" (".$this->nameList($columns, false).")";
        $sql.="\n".$select;
        if(is_string($upsert)) $sql.="\n".$upsert;
        if($returning!==null) $sql.="\nRETURNING ".$this->nameVarList($returning);
        return $this->sql($sql);
    }

    public function update_sql($table, array $data, $from, ?array $where, ?string $or=null, ?array $returning=null): SQL{
        $sql="UPDATE";
        if($or!==null && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" ".$this->qualified_name($table)." SET ".$this->set_clause($data);
        if(is_array($from)) $sql.="\nFROM ".$this->nameVarList($from);
        elseif($from!==null) $sql.="\nFROM ".$this->nameVar($from);
        if($where!==null) $sql.="\nWHERE ".$this->whereAND_clause($where);
        if($returning!==null) $sql.="\nRETURNING ".$this->nameVarList($returning);
        return $this->sql($sql);
    }

    public function delete_sql($table, array $where, ?array $returning=null): SQL{
        $sql="DELETE FROM ".$this->qualified_name($table);
        $sql.="\nWHERE ".$this->whereAND_clause($where);
        if($returning!==null) $sql.="\nRETURNING ".$this->nameVarList($returning);
        return $this->sql($sql);
    }

    /**
     * Usar para {@see ManagerBase::insert_select_sql()} y {@see ManagerBase::insert_sql()}
     *
     * Ver {@link https://www.sqlite.org/syntax/upsert-clause.html}
     * @param array|null $conflict_columns Ver {@link https://www.sqlite.org/syntax/indexed-column.html}
     * @param mixed $conflict_where  Solo aplicable si se indica $conflict_columns
     * @param array|null $update_set
     * @param mixed $update_where Solo aplicable si se indica $update_set
     * @return SQL
     */
    public function upsert_clause(?array $conflict_columns=null, $conflict_where=null, ?array $update_set=null, $update_where=null): SQL{
        $sql="ON CONFLICT";
        if($conflict_columns){
            $sql.=" (\n".$this->orderBy_clause($conflict_columns, true)."\n)";
            if($conflict_where!==null) $sql.="\nWHERE ".$this->whereAND_clause($conflict_where);
        }
        $sql.=" DO";
        if($update_set){
            $sql.=" UPDATE SET ".$this->set_clause($update_set);
            if($update_where!==null) $sql.="\nWHERE ".$this->whereAND_clause($update_where);
        }
        else{
            $sql.=" NOTHING";
        }
        return $this->sql($sql);
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
<?php

namespace SQLiteMan;

use PDO;
use PDOStatement;
use \SQVar;

/**
 * #SQLite Manager
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class Manager{
    use funcList;

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

    /**
     * @var PDO
     */
    protected $conn;
    private static $tbMaster='sqlite_master';

    /**
     * @param PDO $conn
     * @throws Exception
     */
    public function __construct(PDO $conn){
        if($conn->getAttribute(PDO::ATTR_DRIVER_NAME)!=='sqlite'){
            throw new Exception('Invalid connection driver');
        }
        $this->conn=&$conn;
    }

    public function lastError(){
        return Exception::fromConnection($this->conn);
    }

    public function vacuum(){
        return $this->conn->exec('VACUUM')!==false;
    }

    /**
     * @return PDO
     */
    public function conn(){
        return $this->conn;
    }

    public static function columnIndex(PDOStatement $stmt, string $column){
        $i=-1;
        $count=$stmt->columnCount();
        while(++$i<$count){
            if($stmt->getColumnMeta($i)['name']==$column) return $i;
        }
        return null;
    }

    /**
     * @return array|false|null
     */
    public function getSchemaList(){
        $stmt=$this->conn->prepare('PRAGMA database_list');
        if($stmt->execute()){
            $i=self::columnIndex($stmt, 'name');
            if($i===null) return null;
            return $stmt->fetchAll(PDO::FETCH_COLUMN, $i);
        }
        return null;
    }

    /**
     * @return array|false|null
     */
    public function getFunctionList(){
        $stmt=$this->conn->prepare('PRAGMA function_list');
        if($stmt->execute()){
            $i=self::columnIndex($stmt, 'name');
            if($i===null) return null;
            return $stmt->fetchAll(PDO::FETCH_COLUMN, $i);
        }
        return null;
    }

    /**
     * @return array|false|null
     */
    public function getFunctionInfo(string $name){
        $stmt=$this->conn->prepare('PRAGMA function_list');
        if($stmt->execute()){
            foreach($stmt as $item){
                if($item['name']==$name) return $item;
            }
        }
        return null;
    }

    /**
     * @param string $table
     * @param string|null $schema
     * @return array|false
     */
    public function getTableSQL(string $table, ?string $schema=null){
        if(is_string($schema)) $schema=$this->name($schema).'.';
        $stmt=$this->conn->prepare('SELECT "sql" FROM '.$schema.self::$tbMaster.' WHERE type=:type AND name=:name');
        $stmt->bindValue(':type', 'table');
        $stmt->bindValue(':name', $table);
        if($stmt->execute()){
            return $stmt->fetchColumn(0);
        }
        return null;
    }

    public function getTableList(?string $schema=null){
        if(is_string($schema)) $schema=$this->name($schema).'.';
        $stmt=$this->conn->prepare('SELECT name FROM '.$schema.self::$tbMaster.' WHERE type=:type');
        $stmt->bindValue(':type', 'table');
        if($stmt->execute()){
            $i=self::columnIndex($stmt, 'name');
            if($i===null) return null;
            return $stmt->fetchAll(PDO::FETCH_COLUMN, $i);
        }
        return null;
    }

    public function getViewList(?string $schema=null){
        if(is_string($schema)) $schema=$this->name($schema).'.';
        $stmt=$this->conn->prepare('SELECT name FROM '.$schema.self::$tbMaster.' WHERE type=:type');
        $stmt->bindValue(':type', 'view');
        if($stmt->execute()){
            $i=self::columnIndex($stmt, 'name');
            if($i===null) return null;
            return $stmt->fetchAll(PDO::FETCH_COLUMN, $i);
        }
        return null;
    }

    public function getIndexList(?string $schema=null, ?string $table=null){
        if(is_string($schema)) $schema=$this->name($schema).'.';
        $stmt=$this->conn->prepare('SELECT name, tbl_name FROM '.$schema.self::$tbMaster.' WHERE type=:type'.(is_null($table)?'':' AND tbl_name=:table'));
        $stmt->bindValue(':type', 'index');
        is_null($table) || $stmt->bindValue(':table', $table);
        if($stmt->execute()) return $stmt->fetchAll(PDO::FETCH_ASSOC);
        return null;
    }

    /**
     * @param string $table
     * @param string|null $schema
     * @return array|false
     */
    public function getTableInfo(string $table, ?string $schema=null){
        if(is_string($schema)) $schema=$this->name($schema).'.';
        $res=$this->conn->query('pragma '.$schema.'table_info('.$this->name($table).')');
        if(!$res) return false;
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ejecuta
     * {@see Manager::createTable_sql()}
     */
    public function createTable($table, array $columns, ?array $constraints=null, array $options=[]){
        $sql=$this->createTable_sql($table, $columns, $constraints, $options);
        if(!$sql) return $sql;
        return $this->conn->exec($sql)!==false;
    }

    /**
     * Ejecuta
     * {@see Manager::createTableSelect_sql()}
     */
    public function createTableSelect($table, string $select, array $options=[]){
        $sql=$this->createTableSelect_sql($table, $select, $options);
        if(!$sql) return $sql;
        return $this->conn->exec($sql)!==false;
    }

    /**
     * Ejecuta
     * {@see Manager::renameTable_sql()}
     */
    public function renameTable($table, string $newTable){
        $sql=$this->renameTable_sql($table, $newTable);
        if(!$sql) return $sql;
        return $this->conn->exec($sql)!==false;
    }

    /**
     * Ejecuta
     * {@see Manager::dropTable_sql()}
     */
    public function dropTable($table, $if_exists=true){
        $sql=$this->dropTable_sql($table, $if_exists);
        if(!$sql) return $sql;
        return $this->conn->exec($sql)!==false;
    }

    /**
     * Ejecuta
     * {@see Manager::addColumn_sql()}
     */
    public function addColumn($table, string $column, $info){
        $sql=$this->addColumn_sql($table, $column, $info);
        if(!$sql) return $sql;
        return $this->conn->exec($sql)!==false;
    }

    /**
     * Ejecuta
     * {@see Manager::renameColumn_sql()}
     */
    public function renameColumn($table, string $column, string $newColumn){
        $sql=$this->renameColumn_sql($table, $column, $newColumn);
        if(!$sql) return $sql;
        return $this->conn->exec($sql)!==false;
    }

    /**
     * # Esta función es experimental:
     * ## Su comportamiento y resultados pueden cambiar en futuras versiones
     *
     * Estos son los cambios detectados
     * ```PHP
     * [
     *   'add'=>[], // Lista de columnas por agregar (faltan en la tabla)
     *   'drop'=>[], // Lista de columnas por eliminar (opcional)
     *   'change'=>[], // Lista de columnas cuya definición no coindice por completo con lo indicado
     * ]
     * ```
     * @param $table
     * @param array $columns
     * @return array|false
     */
    public function detectColumnDiff($table, array $columns){
        $defs=$this->getTableInfo($table);
        if(!$defs || count($defs)==0) return false;
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
     * Para más información consulte {@link https://www.sqlite.org/datatype3.html#determination_of_column_affinity}
     * @param string $colName
     * @param null|string|array $colDef Si es string se toma como el tipo de la columna.
     *
     * En caso de un array, estas son las propiedades esperadas:
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
    public function columnDef(string $colName, $colDef){
        if(is_string($colDef)) $colDef=[
            'type'=>$colDef
        ];
        if(!is_array($colDef)) $colDef=[];
        $def=$this->name($colName);
        if(is_string($colDef['type'])) $def.=' '.self::value($colDef['type']);
        if($colDef['notnull'] ?? false) $def.=' NOT NULL';
        if($colDef['unique'] ?? false) $def.=' UNIQUE';

        if(isset($colDef['default'])) $def.=' DEFAULT '.$this->value($colDef['default']);
        elseif(is_string($colDef['defaultExpr'] ?? null)) $def.=' DEFAULT ('.$colDef['defaultExpr'].')';

        if(is_string($colDef['stored'] ?? null)) $def.=' AS ('.$colDef['stored'].') STORED';
        elseif(is_string($colDef['virtual'] ?? null)) $def.=' AS ('.$colDef['virtual'].') VIRTUAL';

        if(($colDef['pk'] ?? false) && ($colDef['ai'] ?? false)) $def.=' PRIMARY KEY AUTOINCREMENT';
        return $def;
    }

    /**
     * @param string|array $table
     * @param array $columns Las llaves son los nombres y el valor es la info de la columna. {@see Manager::columnDef()}
     * @param array|null $constraints
     * @param array $options
     * Estas son las propiedades esperadas:
     * <ul>
     * <li>temp: {bool} Default FALSE. Se crea una tabla temporal</li>
     * <li>if: {bool} Default FALSE. La tabla se intenta crear solo si no existe</li>
     * <li>strict: {bool} Default: FALSE. Los tipos de datos de la tabla son estrictos</li>
     * <li>without_rowid: {bool} Default: FALSE: La tabla tiene ROW ID</li>
     * </ul>
     * @return false|string
     */
    public function createTable_sql($table, array $columns, ?array $constraints=null, array $options=[]){
        $pk=[];
        $defs=[];
        foreach($columns as $colName=>$colDef){
            if(!($defs[]=$this->columnDef($colName, $colDef))) return false;
            if($colDef['pk'] ?? false && !($colDef['ai'] ?? false)){
                if(isset($pk[$colDef['pk']])) return false;
                $pk[$colDef['pk']]=$colName;
            }
        }
        if(count($pk)){
            ksort($pk);
            $defs[]='PRIMARY KEY('.$this->nameList(array_values($pk)).')';
        }
        if(is_array($constraints)) $defs=array_merge($defs, array_values($constraints));
        $sql='CREATE '.(($options['temp']??false)?'TEMP ':'').'TABLE ';
        if($options['if']??false) $sql.='IF NOT EXISTS ';
        $sql.=$this->name($table);
        $sql.="(\n\t".implode(",\n\t", $defs)."\n)";
        if($options['without_rowid']??false) $sql.=' WITHOUT ROWID';
        return $sql;
    }

    /**
     * @param string|array $table
     * @param array $columns Las llaves son los nombres y el valor es la info de la columna. {@see Manager::columnDef()}
     * @param array|null $constraints
     * @param array $options
     * Estas son las propiedades esperadas:
     * <ul>
     * <li>temp: {bool} Default FALSE. Se crea una tabla temporal</li>
     * <li>if: {bool} Default FALSE. La tabla se intenta crear solo si no existe</li>
     * </ul>
     * @return false|string
     */
    public function createTableSelect_sql($table, string $select, array $options=[]){
        $sql='CREATE '.(($options['temp']??false)?'TEMP ':'').'TABLE ';
        if($options['if']??false) $sql.='IF NOT EXISTS ';
        $sql.=$this->name($table);
        $sql.=' AS '.$select;
        return $sql;
    }

    public function renameTable_sql($table, string $newTable){
        $sql='ALTER TABLE '.$this->name($table).' RENAME TO '.$this->name($newTable);
        return $sql;
    }

    public function dropTable_sql($table, $if_exists=true){
        if($if_exists){
            $sql='DROP TABLE IF EXISTS '.$this->name($table);
        }
        else{
            $sql='DROP TABLE '.$this->name($table);
        }
        return $sql;
    }

    public function renameColumn_sql($table, string $column, string $newColumn){
        $sql='ALTER TABLE '.$this->name($table).' RENAME COLUMN '.$this->name($column).' TO '.$this->name($newColumn);
        return $sql;
    }

    public function addColumn_sql($table, string $column, $colDef){
        $sql='ALTER TABLE '.$this->name($table).' ADD COLUMN '.$this->columnDef($column, $colDef);
        return $sql;
    }

    public function setList(array $list){
        $sql=[];
        foreach($list as $name=>$var){
            $sql[]=$this->nameVar($name).'='.$this->nameVar($var);
        }
        $sql=implode(',', $sql);
        return $sql;
    }

    public function whereAND(array $list, $op='='){
        $sql=[];
        foreach($list as $col=>$var){
            $sql[]=(is_string($col)?$this->nameVar($col).$op:'').$this->nameVar($var);
        }
        $sql=implode(' AND ', $sql);
        return $sql;
    }

    public function whereOR(array $list, $op='='){
        $sql=[];
        foreach($list as $col=>$var){
            $sql[]=(is_string($col)?$this->nameVar($col).$op:'').$this->nameVar($var);
        }
        $sql=implode(' OR ', $sql);
        return $sql;
    }

    /**
     * Escapa un nombre de tabla o columna
     * @param string $name
     * @return string
     */
    public static function quoteName(string $name){
        return '`'.str_replace('`', '``', $name).'`';
    }

    /**
     * Escapa el nombre o nombres indicados
     * @param string|array|SQVar $name
     * @return string
     */
    public function name($name){
        if(is_a($name, SQVar::class)) return $this->SQVar($name);
        if(is_array($name)){
            $sql=[];
            foreach($name as $n){
                $sql[]=$this->name($n);
            }
            return implode('.', $sql);
        }
        $name=strval($name);
        if($name==='*') return $name;
        if(preg_match('/^(?:(`(?:[^`]|``)+`)|([^`][^\.]*))(?:\.(.*))?$/', $name, $m)){
            $r=($m[1]!=='')?$m[1]:self::quoteName($m[2]);
            if(isset($m[3])) $r.='.'.self::name($m[3]);
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
     * ###Si el origen del nombre es externo al código (como variables POST, GET, HEADERS, etc), debe utilizar primero la función {@see Esc::escapeName()} sobre esos valores
     * @param string|array|SQVar $name
     * @return string
     */
    public function nameVar($name){
        if(is_a($name, SQVar::class)) return $this->SQVar($name);
        if(is_array($name)){
            return $this->name($name);
        }
        else{
            $name=strval($name);
            if(preg_match('/^\:\w+$/', $name)){
                return $name;
            }
            else{
                return $this->name($name);
            }
        }
    }

    /**
     * Escapa las lista de nombres o expresiones separados por comas por medio de {@see Esc::escapeName()}
     * @param array $list Lista de nombres o expresiones. Si el indice es un string, se usa como alias
     * @return string
     * @link https://www.sqlite.org/syntax/expr.html
     * @link https://www.sqlite.org/syntax/result-column.html
     */
    /**
     * @param array $list
     * @return string
     */
    public function nameList(array $list){
        $sql=[];
        foreach($list as $as=>$name){
            $sql[]=static::name($name).(is_string($as)?' AS '.static::quoteName($as):'');
        }
        $sql=implode(',', $sql);
        return $sql;
    }

    /**
     * Escapa las lista de nombres o expresiones separados por comas por medio de {@see Esc::escapeNameVar()}
     * @param array $list Lista de nombres o expresiones. Si el indice es un string, se usa como alias
     * @return string
     * @link https://www.sqlite.org/syntax/expr.html
     * @link https://www.sqlite.org/syntax/result-column.html
     */
    /**
     * @param array $list
     * @return string
     */
    public function nameVarList(array $list){
        $sql=[];
        foreach($list as $as=>$name){
            $sql[]=$this->nameVar($name).(is_string($as)?' AS '.static::quoteName($as):'');
        }
        $sql=implode(',', $sql);
        return $sql;
    }

    /**
     * @param null|scalar|SQVar $value
     * @return bool|string
     */
    public function value($value){
        if(is_a($value, SQVar::class)) return $this->SQVar($value);
        if($value===null) return 'NULL';
        if(is_bool($value)) return $value?'1':'0';
        if(is_int($value)||is_float($value)) return strval($value);
        return $this->conn->quote(strval($value));
    }

    public function values(array $value){
        $sql=implode(',', array_map([$this, 'value'], $value));
        return $sql;
    }

    /**
     * @param SQVar $value
     * @return bool|string|null
     */
    public function SQVar(SQVar $value){
        if($value->getType()===SQVar::TYPE_SQL){
            return $value->getData();
        }
        elseif($value->getType()===SQVar::TYPE_VALUE){
            return $this->value($value->getData());
        }
        elseif($value->getType()===SQVar::TYPE_NAME){
            return $this->name($value->getData());
        }
        return "";
    }

    /**
     * @param SQVar ...$value
     * @return string
     */
    public function SQVars(SQVar ...$value){
        $sql=implode(',', array_map([$this, 'SQVar'], $value));
        return $sql;
    }

    public function fn($name, ...$params){
        $sql=$name.'('.$this->nameVarList($params).')';
        return $sql;
    }

    public function fn_val($name, ...$params){
        $sql=$name.'('.$this->values($params).')';
        return $sql;
    }

    public function selectValues(array ...$values){
        $sql=[];
        foreach($values as $row){
            $sql[]='('.$this->values($row).')';
        }
        $sql="VALUES\n\t".implode(",\n\t", $sql);
        return $sql;
    }

    public function groupBy($groupBy){
        //TODO
    }

    public function window($window){
        //TODO
    }

    public function select_sql(array $columns, $from=null, $where=null, $groupBy=null, $having=null, $window=null, $orderBy=null, $limit=null, $offset=null){
        $sql='SELECT '.$this->nameVarList($columns);
        if($from!==null) $sql.=' FROM '.$this->nameVarList($from);
        if($where!==null) $sql.=' WHERE '.$this->whereAND($where);
        if($groupBy!==null) $sql.=' GROUP BY '.$this->groupBy($groupBy);
        if($having!==null) $sql.=' HAVING '.$this->whereAND($having);
        if($window!==null) $sql.=' WINDOW '.$this->window($window);
        if($orderBy!==null) $sql.=' ORDER BY '.$this->nameVar($orderBy);
        if($limit!==null) $sql.=' LIMIT '.$this->value($limit);
        if($offset!==null) $sql.=' OFFSET '.$this->value($offset);
        return $sql;
    }

    public function insert_sql(){
        //TODO
    }

    public function update_sql(){
        //TODO
    }

    public function delete_sql(){
        //TODO
    }
}
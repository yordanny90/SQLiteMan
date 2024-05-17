<?php

namespace SQLiteMan;

/**
 * #IMPORTANTE:
 * La funciones que se declaren aquí, deben apegarse a la documentación oficial de sqlite.
 *
 * Todas las funciones son utilidades que deben retornar un objeto de {@see SQL}
 *
 * - Las funciones que inician con `sql_` generan sentencias que pueden ejecutarse
 * - Las funciones que inician con `_` generan llamados a funciones existentes en SQLite, y forman parte de un SQL
 * - Las funciones que terminan con `_` son utilidades que generan cláusulas o expresiones que forman una parte de un SQL
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
trait Manager_adds{
    private static $tbMaster='`sqlite_master`';

    public function concat_($val, ...$values): SQL{
        $sql=$this->value($val);
        foreach($values as $v){
            $sql->_concat($v);
        }
        return $this->sql($sql);
    }

    public function whereAND_(array $list): SQL{
        $sql=null;
        foreach($list as $col=>$val){
            if($sql===null){
                $sql=(is_string($col)?$this->name($col)->cond_equal($val):$this->value($val));
            }
            else{
                if(is_string($col)) $sql->_('AND')->_name($col)->cond_equal($val);
                else $sql->_('AND')->_value($val);
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    public function whereOR_(array $list): SQL{
        $sql=null;
        foreach($list as $col=>$val){
            if($sql===null){
                $sql=(is_string($col)?$this->name($col)->cond_equal($val):$this->value($val));
            }
            else{
                if(is_string($col)) $sql->_('OR')->_name($col)->cond_equal($val);
                else $sql->_('OR')->_value($val);
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    public function on_(array $list): SQL{
        $sql=null;
        foreach($list as $col=>$name){
            if($sql===null){
                $sql=(is_string($col)?$this->name($col)->cond_equal($this->name($name)):$this->name($name));
            }
            else{
                if(is_string($col)) $sql->_('AND')->_name($col)->cond_equal($this->name($name));
                else $sql->_('AND')->_name($name);
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    public function set_(array $list): SQL{
        $sql=null;
        foreach($list as $name=>$val){
            if($sql===null){
                $sql=$this->name($name)->_('='.$this->value($val));
            }
            else{
                $sql->_comma($this->name($name)->_('='.$this->value($val)));
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    /**
     * Ver {@link https://www.sqlite.org/syntax/ordering-term.html}
     * @param array|string|SQL|SelfEscape $name
     * @param string|null $ord 'ASC', 'DESC'
     * @param string|null $nullsPos 'FIRST', 'LAST'
     * @param string|null $collation
     * @return SQL
     */
    public function orderBy_($names, ?string $ord=null, ?string $nullsPos=null, ?string $collation=null): SQL{
        if(is_array($names)){
            $sql=null;
            foreach($names as $name){
                if($sql===null){
                    $sql=$this->orderBy_($name, $ord, $nullsPos, $collation);
                }
                else{
                    $sql->_comma($this->orderBy_($name, $ord, $nullsPos, $collation));
                }
            }
            if($sql===null) $sql=$this->sql('');
            return $sql;
        }
        $sql=$this->name($names);
        if(is_string($collation)) $sql->_('COLLATE')->_value($collation);
        if(is_string($ord) && in_array(strtoupper($ord), ['ASC', 'DESC'])) $sql->_($ord);
        if(is_string($nullsPos) && in_array(strtoupper($nullsPos), ['FIRST', 'LAST'])) $sql->_('NULLS '.$nullsPos);
        return $sql;
    }

    /**
     * Ver {@link https://www.sqlite.org/syntax/indexed-column.html}
     * @param string|SQL|SelfEscape $name
     * @param string|null $ord 'ASC', 'DESC'
     * @param string|null $collate
     * @return SQL
     */
    public function indexedColumn_($name, ?string $ord=null, ?string $collate=null): SQL{
        $sql=$this->name($name);
        if(is_string($collate)) $sql->_('COLLATE')->_value($collate);
        if(is_string($ord) && in_array(strtoupper($ord), ['ASC', 'DESC'])) $sql->_($ord);
        return $sql;
    }

    /**
     * Usar para {@see Manager::sql_insert_select()} y {@see Manager::sql_insert()}
     *
     * Ver {@link https://www.sqlite.org/syntax/upsert-clause.html}
     * @param null|array|string|SQL|SelfEscape $conflict_columns Ver {@see Manager::indexedColumn_()}
     * @param mixed $conflict_where  Solo aplicable si se indica $conflict_columns
     * @param array|null $update_set
     * @param array|null $update_where Solo aplicable si se indica $update_set
     * @return SQL
     */
    public function upsert_($conflict_columns=null, ?array $conflict_where=null, ?array $update_set=null, ?array $update_where=null): SQL{
        $sql="ON CONFLICT";
        if($conflict_columns!==null){
            $sql.=' ('.$this->names($conflict_columns, false).')';
            if($conflict_where) $sql.="\nWHERE ".$this->whereAND_($conflict_where);
        }
        $sql.=" DO";
        if($update_set!==null){
            $sql.=" UPDATE SET ".$this->set_($update_set);
            if($update_where) $sql.="\nWHERE ".$this->whereAND_($update_where);
        }
        else{
            $sql.=" NOTHING";
        }
        return $this->sql($sql);
    }

    /**
     * Genera el SQL de un CASE utilizable dentro de otra sentencia SQL.<br>
     * El objetivo es obtener un valor según la condición que se cumpla.
     * @param bool|string|SQL|SelfEscape $case_name Valor inicial que se comparará con las condiciones
     * @param SQL $whenList Lista de condiciones y los valores correspondientes. Ver {@see Manager::whenList_()}, {@see Manager::when_()}
     * @param null|string|SQL|SelfEscape $else_value Valor que se devolverá si ninguna condición se cumple
     * @return SQL
     */
    public function &case_($case_name, SQL $whenList, $else_value=null): SQL{
        $sql=$this->sql("CASE");
        if($case_name!==null){
            $sql->_name($case_name);
        }
        $sql->_($whenList);
        if($else_value!==null){
            $sql->_("ELSE")->_value($else_value);
        }
        return $sql->_("END");
    }

    /**
     * Usado para generar parametros de {@see Manager::case_()}
     *
     * Ejemplo:
     * ```php
     * $when=$man->whenList_([
     *   1=>'Uno',
     *   2=>'Dos',
     *   3=>'Tres'
     * ]);
     * ```
     * @param array $when_then
     * @return SQL
     */
    public function &whenList_(array $when_then): SQL{
        $sql=null;
        foreach($when_then as $when=>$then){
            if($sql===null){
                $sql=$this->sql('WHEN')->_value($when)->_('THEN')->_value($then);
            }
            else{
                $sql->_('WHEN')->_value($when)->_('THEN')->_value($then);
            }
        }
        if($sql===null) $sql=$this->sql('');
        return $sql;
    }

    /**
     * Usado para generar parametros de {@see Manager::case_()}
     *
     * Ejemplo:
     * ```php
     * $when=$man->when_(1, 'Uno')
     *   ->_($man->when_(2, 'Dos'))
     *   ->_($man->when_(3, 'Tres'));
     * ```
     * @param string|SQL|SelfEscape $when
     * @param string|SQL|SelfEscape $then
     * @return SQL
     */
    public function &when_($when, $then): SQL{
        return $this->sql('WHEN')->_value($when)->_('THEN')->_value($then);
    }

    /**
     * @param array|string|SQL|SelfEscape $names
     * @return SQL
     */
    public function &returning_($names){
        return $this->sql('RETURNING')->_names($names, true);
    }

    public function sql_functionList(): SQL{
        return $this->sql('PRAGMA function_list');
    }

    public function sql_indexList(?string $schema=null, ?string $table=null): SQL{
        if(is_string($schema)) $schema=$this->quoteName($schema).'.';
        $sql="SELECT `name`, `tbl_name` FROM ".$schema.static::$tbMaster.' WHERE '.$this->whereAND_(['type'=>"index"]);
        if(!is_null($table)) $sql.=' AND tbl_name='.$this->value($table);
        return $this->sql($sql);
    }

    public function sql_tableDDL(string $table, ?string $schema=null): SQL{
        if(is_string($schema)) $schema=$this->quoteName($schema).'.';
        $sql='SELECT `sql` FROM '.$schema.self::$tbMaster.' WHERE '.$this->whereAND_(['type'=>"table", 'name'=>$table]);
        return $this->sql($sql);
    }

    public function sql_tableList(?string $schema=null): SQL{
        if(is_string($schema)) $schema=$this->quoteName($schema).'.';
        $sql='SELECT `name` FROM '.$schema.self::$tbMaster.' WHERE '.$this->whereAND_(['type'=>"table"]);
        return $this->sql($sql);
    }

    public function sql_viewList(?string $schema=null): SQL{
        if(is_string($schema)) $schema=$this->quoteName($schema).'.';
        $sql='SELECT `name` FROM '.$schema.self::$tbMaster.' WHERE '.$this->whereAND_(['type'=>"view"]);
        return $this->sql($sql);
    }

    public function sql_tableInfo(string $table, ?string $schema=null): SQL{
        if(is_string($schema)) $schema=$this->quoteName($schema).'.';
        $sql='pragma '.$schema.'table_info('.$this->quoteName($table).')';
        return $this->sql($sql);
    }

    /**
     * @param string|SQL|SelfEscape $table
     * @param array $columns Las llaves son los nombres y el valor es la info de la columna. {@see Manager::columnDef()}
     * @param array|null $constraints
     * @param null|SQL $options Table options
     * @param bool $temp Si es true, se crea una tabla temporal
     * @param bool $if Si es true, se intenta crear la tabla solo si no existe
     * @return SQL
     * @link https://www.sqlite.org/lang_createtable.html
     */
    public function sql_createTable(string $table, array $columns, ?array $constraints=null, ?SQL $options=null, bool $temp=false, bool $if=false): SQL{
        $sql="CREATE ".($temp?"TEMP ":'')."TABLE ";
        if($if) $sql.="IF NOT EXISTS ";
        $sql.=static::quoteNameParts($table)."(\n";
        $pk=[];
        $first=true;
        foreach($columns as $colName=>$colDef){
            if($first) $first=false;
            else $sql.=",\n";
            $sql.=$this->columnDef($colName, $colDef);
            if(($colDef['pk'] ?? false) && !($colDef['ai'] ?? false)){
                $pk[$colDef['pk']]=$colName;
            }
        }
        if(count($pk)){
            ksort($pk);
            $sql.=",\nPRIMARY KEY(".$this->names($pk, false).")";
        }
        if(is_array($constraints) && count($constraints)>0) $sql.=",\n".implode(",\n", $constraints);
        $sql.="\n)";
        if($options) $sql.="\n".$options;
        return $this->sql($sql);
    }

    /**
     * @param string|SQL|SelfEscape $table
     * @param SQL $select Sentencia select
     * @param bool|null $temp Si es true, se crea una tabla temporal
     * @param bool|null $if Si es true, se intenta crear la tabla solo si no existe
     * @return SQL
     * @link https://www.sqlite.org/lang_createtable.html
     */
    public function sql_createTableSelect(string $table, SQL $select, ?bool $temp=false, ?bool $if=false): SQL{
        $sql="CREATE ".($temp?"TEMP ":'')."TABLE ";
        if($if) $sql.="IF NOT EXISTS ";
        $sql.=static::quoteNameParts($table);
        $sql.=" AS ".$select;
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param bool|null $if Si es true, se intenta eliminar la tabla solo si existe
     * @return SQL
     * @link https://www.sqlite.org/lang_droptable.html
     */
    public function sql_dropTable(string $table, ?bool $if=true): SQL{
        return $this->sql("DROP TABLE ".($if?"IF EXISTS ":"").static::quoteNameParts($table));
    }

    /**
     * @param string $table
     * @param string $newTable
     * @return SQL
     * @link https://www.sqlite.org/lang_altertable.html
     */
    public function sql_renameTable(string $table, string $newTable): SQL{
        $sql="ALTER TABLE ".static::quoteNameParts($table)." RENAME TO ".static::quoteName($newTable);
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $newColumn
     * @return SQL
     * @link https://www.sqlite.org/lang_altertable.html
     */
    public function sql_renameColumn(string $table, string $column, string $newColumn): SQL{
        $sql="ALTER TABLE ".static::quoteNameParts($table)." RENAME COLUMN ".static::quoteName($column)." TO ".static::quoteName($newColumn);
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param string $column
     * @param array $colDef
     * @return SQL
     * @link https://www.sqlite.org/lang_altertable.html
     */
    public function sql_addColumn(string $table, string $column, array $colDef): SQL{
        $sql="ALTER TABLE ".static::quoteNameParts($table)." ADD COLUMN ".$this->columnDef($column, $colDef);
        return $this->sql($sql);
    }

    /**
     * @param string $table
     * @param string $column
     * @return SQL
     * @link https://www.sqlite.org/lang_altertable.html
     */
    public function sql_dropColumn(string $table, string $column): SQL{
        $sql="ALTER TABLE ".static::quoteNameParts($table)." DROP COLUMN ".static::quoteName($column);
        return $this->sql($sql);
    }

    public function sql_selectValues(array $val, array ...$values): SQL{
        $sql="VALUES (".$this->values($val).')';
        foreach($values as $row){
            $sql.=",\n(".$this->values($row).')';
        }
        return $this->sql($sql);
    }

    /**
     * @param $select
     * @param $from
     * @param array|null $where
     * @param array|null $groupBy
     * @param array|null $having
     * @param SQL|null $window
     * @param null|array|string|SQL|SelfEscape $orderBy Ver {@see Manager::orderBy_()}
     * @param null|scalar|SQL|SelfEscape $limit
     * @param null|scalar|SQL|SelfEscape $offset Requiere indicar un limit
     * @return SQL
     */
    public function sql_select($select, $from=null, ?array $where=null, ?array $groupBy=null, ?array $having=null, ?SQL $window=null, $orderBy=null, $limit=null, $offset=null): SQL{
        $sql="SELECT ".$this->names($select, true);
        if($from!==null) $sql.="\nFROM ".$this->names($from, true);
        if($where) $sql.="\nWHERE ".$this->whereAND_($where);
        if($groupBy) $sql.="\nGROUP BY ".$this->names($groupBy, false);
        if($having) $sql.="\nHAVING ".$this->whereAND_($having);
        if($window!==null) $sql.="\nWINDOW ".$window;
        if($orderBy!==null) $sql.="\nORDER BY ".$this->orderBy_($orderBy);
        if($limit!==null){
            $sql.="\nLIMIT ".$this->value($limit);
            if($offset!==null) $sql.="\nOFFSET ".$this->value($offset);
        }
        return $this->sql($sql);
    }

    static $INSERT_UPDATE_OR_LIST=[
        self::OR_ABORT,
        self::OR_FAIL,
        self::OR_IGNORE,
        self::OR_REPLACE,
        self::OR_ROLLBACK,
    ];

    /**
     * @param string|SQL|SelfEscape $table
     * @param string|null $or Valores: {@see Manager::$INSERT_UPDATE_OR_LIST}
     * @return SQL
     */
    public function sql_insert_default($table, ?string $or=null): SQL{
        $sql="INSERT";
        if(is_string($or) && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" INTO ".$this->name($table)."\nDEFAULT VALUES";
        return $this->sql($sql);
    }

    /**
     * @param string|SQL|SelfEscape $table
     * @param array $data Array asociativo con los datos a insertar <code>
     * [
     *   "columnaA"=>"valorA",
     *   "columnaB"=>"valorB",
     * ]</code>
     * @param string|null $or Valores: {@see Manager::$INSERT_UPDATE_OR_LIST}
     * @param SQL|null $upsert {@see Manager::upsert_()}
     * @return SQL
     */
    public function sql_insert($table, array $data, ?string $or=null, ?SQL $upsert=null): SQL{
        $sql="INSERT";
        if(is_string($or) && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" INTO ".$this->name($table);
        $sql.="(".$this->names(array_keys($data), false).")";
        $sql.=" VALUES(".$this->values($data).")";
        if($upsert!==null) $sql.="\n".$upsert;
        return $this->sql($sql);
    }

    /**
     * @param string|SQL|SelfEscape $table
     * @param array|null $columns
     * @param SQL $select
     * @param string|null $or Valores: {@see Manager::$INSERT_UPDATE_OR_LIST}
     * @param SQL|null $upsert {@see Manager::upsert_()}
     * @return SQL
     */
    public function sql_insert_select($table, ?array $columns, SQL $select, ?string $or=null, ?SQL $upsert=null): SQL{
        $sql="INSERT";
        if(is_string($or) && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" INTO ".$this->name($table);
        if($columns!==null) $sql.=" (".$this->names($columns, false).")";
        $sql.="\n".$select;
        if($upsert!==null) $sql.="\n".$upsert;
        return $this->sql($sql);
    }

    public function sql_update($table, array $data, $from, ?array $where, ?string $or=null): SQL{
        $sql="UPDATE";
        if($or!==null && in_array(strtoupper($or), static::$INSERT_UPDATE_OR_LIST)) $sql.=" OR ".$or;
        $sql.=" ".$this->name($table)." SET ".$this->set_($data);
        if($from!==null) $sql.="\nFROM ".$this->names($from, true);
        if($where) $sql.="\nWHERE ".$this->whereAND_($where);
        return $this->sql($sql);
    }

    public function sql_delete($table, ?array $where): SQL{
        $sql="DELETE FROM ".$this->name($table);
        if($where) $sql.="\nWHERE ".$this->whereAND_($where);
        return $this->sql($sql);
    }

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en valores</b>
     * @param string $fn Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return SQL
     */
    public function fn_values(string $fn, ...$params): SQL{
        return $this->sql($fn)->_parentheses($this->values($params));
    }

    /**
     * Genera el SQL de la llamada a una función<br>
     * <b>Nota: Todos los parámetros se convertirán en nombres</b>
     * @param string $fn Nombre de la función
     * @param mixed ...$params Parámetros de la función
     * @return SQL
     */
    public function fn_names(string $fn, ...$params): SQL{
        return $this->sql($fn)->_parentheses($this->names($params, false));
    }

    public function _CAST($val, $type): SQL{
        return $this->sql('CAST')->_parentheses($this->value($val)->_('AS')->_value($type));
    }

    public function _ABS($val): SQL{
        return $this->fn_values('ABS', $val);
    }

    public function _CHANGES(): SQL{
        return $this->fn_values('CHANGES');
    }

    public function _CHAR(...$vals): SQL{
        return $this->fn_values('CHAR', ...$vals);
    }

}

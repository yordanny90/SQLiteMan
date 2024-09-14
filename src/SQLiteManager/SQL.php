<?php

namespace SQLiteManager;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
class SQL{
    use SQL_adds;

    /**
     * @var string
     */
    protected $d='';
    /**
     * @var \SQLiteMan
     */
    protected $man;
    
    public function __construct(string $data, \SQLiteMan &$db){
        $this->d=$data;
        $this->man=&$db;
    }

    /**
     * Ejecuta esta sentencia SQL
     * @param array|null $params Ver {@see \SQLiteMan::query()}
     * @return Result|null
     */
    public function query(?array $params=null): ?Result{
        return $this->man->query($this, $params);
    }

    public function &_parentheses(string $sql=''): self{
        $this->d.='('.$sql.')';
        return $this;
    }

    public function &_(string $sql): self{
        $this->d.=' '.$sql;
        return $this;
    }

    public function &_comma(string $sql=''): self{
        $this->d.=','.$sql;
        return $this;
    }

    public function &_comma_value($value): self{
        return $this->_comma($this->man->value($value));
    }

    public function &_comma_name($name): self{
        return $this->_comma($this->man->name($name));
    }

    public function &_as(string $alias): self{
        return $this->_('AS '.\SQLiteMan::quoteName($alias));
    }

    public function &_concat($value): self{
        $this->d.='||'.$this->man->value($value);
        return $this;
    }

    public function &_value($value): self{
        return $this->_($this->man->value($value));
    }

    /**
     * @param $values
     * @return $this
     * @see \SQLiteMan::values()
     */
    public function &_values($values): self{
        return $this->_($this->man->values($values));
    }

    public function &_name($name): self{
        return $this->_($this->man->name($name));
    }

    public function &_names($value, bool $alias=true): self{
        return $this->_($this->man->names($value, $alias));
    }

    public function __toString(){
        return $this->d;
    }

    public function __clone(){
        return new self($this->d, $this->man);
    }
}
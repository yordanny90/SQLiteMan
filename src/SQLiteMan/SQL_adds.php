<?php

namespace SQLiteMan;

/**
 * #IMPORTANTE:
 * La funciones que se declaren aquí, deben apegarse a la documentación oficial de sqlite.
 *
 * Repositorio {@link https://github.com/yordanny90/SQLiteMan}
 */
trait SQL_adds{

    public function cond_equal($val){
        if($val===null) return $this->cond_is($val);
        return $this->_('=')->_value($val);
    }

    public function &cond_diff($val){
        if($val===null) return $this->cond_is_not($val);
        return $this->_('<>')->_value($val);
    }

    public function cond_is($val){
        return $this->_('IS')->_value($val);
    }

    public function &cond_is_not($val){
        return $this->_('IS NOT')->_value($val);
    }

    public function &cond_greater($val){
        return $this->_('>')->_value($val);
    }

    public function &cond_not_greater($val){
        return $this->_('!>')->_value($val);
    }

    public function &cond_less($val){
        return $this->_('<')->_value($val);
    }

    public function &cond_not_less($val){
        return $this->_('!<')->_value($val);
    }

    public function &cond_less_equal($val){
        return $this->_('<=')->_value($val);
    }

    public function &cond_greater_equal($val){
        return $this->_('>=')->_value($val);
    }

    public function &cond_between($val_init, $val_end){
        return $this->_('BETWEEN')->_value($val_init)->_('AND')->_value($val_end);
    }

    public function &cond_not_between($val_init, $val_end){
        return $this->_('NOT BETWEEN')->_value($val_init)->_('AND')->_value($val_end);
    }

    public function &cond_inlist(...$values){
        return $this->_('IN')->_parentheses($this->man->values($values));
    }

    public function &cond_not_inlist(...$values){
        return $this->_('NOT IN')->_parentheses($this->man->values($values));
    }

    public function &cond_like($val){
        return $this->_('LIKE')->_value($val);
    }

    public function &cond_not_like($val){
        return $this->_('NOT LIKE')->_value($val);
    }

    public function &cond_begins($val){
        return $this->cond_like($this->man->concat_($val,'%'));
    }

    public function &cond_not_begins($val){
        return $this->cond_not_like($this->man->concat_($val,'%'));
    }

    public function &cond_ends($val){
        return $this->cond_like($this->man->concat_('%', $val));
    }

    public function &cond_not_ends($val){
        return $this->cond_not_like($this->man->concat_('%', $val));
    }

    public function &cond_contains($val){
        return $this->cond_like($this->man->concat_('%', $val, '%'));
    }

    public function &cond_not_contains($val){
        return $this->cond_not_like($this->man->concat_('%', $val, '%'));
    }

    /**
     * @param string|array|SQL|SelfEscape $name
     * @param string|null $joinType Valores: {@see Manager::JOINS}
     * @return SQL
     */
    public function &join($name, ?string $joinType=null){
        if($joinType!==null && in_array(strtoupper($joinType), Manager::JOINS)) $this->_($joinType);
        return $this->_('JOIN')->_names($name, true);
    }

    /**
     * @param string|array|SQL|SelfEscape $name
     * @param string|null $joinType Valores: {@see Manager::JOINS}
     * @return SQL
     */
    public function &natural_join($name, ?string $joinType=null){
        $this->_('NATURAL');
        if($joinType!==null && in_array(strtoupper($joinType), Manager::JOINS)) $this->_($joinType);
        return $this->_('JOIN')->_names($name, true);
    }

    /**
     * @param string|array|SQL|SelfEscape $name
     * @param array $on
     * @param string|null $joinType Valores: {@see Manager::JOINS}
     * @return SQL
     */
    public function &join_on($name, array $on, ?string $joinType=null){
        if($joinType!==null && in_array(strtoupper($joinType), Manager::JOINS)) $this->_($joinType);
        return $this->_('JOIN')->_names($name, true)->_('ON')->_($this->man->on_($on));
    }

    /**
     * @param string|array|SQL|SelfEscape $name
     * @param array $using
     * @param string|null $joinType Valores: {@see Manager::JOINS}
     * @return SQL
     */
    public function &join_using($name, array $using, ?string $joinType=null){
        if($joinType!==null && in_array(strtoupper($joinType), Manager::JOINS)) $this->_($joinType);
        return $this->_('JOIN')->_names($name, true)->_('USING')->_parentheses($this->man->names($using, false));
    }

    /**
     * @param string|array|SQL|SelfEscape $name
     * @return SQL
     */
    public function &cross_join($name){
        return $this->_('CROSS JOIN')->_names($name, true);
    }

}

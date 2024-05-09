<?php

namespace SQLiteMan;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class Value extends Data{
    protected function __construct($data){
        if(!is_scalar($data) && $data!==null) $data=strval($data);
        parent::__construct($data);
    }
}
<?php

namespace SQLiteMan;

/**
 * Interface para crear nuevos tipos de datos que se escapan automáticamente a {@see SQL} cuando sea necesario
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
interface SelfEscape{
    public function toSQLite(Manager &$man): SQL;
}
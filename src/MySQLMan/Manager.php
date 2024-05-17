<?php
namespace MySQLMan;

/**
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
abstract class Manager{
    use Manager_adds;

    const ENGINE_MYISAM='MyISAM';
    const ENGINE_INNODB='InnoDB';
    const ENGINE_MEMORY='MEMORY';

    const INDEX_TYPE_FULLTEXT='FULLTEXT';
    const INDEX_TYPE_SPATIAL='SPATIAL';
    const INDEX_TYPE_UNIQUE='UNIQUE';

    const INDEX_METHOD_BTREE='BTREE';
    const INDEX_METHOD_HASH='HASH';

}
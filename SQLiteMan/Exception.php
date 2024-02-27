<?php

namespace SQLiteMan;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Lista de códigos de resultados obtenidos de {@link https://sqlite.org/c3ref/c_abort.html}
 *
 * Repositorio {@link https://github.com/yordanny90/SQLManager}
 */
class Exception extends \Exception{
    /**
     * Successful result
     */
    const SQLITE_OK=0;
    /**
     * Generic error
     */
    const SQLITE_ERROR=1;
    /**
     * Internal logic error in SQLite
     */
    const SQLITE_INTERNAL=2;
    /**
     * Access permission denied
     */
    const SQLITE_PERM=3;
    /**
     * Callback routine requested an abort
     */
    const SQLITE_ABORT=4;
    /**
     * The database file is locked
     */
    const SQLITE_BUSY=5;
    /**
     * A table in the database is locked
     */
    const SQLITE_LOCKED=6;
    /**
     * A malloc() failed
     */
    const SQLITE_NOMEM=7;
    /**
     * Attempt to write a readonly database
     */
    const SQLITE_READONLY=8;
    /**
     * Operation terminated by sqlite3_interrupt()
     */
    const SQLITE_INTERRUPT=9;
    /**
     * Some kind of disk I/O error occurred
     */
    const SQLITE_IOERR=10;
    /**
     * The database disk image is malformed
     */
    const SQLITE_CORRUPT=11;
    /**
     * Unknown opcode in sqlite3_file_control()
     */
    const SQLITE_NOTFOUND=12;
    /**
     * Insertion failed because database is full
     */
    const SQLITE_FULL=13;
    /**
     * Unable to open the database file
     */
    const SQLITE_CANTOPEN=14;
    /**
     * Database lock protocol error
     */
    const SQLITE_PROTOCOL=15;
    /**
     * Internal use only
     */
    const SQLITE_EMPTY=16;
    /**
     * The database schema changed
     */
    const SQLITE_SCHEMA=17;
    /**
     * String or BLOB exceeds size limit
     */
    const SQLITE_TOOBIG=18;
    /**
     * Abort due to constraint violation
     */
    const SQLITE_CONSTRAINT=19;
    /**
     * Data type mismatch
     */
    const SQLITE_MISMATCH=20;
    /**
     * Library used incorrectly
     */
    const SQLITE_MISUSE=21;
    /**
     * Uses OS features not supported on host
     */
    const SQLITE_NOLFS=22;
    /**
     * Authorization denied
     */
    const SQLITE_AUTH=23;
    /**
     * Not used
     */
    const SQLITE_FORMAT=24;
    /**
     * 2nd parameter to sqlite3_bind out of range
     */
    const SQLITE_RANGE=25;
    /**
     * File opened that is not a database file
     */
    const SQLITE_NOTADB=26;
    /**
     * Notifications from sqlite3_log()
     */
    const SQLITE_NOTICE=27;
    /**
     * Warnings from sqlite3_log()
     */
    const SQLITE_WARNING=28;
    /**
     * sqlite3_step() has another row ready
     */
    const SQLITE_ROW=100;
    /**
     * sqlite3_step() has finished executing
     */
    const SQLITE_DONE=101;

    public static function fromConnection(PDO $conn, ?Throwable $previous=null){
        [,$code,$msg]=$conn->errorInfo();
        if(!is_int($code) || !is_string($msg)) return null;
        return new static($msg, $code, $previous);
    }

    public static function fromStatement(PDOStatement $stmt, ?Throwable $previous=null){
        [,$code,$msg]=$stmt->errorInfo();
        if(!is_int($code) || !is_string($msg)) return null;
        return new static($msg, $code, $previous);
    }

    public static function fromException(PDOException $err){
        [,$code,$msg]=$err->errorInfo;
        if(!is_int($code) || !is_string($msg)) return null;
        return new static($msg, $code, $err);
    }
}
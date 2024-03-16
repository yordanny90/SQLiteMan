<?php
// Require PHP 7.2+
require __DIR__.'/../src/_autoloader.php';

$db=__DIR__.'/test.db';
try{
//    $m=new SQLiteManPDO(new PDO('sqlite:'.$db));
//    $m->conn()->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $m=new SQLiteMan(new SQLite3($db));
    $m->timeout(5);
    $m->throwExceptions(true);
    $m->exec($sql=$m->dropTable_sql('proc'));
    $m->exec($sql=$m->createTable_sql('proc', [
        'ID'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'pk'=>1,
            'ai'=>1,
            'notnull'=>true,
        ],
        'name'=>[
            'type'=>SQLiteMan::TYPE_TEXT,
            'notnull'=>true,
            'default'=>'',
        ],
        'open'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'default'=>0,
            'notnull'=>true,
        ],
        'close'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'default'=>0,
            'notnull'=>true,
        ],
        'dt'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'defaultExpr'=>$m->fn_val('datetime', 'now', 'localtime'),
            'notnull'=>true,
        ],
    ], null, [
        'if'=>true,
    ]));
    $i=-1;
    while(++$i<256) $m->exec($sql=$m->insert_sql('proc', [
        'name'=>'N:'.chr($i).'--',
    ]));
    $res=$m->query($sql=$m->select_sql(['*'], 'proc', [SQData::s("ID IN (1,5,23,230,99)")]));
    foreach($res as $row){
        echo json_encode($row).PHP_EOL;
    }
}catch(PDOException $err){
    $e=\SQLiteMan\Exception::fromPDOException($err);
}catch(SQLite3Exception $err){
    $e=\SQLiteMan\Exception::fromSQLiteException($err);
}catch(Exception $err){
    $e=$err;
}

exit;

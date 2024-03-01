<?php
// Require PHP 7.2+
require __DIR__.'/../src/SQVar.php';
require __DIR__.'/../src/SQLiteMan/funcList.php';
require __DIR__.'/../src/SQLiteMan/Exception.php';
require __DIR__.'/../src/SQLiteMan/Manager.php';

$dsnLite='sqlite:'.__DIR__.'/test.db';
try{
    $m=new \SQLiteMan\Manager(new PDO($dsnLite));
    $m->conn()->setAttribute(PDO::ATTR_TIMEOUT, 5);
    $m->conn()->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    //$m->dropTable('proc');
    $r=$m->createTable('proc', [
        'name'=>[
            'type'=>'text',
            'pk'=>1,
            'notnull'=>true,
        ],
        'open'=>[
            'type'=>'integer',
            'default'=>0,
            'notnull'=>true,
        ],
        'close'=>[
            'type'=>'integer',
            'default'=>0,
            'notnull'=>true,
        ],
        'a'=>[
            'type'=>'integer',
            'defaultExpr'=>$m->fn('datetime'),
            'notnull'=>true,
        ],
    ], null, [
        'if'=>true,
        'without_rowid'=>true
    ]);
}catch(PDOException $err){
    $e=\SQLiteMan\Exception::fromException($err);
}catch(Exception $err){
}
exit;

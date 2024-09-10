<?php
// Require PHP 7.2+, 8.0+

$dir_class=__DIR__.'/../src';
spl_autoload_register(function($class) use ($dir_class){
	if($class=='SQLiteMan' || strpos($class, 'SQLiteMan\\')===0){
		include $dir_class.'/'.$class.'.php';
	}
});

$db='test.db';
try{
    $m=new SQLiteMan(new PDO('sqlite::memory:'));
    $m->attach_database($db, 'test');
    $m->fetchMode(PDO::FETCH_ASSOC);
    $m->timeout(5);
    $m->throwExceptions(true);
    $m->query($sql=$m->sql_dropTable('test.proc'));
	$m->query($sql=$m->sql_createTable('test.proc', [
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
            'defaultExpr'=>$m->fn_values('datetime', 'now', 'localtime'),
            'notnull'=>true,
        ],
    ], null, null, false, true));
    $i=32;
    while(++$i<127) $m->query($sql=$m->sql_insert('test.proc', [
        'name'=>'N:'.chr($i).'--',
        'open'=>$i,
    ]));
    $res=$m->query($sql=$m->sql_select('*', 'test.proc', [$m->name("ID")->cond_inlist(...[1, 5, 23, 230, 99])]));
    foreach($res as $row){
        echo json_encode($row).PHP_EOL;
    }
    $res=$m->query($sql=$m->sql_select('*', 'test.proc', [$m->name("name")->cond_contains("0")], null, null, null, null, 10));
    echo $sql;
    foreach($res as $row){
        echo json_encode($row).PHP_EOL;
    }
}catch(PDOException $err){
    $e=\SQLiteMan\Exception::fromPDOException($err);
}catch(Exception $err){
    $e=$err;
}

exit;

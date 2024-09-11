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
    $m->query($sql=$m->sql_dropTable('test.chars'));
	$m->query($sql=$m->sql_createTable('test.chars', [
        'ID'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'pk'=>1,
            'ai'=>1,
            'notnull'=>true,
        ],
        'char'=>[
            'type'=>SQLiteMan::TYPE_TEXT,
            'notnull'=>true,
            'default'=>'',
        ],
        'val'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'default'=>0,
            'notnull'=>true,
        ],
        'ord'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'default'=>0,
            'notnull'=>true,
            'unique'=>true,
        ],
        'dt'=>[
            'type'=>SQLiteMan::TYPE_INTEGER,
            'defaultExpr'=>$m->fn_values('datetime', 'now', 'localtime'),
            'notnull'=>true,
        ],
    ], null, null, false, true));
    $start=33;
    $i=-1;
    while(++$i<256){
        $c=$i+$start;
        $m->query($sql=$m->sql_insert('test.chars', [
            'char'=>'('.chr($c).')',
            'ord'=>ord(chr($c)),
            'val'=>$c,
        ], SQLiteMan::OR_IGNORE));
    }
    $res=$m->query($sql=$m->sql_select('*', 'test.chars', [
        $m->name('ord')->cond_inlist(...[33, 37, 55, 127, 128, 0, 1, 6, 32])
    ], null, null, null, ['ID'=>'ASC']));
    echo $sql.PHP_EOL;
    foreach($res as $row){
        echo (json_encode($row)?:'#ERROR_JSON: '.$row['ord'].' '.$row['char']).PHP_EOL;
    }
    $res=$m->query($sql=$m->sql_select('*', 'test.chars', [
        $m->name("char")->cond_contains($m->sql(':c'))
    ], null, null, null, null, 10), [':c'=>"(\00)"]);
    echo $sql.PHP_EOL;
    foreach($res as $row){
        echo (json_encode($row)?:'#ERROR_JSON: '.$row['ord'].' '.$row['char']).PHP_EOL;
    }
}catch(PDOException $err){
    $e=\SQLiteMan\Exception::fromPDOException($err);
}catch(Exception $err){
    $e=$err;
}

exit;

<?php
function connectDatabase(): PDO {
 $c = require APP_ROOT.'/config/database.php';
 $opts=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
 if ($c['driver']==='sqlite') { $db=new PDO('sqlite:'.$c['path'],null,null,$opts); $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000'); return $db; }
 if ($c['driver']!=='mysql') throw new RuntimeException('Invalid DB_DRIVER');
 $opts[PDO::ATTR_EMULATE_PREPARES]=false;
 return new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['name'].';charset=utf8mb4',$c['user'],$c['password'],$opts);
}

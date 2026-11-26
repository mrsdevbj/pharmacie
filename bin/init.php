<?php
if (PHP_SAPI!=='cli') exit(1);
define('APP_ROOT',dirname(__DIR__)); require APP_ROOT.'/app/Core/Database.php'; require APP_ROOT.'/app/Core/Security.php';
require APP_ROOT.'/app/Models/GuardPharmacy.php';
try { $pdo=connectDatabase(); $driver=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME); $sql=file_get_contents(APP_ROOT.'/database/migrations/'.$driver.'.sql'); foreach(explode(';',$sql) as $stmt) if(trim($stmt)!=='') $pdo->exec($stmt); GuardPharmacy::migrate($pdo); echo "Schéma initialisé.\n"; }
catch(Throwable $e) { fwrite(STDERR,"Initialisation impossible : ".$e->getMessage()."\n"); exit(1); }

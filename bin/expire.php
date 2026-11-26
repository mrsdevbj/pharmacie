<?php
if(PHP_SAPI!=='cli')exit(1);
define('APP_ROOT',dirname(__DIR__));require APP_ROOT.'/app/Core/Database.php';require APP_ROOT.'/app/Models/Reservation.php';
try{$db=connectDatabase();do{$n=Reservation::expire($db);echo "$n réservation(s) expirée(s).\n";}while($n===500);}catch(Throwable $e){fwrite(STDERR,"Expiration impossible.\n");exit(1);}

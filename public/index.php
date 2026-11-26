<?php
define('APP_ROOT',dirname(__DIR__));
ini_set('display_errors','0'); ini_set('log_errors','1');
ini_set('error_log',APP_ROOT.'/storage/logs/php.log');
require APP_ROOT.'/app/Core/Database.php';
require APP_ROOT.'/app/Core/Security.php';
require APP_ROOT.'/app/Core/Auth.php';
require APP_ROOT.'/app/Core/Router.php';
require APP_ROOT.'/app/Models/Medicine.php';
require APP_ROOT.'/app/Models/Reservation.php';
require APP_ROOT.'/app/Models/GuardPharmacy.php';
if (getenv('APP_ENV')==='production' && !secureConnection()) { http_response_code(403); exit('Connexion HTTPS requise.'); }
ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>secureConnection(),'httponly'=>true,'samesite'=>'Lax']); session_start();
if (isset($_SESSION['_last']) && time()-$_SESSION['_last']>1800) { $_SESSION=[]; session_regenerate_id(true); } $_SESSION['_last']=time();
header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: no-referrer'); header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
if (secureConnection() && getenv('APP_ENV')==='production') header('Strict-Transport-Security: max-age=31536000');
try { $pdo=connectDatabase(); Reservation::expire($pdo); dispatch(require APP_ROOT.'/routes/web.php'); }
catch(Throwable $e) { if(isset($pdo)&&$pdo->inTransaction()) $pdo->rollBack(); error_log('Application failure: '.get_class($e).' at '.basename($e->getFile()).':'.$e->getLine()); http_response_code(500); echo 'Service temporairement indisponible.'; }

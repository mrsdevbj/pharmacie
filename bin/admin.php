<?php
if (PHP_SAPI!=='cli') exit(1);
define('APP_ROOT',dirname(__DIR__)); require APP_ROOT.'/app/Core/Database.php'; require APP_ROOT.'/app/Core/Security.php';
$email=strtolower(trim($argv[1]??'')); $mode=$argv[2]??'create';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($mode,['create','reset'],true)) { fwrite(STDERR,"Usage : php bin/admin.php email [create|reset]\n"); exit(1); }
// Password supplied through stdin, not shell arguments.
fwrite(STDOUT,"Mot de passe (12 à 72 octets) : "); $pass=rtrim((string)fgets(STDIN),"\r\n"); if(strlen($pass)<12||strlen($pass)>72) exit(1);
try { $pdo=connectDatabase(); $hash=hashPassword($pass); if($mode==='create') $pdo->prepare('INSERT INTO admin_users(email,password) VALUES(?,?)')->execute([$email,$hash]); else { $s=$pdo->prepare('UPDATE admin_users SET password=? WHERE email=?'); $s->execute([$hash,$email]); if(!$s->rowCount()) throw new RuntimeException('Compte inconnu'); } echo "Compte enregistré.\n"; } catch(Throwable $e) { fwrite(STDERR,"Opération impossible.\n"); exit(1); }

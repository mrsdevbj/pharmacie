<?php
function csrfToken(): string { return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); }
function csrfField(): string { return '<input type="hidden" name="_csrf" value="'.csrfToken().'">'; }
function validQuantity($value,int $min): bool { return is_scalar($value) && filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>100000000]])!==false; }
function hashPassword(string $p): string { return password_hash($p, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT); }
function secureConnection(): bool { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; }
function rememberEmail(string $email): void { setcookie('admin_remember_email',$email,['expires'=>time()+($email ? 2592000 : -3600),'path'=>'/','secure'=>secureConnection(),'httponly'=>true,'samesite'=>'Lax']); }
function allowLogin(string $type,string $email): bool {
 // Persistent per-IP and per-account limit, independent of browser session.
 global $pdo;
 $now=time(); $window=intdiv($now,900);
 foreach (['ip:'.($_SERVER['REMOTE_ADDR']??'unknown'),'account:'.strtolower($email)] as $key) {
  $key=hash('sha256',$type.':'.$key);
  $sql=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite' ? 'INSERT INTO login_attempts (bucket, window_id, attempts) VALUES (?, ?, 1) ON CONFLICT(bucket,window_id) DO UPDATE SET attempts=attempts+1' : 'INSERT INTO login_attempts (bucket, window_id, attempts) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE attempts=attempts+1';
  $pdo->prepare($sql)->execute([$key,$window]);
  $s=$pdo->prepare('SELECT attempts FROM login_attempts WHERE bucket=? AND window_id=?'); $s->execute([$key,$window]);
  if ((int)$s->fetchColumn()>10) return false;
 }
 $pdo->prepare('DELETE FROM login_attempts WHERE window_id < ?')->execute([$window-1]);
 return true;
}
function safeZipEntry(ZipArchive $zip,string $name): string|false { $s=$zip->statName($name); if (!$s || $s['size']>4000000) return false; $v=$zip->getFromName($name); return $v!==false && !preg_match('/<!DOCTYPE|<!ENTITY/i',$v) ? $v : false; }
function formatMoney($v): string { return number_format((float)$v,2,',',' ').' FCFA'; }
function setFlash(string $message,string $type='success'): void { $_SESSION['flash_message']=['message'=>$message,'type'=>$type]; }
function getFlash(): ?array { $f=$_SESSION['flash_message']??null; unset($_SESSION['flash_message']); return $f; }
function isProductAvailable(array $m): bool { return (int)$m['quantite']>0 && (int)$m['is_actif']===1; }

function parsePrice(string $value): ?string { $value=str_replace(',','.',trim($value)); if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$value))return null; return $value; }

<?php
function dispatch(array $routes): void {
 $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $method=$_SERVER['REQUEST_METHOD'];
 foreach ($routes as [$pattern,$methods,$file,$admin]) {
  if (!preg_match('~^'.$pattern.'$~D',$path,$m)) continue;
  if (!in_array($method,$methods,true)) { header('Allow: '.implode(', ',$methods)); http_response_code(405); exit('Méthode non autorisée.'); }
  if ($admin) requireAdminAuth();
  if ($method==='POST' && (!is_string($_POST['_csrf']??null)||!hash_equals(csrfToken(),$_POST['_csrf']))) { http_response_code(403); exit('Session du formulaire expirée.'); }
  foreach ([$_GET,$_POST] as $input) foreach ($input as $value) if (!is_string($value)) { http_response_code(400); exit('Paramètre invalide.'); }
  if (isset($m['id'])) $_GET['id']=$m['id'];
  global $pdo;
  require APP_ROOT.'/app/Controllers/'.$file; return;
 }
 http_response_code(404); echo 'Page introuvable.';
}

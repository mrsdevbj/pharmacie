<?php
$title='Utilisateurs';$nav='/admin/utilisateurs';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try {
    if (($_POST['kind'] ?? 'admin') !== 'admin' || ($_GET['type'] ?? 'admin') !== 'admin') {
        throw new DomainException('Seuls les comptes administrateurs peuvent être créés ici.');
    }
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!is_string($email) || !is_string($password)) throw new DomainException('Données invalides.');
    $email = strtolower(trim($email));
    if (strlen($email)>190 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12 || strlen($password)>72) {
        throw new DomainException('Adresse email ou mot de passe invalide.');
    }
    $pdo->prepare('INSERT INTO admin_users(email,password) VALUES(?,?)')->execute([$email,hashPassword($password)]);
    setFlash('Administrateur créé.');
 } catch (DomainException $e) {
    setFlash($e->getMessage(),'error');
 } catch (PDOException $e) {
    if ($e->getCode() !== '23000') throw $e;
    setFlash('Un compte existe déjà avec cette adresse.','error');
 }
 header('Location: /admin/utilisateurs?type=admin'); exit;
}
$kind=($_GET['type']??'admin')==='client'?'client':'admin';$page=max(1,min(100000,(int)($_GET['page']??1)));$offset=($page-1)*50;$rows=$pdo->query(($kind==='admin'?'SELECT id,email FROM admin_users':'SELECT id,nom,email,telephone FROM client_users').' ORDER BY id DESC LIMIT 51 OFFSET '.$offset)->fetchAll();$more=count($rows)>50;if($more)array_pop($rows);$flash=getFlash();require APP_ROOT.'/app/Views/utilisateurs.php';

<?php
$title='Ajouter du stock';$nav='/admin';$flash=null;$message=[];$id=(int)$_GET['id'];$s=$pdo->prepare('SELECT * FROM medicament WHERE id=?');$s->execute([$id]);$med=$s->fetch();if(!$med){http_response_code(404);exit('Médicament introuvable.');}
if($_SERVER['REQUEST_METHOD']==='POST'){if(validQuantity($_POST['quantite']??'',1)&&Medicine::addStock($pdo,$id,(int)$_POST['quantite'])){setFlash('Stock ajouté.');header('Location: /admin');exit;}$message=['text'=>'Quantité invalide ou stock maximal dépassé.'];}
require APP_ROOT.'/app/Views/stock.php';

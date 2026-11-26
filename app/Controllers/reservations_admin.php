<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
 try {
  $target=$_POST['target']??'';
  if(!in_array($target,['confirmed','rejected','collected','cancelled'],true)||!validQuantity($_POST['id']??'',1)||!Reservation::transition($pdo,(int)$_POST['id'],$target))throw new DomainException('Cette réservation ne peut plus être modifiée.');
  setFlash('Réservation mise à jour.');
 }catch(DomainException $e){setFlash($e->getMessage(),'error');}
 header('Location: /admin/reservations');exit;
}
$status=$_GET['status']??'';$where='';$params=[];if(isset(Reservation::LABELS[$status])){$where=' WHERE r.status=?';$params[]=$status;}
$page=max(1,min(100000,(int)($_GET['page']??1)));$offset=($page-1)*50;
$s=$pdo->prepare('SELECT r.*,c.nom AS client_name FROM reservations r JOIN client_users c ON c.id=r.client_id'.$where.' ORDER BY r.id DESC LIMIT 51 OFFSET '.$offset);$s->execute($params);$rows=$s->fetchAll();$more=count($rows)>50;if($more)array_pop($rows);
$title='Réservations';$flash=getFlash();require APP_ROOT.'/app/Views/reservations_admin.php';

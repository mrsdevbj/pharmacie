<?php
if (!isClientLoggedIn()) {
    if(isset($_GET['medicament']) && validQuantity($_GET['medicament'],1)) $_SESSION['reserve_medicine']=(int)$_GET['medicament'];
    header('Location: /connexion');
    exit;
}

$message = '';
$messageType = 'error';
$email = (string) ($_SESSION['client_email'] ?? '');
$name = (string) ($_SESSION['client_nom'] ?? 'Client');
$patientId = isset($_SESSION['client_patient_id']) ? (int) $_SESSION['client_patient_id'] : null;

$patientStmt = $pdo->prepare('SELECT id, full_name, phone, email FROM patient_accounts WHERE id = ? AND email = ? LIMIT 1');
$patientStmt->execute([$patientId, $email]);
$patient = $patientStmt->fetch();
if ($patient) {
    $patientId = (int) $patient['id'];
    $name = (string) $patient['full_name'];
    $_SESSION['client_patient_id'] = $patientId;
}

$flash=getFlash(); if($flash){$message=$flash['message'];$messageType=$flash['type'];}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 try {
  if(($_POST['action']??'')==='cancel') {
   if(!Reservation::transition($pdo,(int)($_POST['id']??0),'cancelled',(int)$_SESSION['client_id'])) throw new DomainException('Réservation non modifiable.');
   setFlash('Réservation annulée.');
  } else {
   if(($_POST['action']??'')!=='reserve'||!validQuantity($_POST['quantity']??'',1)||!validQuantity($_POST['medicament_id']??'',1))throw new DomainException('Demande invalide.');
   $ref=Reservation::create($pdo,(int)$_SESSION['client_id'],(int)$_POST['medicament_id'],(int)$_POST['quantity'],trim($_POST['phone']??''));
   setFlash('Réservation enregistrée : '.$ref);
  }
 } catch(DomainException $e){setFlash($e->getMessage(),'error');}
 header('Location: /mon-compte');exit;
}
$selected=(int)($_GET['medicament']??$_SESSION['reserve_medicine']??0);unset($_SESSION['reserve_medicine']);
$medicaments = $pdo->query("SELECT id, nom, prix, quantite, is_actif
    FROM medicament
    WHERE quantite > 0 AND is_actif = 1
    ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$s=$pdo->prepare('SELECT * FROM reservations WHERE client_id=? ORDER BY id DESC LIMIT 100');$s->execute([$_SESSION['client_id']]);$requests=$s->fetchAll();

require APP_ROOT . '/app/Views/espace_client.php';

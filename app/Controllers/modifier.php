<?php


$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /admin');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM medicament WHERE id = ?');
$stmt->execute([$id]);
$med = $stmt->fetch();

if (!$med) {
    http_response_code(404); exit('Médicament introuvable.');
}

$title='Modifier un médicament';$nav='/admin';$flash=null;$message=[];
$held=Medicine::held($pdo,(int)$id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $quantite = max(0, (int) ($_POST['quantite'] ?? 0));
    $prix=parsePrice($_POST['prix']??'');
    $isActif = isset($_POST['is_actif']) ? 1 : 0;

    if ($prix===null || $nom === '' || strlen($nom) > 190 || !validQuantity($_POST['quantite'] ?? '', 0)) {
        $message = ['type' => 'error', 'text' => 'Nom, quantité ou prix invalide.'];
    } else {
        if(Medicine::setPhysicalStock($pdo,(int)$id,$quantite,$isActif,$nom,$prix)){header('Location: /admin');exit;}
        $message=['type'=>'error','text'=>'Stock physique inférieur aux quantités réservées.'];
    }
}

require APP_ROOT . '/app/Views/modifier.php';

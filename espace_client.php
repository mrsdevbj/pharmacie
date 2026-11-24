<?php
require_once __DIR__ . '/db.php';

if (empty($_SESSION['client_id'])) {
    header('Location: connexion_client.php');
    exit;
}

$message = '';
$messageType = 'error';
$email = (string) ($_SESSION['client_email'] ?? '');
$name = (string) ($_SESSION['client_nom'] ?? 'Client');
$patientId = isset($_SESSION['client_patient_id']) ? (int) $_SESSION['client_patient_id'] : null;

$patientStmt = $pdo->prepare('SELECT id, full_name, phone, email FROM patient_accounts WHERE email = ? LIMIT 1');
$patientStmt->execute([$email]);
$patient = $patientStmt->fetch();
if ($patient) {
    $patientId = (int) $patient['id'];
    $name = (string) $patient['full_name'];
    $_SESSION['client_patient_id'] = $patientId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reserve') {
    $medicamentId = (int) ($_POST['medicament_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $paymentMode = trim((string) ($_POST['payment_mode'] ?? ''));

    $medStmt = $pdo->prepare('SELECT id, nom, prix, quantite, is_actif FROM medicament WHERE id = ? LIMIT 1');
    $medStmt->execute([$medicamentId]);
    $medicament = $medStmt->fetch();

    if (!$medicament || $quantity < 1 || $phone === '' || !in_array($paymentMode, ['mobile_money', 'carte'], true)) {
        $message = 'Veuillez choisir un produit, une quantité, un téléphone et un mode de paiement.';
    } elseif ((int) $medicament['quantite'] < $quantity || (int) $medicament['is_actif'] !== 1) {
        $message = 'Ce produit n’est pas disponible dans la quantité demandée.';
    } else {
        $unitPrice = (int) round((float) ($medicament['prix'] ?? 0));
        $totalAmount = $unitPrice * $quantity;

        try {
            $pdo->beginTransaction();
            $accessTokenHash = hash('sha256', random_bytes(32));
            $requestStmt = $pdo->prepare("INSERT INTO patient_requests
                (access_token_hash, request_type, patient_id, patient_name, phone, email, status, total_amount, payment_mode, payment_phone)
                VALUES (?, 'reservation', ?, ?, ?, ?, 'paid', ?, ?, ?)");
            $requestStmt->execute([
                $accessTokenHash,
                $patientId,
                $name,
                $phone,
                $email,
                $totalAmount,
                $paymentMode,
                $phone,
            ]);
            $requestId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO patient_request_items
                (request_id, medicament_id, medicament_name, quantity, unit_price, is_available)
                VALUES (?, ?, ?, ?, ?, 1)');
            $itemStmt->execute([$requestId, $medicamentId, $medicament['nom'], $quantity, $unitPrice]);

            $pdo->commit();
            $message = 'Votre réservation a été enregistrée et le paiement confirmé.';
            $messageType = 'success';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = 'Impossible d’enregistrer la réservation pour le moment.';
        }
    }
}

$medicaments = $pdo->query("SELECT id, nom, prix, quantite, is_actif
    FROM medicament
    WHERE quantite > 0 AND is_actif = 1
    ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$requests = [];
if ($patientId) {
    $requestStmt = $pdo->prepare('SELECT id, status, total_amount, payment_mode, created_at
        FROM patient_requests
        WHERE patient_id = ?
        ORDER BY created_at DESC');
    $requestStmt->execute([$patientId]);
    $requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);
}

$guardRow = $pdo->query('SELECT is_on_call, updated_at FROM pharmacy_settings WHERE id = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$isOnCall = $guardRow ? (bool) $guardRow['is_on_call'] : false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace - Pharmacie OLG</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#07130f;--panel:rgba(17,42,33,.92);--border:rgba(52,211,153,.22);--primary:#10b981;--text:#f0fdf4;--muted:#6ee7b7}
        *{box-sizing:border-box;margin:0;padding:0} body{min-height:100vh;font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--text);background:radial-gradient(circle at 15% 15%,rgba(16,185,129,.12),transparent 35%),var(--bg)}
        header{height:68px;padding:0 28px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);background:rgba(7,19,15,.88);backdrop-filter:blur(12px)}
        .brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:800}.mark{width:38px;height:38px;display:grid;place-items:center;border-radius:9px;background:linear-gradient(135deg,#10b981,#059669)}
        .logout{min-height:38px;padding:0 14px;display:grid;place-items:center;border:1px solid var(--border);border-radius:9px;color:#d1fae5;text-decoration:none;font-size:.78rem;font-weight:700}
        main{max-width:1120px;margin:0 auto;padding:32px 24px 48px}.welcome{margin-bottom:24px}.welcome h1{font-size:1.7rem}.welcome p{margin-top:5px;color:var(--muted);font-size:.86rem}
        .grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(280px,.6fr);gap:18px}.panel{border:1px solid var(--border);border-radius:14px;background:var(--panel);padding:20px}.panel h2{font-size:1rem;margin-bottom:16px}
        .guard{margin-bottom:18px}.guard-status{display:flex;align-items:center;gap:10px;padding:14px;border-radius:10px;font-weight:800}.guard-status.on{background:rgba(16,185,129,.14);color:#6ee7b7;border:1px solid rgba(52,211,153,.3)}.guard-status.off{background:rgba(159,18,57,.15);color:#fda4af;border:1px solid rgba(244,63,94,.28)}
        form{display:grid;grid-template-columns:1fr 100px;gap:10px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:.76rem;font-weight:700;color:#d1fae5}input,select{width:100%;min-height:46px;border:1px solid var(--border);border-radius:9px;background:rgba(7,19,15,.78);color:white;padding:0 12px;font-family:inherit;outline:none}input:focus,select:focus{border-color:var(--primary)}
        .pay-btn{grid-column:1/-1;min-height:48px;border:none;border-radius:9px;background:linear-gradient(135deg,#10b981,#059669);color:white;font-family:inherit;font-weight:800;cursor:pointer}.message{grid-column:1/-1;border-radius:9px;padding:10px 12px;font-size:.78rem}.message.success{background:rgba(16,185,129,.14);color:#6ee7b7;border:1px solid rgba(52,211,153,.28)}.message.error{background:rgba(159,18,57,.15);color:#fda4af;border:1px solid rgba(244,63,94,.28)}
        .profile{display:flex;align-items:center;gap:12px;margin-bottom:14px}.avatar{width:48px;height:48px;display:grid;place-items:center;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);font-weight:800}.profile-name{font-weight:800}.profile-email{color:var(--muted);font-size:.76rem}
        .reservation-list{display:grid;gap:8px}.reservation{padding:12px;border:1px solid var(--border);border-radius:9px}.reservation-row{display:flex;justify-content:space-between;gap:10px;font-size:.76rem}.reservation strong{font-size:.82rem}.empty{color:var(--muted);font-size:.78rem;padding:8px 0}
        @media(max-width:820px){header{padding:0 16px}.brand span{display:none}main{padding:24px 16px}.grid{grid-template-columns:1fr}form{grid-template-columns:1fr}.field.full,.pay-btn,.message{grid-column:1}}
    </style>
</head>
<body>
<header><a class="brand" href="recherche_patient.php"><div class="mark">Rx</div><span>Pharmacie OLG</span></a><a class="logout" href="deconnexion_client.php">Déconnexion</a></header>
<main>
    <div class="welcome"><h1>Bonjour, <?= htmlspecialchars($name) ?></h1><p>Votre espace client Pharmacie OLG</p></div>
    <div class="grid">
        <div>
            <section class="panel guard"><h2>Pharmacie de garde</h2><div class="guard-status <?= $isOnCall ? 'on' : 'off' ?>"><span><?= $isOnCall ? '●' : '●' ?></span><?= $isOnCall ? 'La pharmacie est actuellement de garde' : 'La pharmacie n’est pas de garde actuellement' ?></div></section>
            <section class="panel"><h2>Réserver un produit et payer</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="reserve">
                    <?php if ($message !== ''):?><div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                    <div class="field full"><label for="medicament_id">Produit</label><select id="medicament_id" name="medicament_id" required><option value="">Choisir un médicament</option><?php foreach($medicaments as $med): ?><option value="<?= (int)$med['id'] ?>"><?= htmlspecialchars($med['nom']) ?> — <?= formatMoney($med['prix'] ?? 0) ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label for="quantity">Quantité</label><input id="quantity" type="number" name="quantity" min="1" value="1" required></div>
                    <div class="field"><label for="phone">Téléphone</label><input id="phone" type="tel" name="phone" value="<?= htmlspecialchars((string)($patient['phone'] ?? '')) ?>" required></div>
                    <div class="field full"><label for="payment_mode">Mode de paiement</label><select id="payment_mode" name="payment_mode" required><option value="">Choisir le paiement</option><option value="mobile_money">Mobile Money</option><option value="carte">Carte bancaire</option></select></div>
                    <button class="pay-btn" type="submit">Payer et réserver</button>
                </form>
            </section>
        </div>
        <aside>
            <section class="panel"><h2>Mon profil</h2><div class="profile"><div class="avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($name,0,1,'UTF-8'),'UTF-8')) ?></div><div><div class="profile-name"><?= htmlspecialchars($name) ?></div><div class="profile-email"><?= htmlspecialchars($email) ?></div></div></div></section>
            <section class="panel" style="margin-top:18px"><h2>Mes réservations</h2><div class="reservation-list">
                <?php if(empty($requests)): ?><p class="empty">Aucune réservation pour le moment.</p><?php else: foreach($requests as $request): ?><div class="reservation"><div class="reservation-row"><strong>Réservation #<?= (int)$request['id'] ?></strong><span><?= htmlspecialchars($request['status']) ?></span></div><div class="reservation-row" style="margin-top:6px"><span><?= formatMoney($request['total_amount']) ?></span><span><?= date('d/m/Y H:i',strtotime($request['created_at'])) ?></span></div></div><?php endforeach; endif; ?>
            </div></section>
        </aside>
    </div>
</main>
</body>
</html>

<?php
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['client_id'])) {
    header('Location: espace_client.php');
    exit;
}

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $message = 'Veuillez renseigner votre email et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nom, email, password FROM client_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if (!$client) {
            $stmt = $pdo->prepare('SELECT id, full_name AS nom, email, password_hash AS password FROM patient_accounts WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $client = $stmt->fetch();
        }

        if (!$client || !password_verify($password, $client['password'])) {
            $message = 'Email ou mot de passe incorrect.';
        } else {
            $patientStmt = $pdo->prepare('SELECT id FROM patient_accounts WHERE email = ? LIMIT 1');
            $patientStmt->execute([$email]);
            $patient = $patientStmt->fetch();

            $_SESSION['client_id'] = (int) $client['id'];
            $_SESSION['client_patient_id'] = $patient ? (int) $patient['id'] : null;
            $_SESSION['client_nom'] = $client['nom'];
            $_SESSION['client_email'] = $email;

            header('Location: espace_client.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion client - Pharmacie OLG</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07130f; --panel:rgba(17,42,33,.92); --border:rgba(52,211,153,.22); --primary:#10b981; --text:#f0fdf4; --muted:#6ee7b7; }
        * { box-sizing:border-box; }
        body { min-height:100vh; margin:0; display:grid; place-items:center; padding:24px; font-family:'Plus Jakarta Sans',system-ui,sans-serif; color:var(--text); background:radial-gradient(circle at 15% 15%,rgba(16,185,129,.14),transparent 38%),var(--bg); }
        .shell { width:min(430px,100%); }
        .back { display:inline-block; margin-bottom:14px; color:var(--muted); text-decoration:none; font-size:.82rem; font-weight:700; }
        .card { padding:28px; border:1px solid var(--border); border-radius:16px; background:var(--panel); box-shadow:0 24px 60px rgba(0,0,0,.38); }
        .brand { display:flex; align-items:center; gap:12px; margin-bottom:22px; }
        .mark { width:42px; height:42px; display:grid; place-items:center; border-radius:10px; background:linear-gradient(135deg,#10b981,#059669); font-weight:800; }
        .brand strong { display:block; }
        .brand span { color:var(--muted); font-size:.76rem; }
        h1 { margin:0 0 18px; font-size:1.55rem; }
        form { display:grid; gap:12px; }
        label { display:grid; gap:6px; color:#d1fae5; font-size:.78rem; font-weight:700; }
        input { width:100%; min-height:48px; border:1px solid var(--border); border-radius:10px; background:rgba(7,19,15,.78); color:white; padding:0 13px; font-family:inherit; outline:none; }
        input:focus { border-color:var(--primary); }
        button { min-height:48px; border:none; border-radius:10px; background:linear-gradient(135deg,#10b981,#059669); color:white; font-family:inherit; font-weight:800; cursor:pointer; }
        .message { padding:10px 12px; border:1px solid rgba(244,63,94,.28); border-radius:9px; background:rgba(159,18,57,.18); color:#fda4af; font-size:.78rem; }
        .create { display:block; margin-top:16px; text-align:center; color:var(--muted); font-size:.82rem; text-decoration:none; }
        @media(max-width:520px){ body{padding:16px;align-items:start}.shell{margin-top:12px}.card{padding:20px 16px} }
    </style>
</head>
<body>
    <div class="shell">
        <a class="back" href="recherche_patient.php">← Retour à la recherche</a>
        <main class="card">
            <div class="brand"><div class="mark">Rx</div><div><strong>Pharmacie OLG</strong><span>Espace client</span></div></div>
            <h1>Se connecter</h1>
            <?php if ($message !== ''): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <form method="POST">
                <label>Email<input type="email" name="email" value="<?= htmlspecialchars($email) ?>" autocomplete="email" required></label>
                <label>Mot de passe<input type="password" name="password" autocomplete="current-password" required></label>
                <button type="submit">Accéder à mon espace</button>
            </form>
            <a class="create" href="creer_compte.php">Créer un compte client</a>
        </main>
    </div>
</body>
</html>

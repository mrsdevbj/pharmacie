<?php
if (isClientLoggedIn()) {
    header('Location: /mon-compte');
    exit;
}

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!allowLogin('client', $email) || $email === '' || $password === '') {
        $message = 'Email ou mot de passe incorrect, ou trop de tentatives.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nom, email, password FROM client_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if (!$client || !password_verify($password, $client['password'])) {
            $message = 'Email ou mot de passe incorrect.';
        } else {
            $patientStmt = $pdo->prepare('SELECT id, password_hash FROM patient_accounts WHERE email = ? LIMIT 1');
            $patientStmt->execute([$email]);
            $patient = $patientStmt->fetch();

            session_regenerate_id(true);
            $_SESSION['client_id'] = (int) $client['id'];
            $_SESSION['client_patient_id'] = $patient && password_verify($password, $patient['password_hash']) ? (int) $patient['id'] : null;
            $_SESSION['client_nom'] = $client['nom'];
            $_SESSION['client_email'] = $email;
            $_SESSION['client_auth_version'] = hash('sha256',$client['password']);

            header('Location: /mon-compte');
            exit;
        }
    }
}

require APP_ROOT . '/app/Views/connexion_client.php';

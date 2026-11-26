<?php
if (isAdminLoggedIn()) {
    header('Location: /admin');
    exit;
}

$message = '';
$messageType = 'error';
$email = '';
$isForgotMode = isset($_GET['forgot']) && $_GET['forgot'] == '1';

if (!empty($_COOKIE['admin_remember_email'])) {
    $email = trim(strtolower((string) $_COOKIE['admin_remember_email']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isForgotMode || isset($_POST['action'])) {
        $message = 'Contactez le responsable de la pharmacie pour réinitialiser votre accès.';
    } else {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        if (loginAdmin($email, $password)) {
            if ($rememberMe) {
                rememberEmail($email);
            } else {
                rememberEmail('');
            }

            header('Location: /admin');
            exit;
        }

        $message = 'Email ou mot de passe incorrect.';
        $messageType = 'error';
    }
}

require APP_ROOT . '/app/Views/login.php';

<?php
require_once __DIR__ . '/db.php';

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email']);
}

function requireAdminAuth(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function loginAdmin(string $email, string $password): bool
{
    global $pdo;

    $email = trim(strtolower($email));
    if ($email === '' || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        return false;
    }

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];

    return true;
}

function logoutAdmin(): void
{
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }

    header('Location: login.php');
    exit;
}

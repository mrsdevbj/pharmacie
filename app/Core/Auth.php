<?php
function isAdminLoggedIn(): bool
{
    global $pdo;
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_auth_version'])) return false;
    $stmt=$pdo->prepare('SELECT password FROM admin_users WHERE id=? AND email=?');
    $stmt->execute([$_SESSION['admin_id'],$_SESSION['admin_email']]);
    $hash=$stmt->fetchColumn();
    return is_string($hash) && hash_equals($_SESSION['admin_auth_version'],hash('sha256',$hash));
}

function requireAdminAuth(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: /admin/connexion');
        exit;
    }
}

function loginAdmin(string $email, string $password): bool
{
    global $pdo;

    $email = trim(strtolower($email));
    if (!allowLogin('admin', $email) || $email === '' || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_auth_version'] = hash('sha256',$admin['password']);

    return true;
}

function logoutAdmin(): void
{
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
        setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>'/','secure'=>secureConnection(),'httponly'=>true,'samesite'=>'Lax']);
    }

    header('Location: /admin/connexion');
    exit;
}

function isClientLoggedIn(): bool {
    global $pdo;
    if (empty($_SESSION['client_id']) || empty($_SESSION['client_auth_version'])) return false;
    $stmt=$pdo->prepare('SELECT password FROM client_users WHERE id=? AND email=?');
    $stmt->execute([$_SESSION['client_id'],$_SESSION['client_email']??'']);
    $hash=$stmt->fetchColumn();
    return is_string($hash) && hash_equals($_SESSION['client_auth_version'],hash('sha256',$hash));
}

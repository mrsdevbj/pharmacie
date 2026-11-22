<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
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
    if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($email === '' || $newPassword === '' || $confirmPassword === '') {
            $message = 'Veuillez remplir tous les champs.';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin) {
                $message = 'Aucun compte admin trouvé pour cet email.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE admin_users SET password = ? WHERE email = ?');
                $update->execute([$hash, $email]);
                $message = 'Votre mot de passe a été réinitialisé avec succès.';
                $messageType = 'success';
                $isForgotMode = false;
                $email = '';
            }
        }
    } else {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        if (loginAdmin($email, $password)) {
            if ($rememberMe) {
                setcookie('admin_remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
            } else {
                setcookie('admin_remember_email', '', time() - 3600, '/');
            }

            header('Location: index.php');
            exit;
        }

        $message = 'Email ou mot de passe incorrect.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-login-body">
    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-brand-mark">Rx</span>
                <div>
                    <div class="login-brand-title">Pharmacie OLG</div>
                    <div class="login-brand-subtitle">Espace administration</div>
                </div>
            </div>

            <?php if ($isForgotMode): ?>
                <h1>Réinitialiser</h1>

                <?php if ($message !== ''): ?>
                    <div class="login-message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <input type="hidden" name="action" value="reset_password">

                    <div class="login-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" autocomplete="username" placeholder="Votre email" required>
                    </div>

                    <div class="login-field">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input id="new_password" type="password" name="new_password" placeholder="Entrez un nouveau mot de passe" required>
                    </div>

                    <div class="login-field">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input id="confirm_password" type="password" name="confirm_password" placeholder="Confirmez le mot de passe" required>
                    </div>

                    <button type="submit" class="login-btn">Valider</button>

                    <div class="login-links login-back-link">
                        <a href="login.php">Retour à la connexion</a>
                    </div>
                </form>
            <?php else: ?>
                <h1>Connexion</h1>

                <?php if ($message !== ''): ?>
                    <div class="login-message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="login-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" autocomplete="username" placeholder="Votre email" required>
                    </div>

                    <div class="login-field">
                        <label for="password">Mot de passe</label>
                        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Votre mot de passe" required>
                    </div>

                    <label class="remember-row">
                        <input type="checkbox" name="remember_me" <?= !empty($_COOKIE['admin_remember_email']) ? 'checked' : '' ?>>
                        <span>Se souvenir de moi</span>
                    </label>

                    <div class="login-links">
                        <a href="login.php?forgot=1">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="login-btn">Se connecter</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

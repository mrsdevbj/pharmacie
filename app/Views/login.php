<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="page-login-body">
    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <span class="login-brand-mark">Rx</span>
                <div>
                    <div class="login-brand-title">Pharmacie Oganla</div>
                    <div class="login-brand-subtitle">Espace administration</div>
                </div>
            </div>

            <?php if ($isForgotMode): ?>
                <h1>Réinitialiser</h1>

                <?php if ($message !== ''): ?>
                    <div class="login-message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <p>Contactez le responsable de la pharmacie pour réinitialiser votre accès.</p><div class="login-links"><a href="/admin/connexion">Retour à la connexion</a></div>
            <?php else: ?>
                <h1>Connexion</h1>

                <?php if ($message !== ''): ?>
                    <div class="login-message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="login-form"><?= csrfField() ?>
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
                        <a href="/admin/connexion?forgot=1">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="login-btn">Se connecter</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

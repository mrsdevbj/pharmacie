<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte client - Pharmacie Oganla</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #07130f;
            --panel: rgba(17, 42, 33, 0.92);
            --primary: #10b981;
            --primary-light: #6ee7b7;
            --text: #f0fdf4;
            --muted: #6ee7b7;
            --border: rgba(52, 211, 153, 0.22);
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 15%, rgba(16, 185, 129, 0.14), transparent 38%),
                radial-gradient(circle at 85% 85%, rgba(52, 211, 153, 0.08), transparent 45%),
                var(--bg);
        }
        .register-shell { width: min(470px, 100%); }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 14px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .register-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.38);
            backdrop-filter: blur(14px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }
        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-size: 1.1rem;
            font-weight: 800;
            box-shadow: 0 0 18px rgba(16, 185, 129, 0.35);
        }
        .brand-title { font-size: 1.05rem; font-weight: 800; }
        .brand-subtitle { color: var(--muted); font-size: 0.76rem; }
        h1 { margin: 0 0 6px; font-size: 1.55rem; }
        .intro { margin: 0 0 18px; color: #a7f3d0; font-size: 0.86rem; }
        .google-btn {
            width: 100%;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: white;
            color: #1f2937;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .google-g {
            width: 24px;
            height: 24px;
            display: grid;
            place-items: center;
            border: 2px solid #4285f4;
            border-right-color: #34a853;
            border-bottom-color: #fbbc05;
            border-radius: 50%;
            color: #ea4335;
            font-size: 0.78rem;
        }
        .divider {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 10px;
            margin: 18px 0;
            color: #86efac;
            font-size: 0.74rem;
        }
        .divider::before, .divider::after {
            content: '';
            height: 1px;
            background: var(--border);
        }
        .register-form { display: grid; gap: 12px; }
        .field { display: grid; gap: 6px; }
        label { font-size: 0.78rem; font-weight: 700; color: #d1fae5; }
        input {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(7, 19, 15, 0.78);
            color: white;
            padding: 0 13px;
            font-family: inherit;
            outline: none;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12); }
        input::placeholder { color: rgba(110, 231, 183, 0.48); }
        .submit-btn {
            min-height: 48px;
            margin-top: 4px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .submit-btn:hover { filter: brightness(1.08); }
        .message {
            border-radius: 9px;
            padding: 10px 12px;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .message.success { background: rgba(16, 185, 129, 0.14); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.28); }
        .message.error { background: rgba(159, 18, 57, 0.18); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.28); }
        @media (max-width: 520px) {
            body { padding: 16px; align-items: start; }
            .register-shell { margin-top: 12px; }
            .register-card { padding: 20px 16px; }
        }
    </style>
<link rel="stylesheet" href="/assets/css/client.css">
</head>
<body>
    <div class="register-shell">
        <a class="back-link" href="/">← Retour à la recherche</a>
        <main class="register-card">
            <div class="brand">
                <div class="brand-mark">Rx</div>
                <div>
                    <div class="brand-title">Pharmacie Oganla</div>
                    <div class="brand-subtitle">Création du compte client</div>
                </div>
            </div>

            <h1>Créer un compte client</h1>






            <?php if ($message !== ''): ?>
                <div class="message <?= htmlspecialchars($messageType) ?>" style="margin-bottom:12px;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" class="register-form" autocomplete="on"><?= csrfField() ?>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['account_csrf']) ?>">

                <div class="field">
                    <label for="nom">Nom complet *</label>
                    <input id="nom" type="text" name="nom" autocomplete="name" placeholder="Votre nom complet" value="<?= htmlspecialchars((string) ($_POST['nom'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="email">Adresse email *</label>
                    <input id="email" type="email" name="email" autocomplete="email" placeholder="nom@exemple.com" value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="telephone">Téléphone</label>
                    <input id="telephone" type="tel" name="telephone" autocomplete="tel" placeholder="Votre numéro de téléphone" value="<?= htmlspecialchars((string) ($_POST['telephone'] ?? '')) ?>">
                </div>

                <div class="field">
                    <label for="password">Mot de passe *</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" minlength="12" placeholder="Au moins 12 caractères" required>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirmer le mot de passe *</label>
                    <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="12" placeholder="Retapez votre mot de passe" required>
                </div>

                <button type="submit" class="submit-btn">Créer mon compte</button>
            </form>
        </main>
    </div>


</body>
</html>

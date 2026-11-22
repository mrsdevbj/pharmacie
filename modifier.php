<?php
require_once 'db.php';
require_once 'auth.php';
requireAdminAuth();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM medicament WHERE id = ?');
$stmt->execute([$id]);
$med = $stmt->fetch();

if (!$med) {
    die('Médicament introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $quantite = max(0, (int) ($_POST['quantite'] ?? 0));
    $isActif = isset($_POST['is_actif']) ? 1 : 0;

    if ($nom === '') {
        $message = ['type' => 'error', 'text' => 'Le nom du médicament est obligatoire.'];
    } else {
        $stmt = $pdo->prepare('UPDATE medicament SET nom = ?, quantite = ?, prix = NULL, is_actif = ? WHERE id = ?');
        $stmt->execute([$nom, $quantite, $isActif, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un médicament</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-modify-body">
    <div class="modern-page-shell">
        <header class="top-strip">
            <div class="top-strip-inner">
                <div class="top-brand">
                    <span class="add-icon">✎</span>
                    <span>Modifier un produit</span>
                </div>
                <nav class="top-links">
                    <a href="index.php">Dashboard</a>
                    <a href="ajouter.php">Ajouter</a>
                </nav>
            </div>
        </header>

        <main class="modify-page">
            <div class="modify-card">
                <?php if (!empty($message)): ?>
                    <div class="message <?= htmlspecialchars($message['type']) ?>"><?= htmlspecialchars($message['text']) ?></div>
                <?php endif; ?>

                <h1>Modifier : <?= htmlspecialchars($med['nom']) ?></h1>
                <form method="POST" class="modify-form">
                    <div class="field-group">
                        <label for="nom">Nom du médicament</label>
                        <input id="nom" type="text" name="nom" value="<?= htmlspecialchars($med['nom']) ?>" required>
                    </div>

                    <div class="field-group">
                        <label for="quantite">Quantité</label>
                        <input id="quantite" type="number" name="quantite" min="0" value="<?= (int) $med['quantite'] ?>" required>
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="is_actif" <?= (int) $med['is_actif'] ? 'checked' : '' ?>>
                        <span>Produit actif / disponible</span>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">Mettre à jour</button>
                        <a href="index.php" class="secondary-btn">Retour</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <span>© 2026 Pharmacie OLG</span>
            <span>Gestion des médicaments</span>
        </div>
    </footer>
</body>
</html>
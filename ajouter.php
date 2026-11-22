<?php
require_once 'db.php';
require_once 'auth.php';
requireAdminAuth();

$nomSaisi = '';
$quantiteSaisie = 1;
$isActifSaisi = true;
$message = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomSaisi = trim($_POST['nom'] ?? '');
    $quantiteSaisie = (int) ($_POST['quantite'] ?? 0);
    $isActifSaisi = isset($_POST['is_actif']);

    if ($nomSaisi === '') {
        $message = ['type' => 'error', 'text' => 'Le nom du médicament est obligatoire.'];
    } elseif ($quantiteSaisie <= 0) {
        $message = ['type' => 'error', 'text' => 'La quantité doit être supérieure à 0.'];
    } else {
        // Vérifier si le médicament existe déjà
        $existing = $pdo->prepare('SELECT id, quantite, is_actif FROM medicament WHERE LOWER(TRIM(nom)) = LOWER(TRIM(?)) LIMIT 1');
        $existing->execute([$nomSaisi]);
        $med = $existing->fetch();

        if ($med) {
            // Additionner la quantité au stock existant
            $newQty = (int) $med['quantite'] + $quantiteSaisie;
            $newState = $isActifSaisi ? 1 : ($newQty > 0 ? 1 : 0);

            $updateStmt = $pdo->prepare('UPDATE medicament SET quantite = ?, is_actif = ? WHERE id = ?');
            $updateStmt->execute([$newQty, $newState, $med['id']]);

            if (function_exists('setFlash')) {
                setFlash('Le médicament existait déjà : quantité additionnée.');
            }
        } else {
            // Créer un nouveau médicament
            $stmt = $pdo->prepare('INSERT INTO medicament (nom, quantite, prix, is_actif) VALUES (?, ?, NULL, ?)');
            $stmt->execute([$nomSaisi, $quantiteSaisie, $isActifSaisi ? 1 : 0]);

            if (function_exists('setFlash')) {
                setFlash('Le médicament a bien été ajouté.');
            }
        }

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
    <title>Ajouter médicament</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-add-product">
    <div class="top-strip">
        <div class="top-strip-inner">
            <div class="top-brand">
                <span class="add-icon">＋</span>
                <span>Ajouter un produit</span>
            </div>
            <nav class="top-links">
                <a href="index.php">Dashboard</a>
                <a href="importer.php">Importer</a>
            </nav>
        </div>
    </div>

    <main class="form-page">
        <div class="form-panel">
            <?php if (!empty($message)): ?>
                <div class="message <?= htmlspecialchars($message['type']) ?>"><?= htmlspecialchars($message['text']) ?></div>
            <?php endif; ?>

            <h1>Ajouter un médicament</h1>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Si le médicament existe déjà, la quantité saisie sera ajoutée au stock actuel.</p>

            <form method="POST" class="medicine-form">
                <div class="field-group">
                    <label for="nom">Nom du médicament</label>
                    <input id="nom" type="text" name="nom" value="<?= htmlspecialchars($nomSaisi) ?>" placeholder="Ex : Paracétamol" required>
                </div>

                <div class="field-group">
                    <label for="quantite">Quantité à ajouter</label>
                    <input id="quantite" type="number" name="quantite" min="1" value="<?= max(1, (int) $quantiteSaisie) ?>" required>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_actif" <?= $isActifSaisi ? 'checked' : '' ?>>
                    <span>Marquer comme produit actif / disponible</span>
                </label>

                <div class="form-actions">
                    <button type="submit" class="primary-btn">Enregistrer</button>
                    <a href="index.php" class="secondary-btn">Retour</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <span>© 2026 Pharmacie OLG</span>
            <span>Gestion des médicaments</span>
        </div>
    </footer>
</body>
</html>
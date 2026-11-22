<?php
require_once 'db.php';
require_once 'auth.php';
requireAdminAuth();

function normalizeText($value): string
{
    return trim(str_replace(["\r", "\n"], ' ', (string) $value));
}

function parseCsvRows(string $filePath): array
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) return [];
    $rows = [];
    while (($data = fgetcsv($handle, 10000, ';')) !== false) {
        $rows[] = array_map('normalizeText', $data);
    }
    fclose($handle);
    return $rows;
}

function parseCsvRowsWithComma(string $filePath): array
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) return [];
    $rows = [];
    while (($data = fgetcsv($handle, 10000, ',')) !== false) {
        $rows[] = array_map('normalizeText', $data);
    }
    fclose($handle);
    return $rows;
}

function parseXlsxRows(string $filePath): array
{
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return [];

    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        $xml = simplexml_load_string($sharedStringsXml);
        if ($xml !== false) {
            foreach ($xml->si as $si) {
                $text = '';
                foreach ($si->t as $t) $text .= (string) $t;
                $sharedStrings[] = $text;
            }
        }
    }

    $rows = [];
    $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($worksheetXml !== false) {
        $xml = simplexml_load_string($worksheetXml);
        if ($xml !== false) {
            foreach ($xml->sheetData->row as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    $type = (string) $cell['t'];
                    $value = '';
                    if ($type === 'inlineStr') {
                        $value = normalizeText((string) $cell->is->t);
                    } elseif ($type === 's') {
                        $value = $sharedStrings[(int) $cell->v] ?? '';
                    } elseif ($type === 'b') {
                        $value = ((string) $cell->v === '1') ? '1' : '0';
                    } else {
                        $value = normalizeText((string) $cell->v);
                    }
                    $values[] = $value;
                }
                if (!empty($values)) $rows[] = $values;
            }
        }
    }
    $zip->close();
    return $rows;
}

function extractMedicationData(array $row): ?array
{
    if (count($row) < 2) return null;
    $nom = normalizeText($row[0]);
    $quantiteText = normalizeText($row ?? '0');

    if ($nom === '') return null;
    $header = strtolower($nom);
    if (in_array($header, ['nom', 'nom du medicament', 'medicament', 'produit'])) return null;

    $quantite = (int) preg_replace('/[^0-9-]/', '', $quantiteText);

    return [
        'nom' => $nom,
        'quantite' => max(0, $quantite),
        'is_actif' => max(0, $quantite) > 0 ? 1 : 0,
    ];
}

$message = ['type' => 'success', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = ['type' => 'error', 'text' => 'Erreur lors du téléchargement du fichier.'];
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tmpFile = $file['tmp_name'];
        $rows = [];

        if ($ext === 'csv') {
            $rows = parseCsvRows($tmpFile);
            if (empty($rows)) $rows = parseCsvRowsWithComma($tmpFile);
        } elseif ($ext === 'xlsx' || $ext === 'xls') {
            $rows = parseXlsxRows($tmpFile);
        } else {
            $message = ['type' => 'error', 'text' => 'Format non pris en charge. Utilisez CSV ou Excel (.xlsx).'];
        }

        if (!empty($rows) && empty($message['text'])) {
            $imported = 0;
            $updated = 0;
            foreach ($rows as $row) {
                $data = extractMedicationData($row);
                if ($data === null) continue;

                $existing = $pdo->prepare('SELECT id, quantite FROM medicament WHERE LOWER(TRIM(nom)) = LOWER(TRIM(?)) LIMIT 1');
                $existing->execute([$data['nom']]);
                $med = $existing->fetch();

                if ($med) {
                    // Synchronisation inventaire global : mise à jour directe de la quantité exacte du fichier
                    $updateStmt = $pdo->prepare('UPDATE medicament SET quantite = ?, is_actif = ? WHERE id = ?');
                    $updateStmt->execute([$data['quantite'], $data['is_actif'], $med['id']]);
                    $updated++;
                } else {
                    // Création si le produit n'existait pas encore
                    $insertStmt = $pdo->prepare('INSERT INTO medicament (nom, quantite, prix, is_actif) VALUES (?, ?, NULL, ?)');
                    $insertStmt->execute([$data['nom'], $data['quantite'], $data['is_actif']]);
                    $imported++;
                }
            }

            $message['text'] = "Synchronisation inventaire terminée : $imported produit(s) créé(s), $updated produit(s) mis à jour.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synchronisation Stock</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-import-body">
    <div class="import-shell">
        <div class="import-card">
            <header class="import-header">
                <div class="import-brand">
                    <span class="import-brand-mark">⇪</span>
                    <span>Synchro Stock</span>
                </div>
                <nav class="import-links">
                    <a href="index.php">Dashboard</a>
                    <a href="ajouter.php">Ajouter</a>
                </nav>
            </header>

            <main class="import-main">
                <?php if ($message['text'] !== ''): ?>
                    <div class="import-message <?= htmlspecialchars($message['type']) ?>"><?= htmlspecialchars($message['text']) ?></div>
                <?php endif; ?>

                <h1>Importer l'inventaire</h1>
                <p class="lead">Glissez le fichier exporté de la pharmacie (CSV / Excel). Le stock se mettra à jour en fonction de leur inventaire réel.</p>

                <form method="POST" enctype="multipart/form-data" class="import-form">
                    <label class="upload-box" for="import_file">
                        <input id="import_file" type="file" name="import_file" accept=".csv,.xlsx,.xls" required>
                        <span class="upload-placeholder">Sélectionner le fichier d'inventaire</span>
                        <span class="upload-action">Choisir un fichier</span>
                    </label>

                    <div class="import-actions">
                        <button type="submit" class="primary-import-btn">Synchroniser</button>
                        <a href="index.php" class="secondary-import-btn">Retour</a>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <span>© 2026 Pharmacie OLG</span>
            <span>Gestion des médicaments</span>
        </div>
    </footer>
</body>
</html>
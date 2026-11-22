<?php
require_once 'db.php';
require_once 'auth.php';
requireAdminAuth();

// Sécurité pour getFlash et isProductAvailable au cas où elles ne seraient pas définies
$flash = function_exists('getFlash') ? getFlash() : null;
if (!function_exists('isProductAvailable')) {
    function isProductAvailable($m) {
        return ((int)($m['quantite'] ?? 0)) > 0 && ((int)($m['is_actif'] ?? 1)) === 1;
    }
}

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM medicament';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE nom LIKE ?';
    $params[] = "%$search%";
}

$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = $pdo->query('SELECT COUNT(*) AS total, SUM(CASE WHEN quantite > 0 AND is_actif = 1 THEN 1 ELSE 0 END) AS disponible, SUM(CASE WHEN quantite = 0 OR is_actif = 0 THEN 1 ELSE 0 END) AS rupture FROM medicament')->fetch(PDO::FETCH_ASSOC);
$totalCount = max(1, (int)($stats['total'] ?? 1));
$dispoCount = (int)($stats['disponible'] ?? 0);
$rupteurCount = (int)($stats['rupture'] ?? 0);
$tauxDispo = round(($dispoCount / $totalCount) * 100);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration • Pharmacie OLG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="brand-box">
                <div class="brand-logo">Rx</div>
                <div>
                    <div class="brand-title">Pharmacie OLG</div>
                    <div class="brand-subtitle">Espace Officine</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <span class="nav-icon">◈</span>
                    Tableau de bord
                </a>
                <a href="ajouter.php" class="nav-item">
                    <span class="nav-icon">+</span>
                    Ajouter un produit
                </a>
                <a href="importer.php" class="nav-item">
                    <span class="nav-icon">↑</span>
                    Importer CSV
                </a>
            </nav>

            <div class="sidebar-footer-card">
                <div class="sf-title">Disponibilité stock</div>
                <div class="sf-progress">
                    <div class="sf-progress-bar" style="width: <?= $tauxDispo ?>%;"></div>
                </div>
                <div class="sf-sub"><?= $tauxDispo. '%' ?> des références actives</div>
            </div>
        </aside>

        <div class="workspace">
            <header class="workspace-header">
                <div class="header-left">
                    <div class="header-badge">OFFICINE LIVE</div>
                    <div class="header-title">Tableau de bord des stocks</div>
                </div>
                <div class="header-right">
                    <a href="logout.php" class="header-btn secondary-btn">Déconnexion</a>
                    <a href="ajouter.php" class="header-btn primary-btn">+ Nouveau médicament</a>
                </div>
            </header>

            <main class="dashboard-main">
                <?php if ($flash && !empty($flash['message'])): ?>
                    <div class="flash-overlay">
                        <div class="success-banner success-popup" role="alert">
                            <div class="success-icon">✓</div>
                            <div class="success-text"><?= htmlspecialchars($flash['message']) ?></div>
                            <button type="button" class="flash-close" aria-label="Fermer">OK</button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- KPI / STATS CARDS -->
                <div class="stats-grid">
                    <div class="kpi-card kpi-total">
                        <div class="kpi-meta">
                            <span class="kpi-label">Catalogue total</span>
                            <span class="kpi-ic">💊</span>
                        </div>
                        <div class="kpi-value"><?= (int)($stats['total'] ?? 0) ?></div>
                        <span class="kpi-sub">Références enregistrées</span>
                    </div>

                    <div class="kpi-card kpi-dispo">
                        <div class="kpi-meta">
                            <span class="kpi-label">Disponibles</span>
                            <span class="kpi-ic">🟢</span>
                        </div>
                        <div class="kpi-value"><?= $dispoCount ?></div>
                        <span class="kpi-sub positive-sub">Prêts à la délivrance</span>
                    </div>

                    <div class="kpi-card kpi-rupture">
                        <div class="kpi-meta">
                            <span class="kpi-label">En rupture / Inactifs</span>
                            <span class="kpi-ic">🔴</span>
                        </div>
                        <div class="kpi-value"><?= $rupteurCount ?></div>
                        <span class="kpi-sub negative-sub">Nécessite réapprovisionnement</span>
                    </div>
                </div>

                <!-- CONTENT PANEL -->
                <div class="content-panel">
                    <div class="panel-head">
                        <div>
                            <h2>Inventaire des médicaments</h2>
                            <p class="panel-desc">Gérez les quantités, le statut et l'accessibilité guichet.</p>
                        </div>
                        <form method="GET" class="search-box">
                            <div class="search-input-wrap">
                                <span class="search-icon-symbol">🔍</span>
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher par nom commercial...">
                            </div>
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                            <?php if ($search !== ''): ?>
                                <a href="index.php" class="btn btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Nom du médicament</th>
                                    <th style="width: 140px;">Stock</th>
                                    <th style="width: 150px;">Disponibilité</th>
                                    <th style="width: 180px; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($medicaments)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-box">
                                                <div class="empty-emoji">📦</div>
                                                <div class="empty-title">Aucun médicament trouvé</div>
                                                <div class="empty-text">Modifiez vos critères de recherche ou ajoutez un nouveau produit.</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($medicaments as $m): ?>
                                        <tr>
                                            <td><span class="id-badge">#<?= (int)$m['id'] ?></span></td>
                                            <td>
                                                <div class="med-name"><?= htmlspecialchars($m['nom']) ?></div>
                                                <?php if (!empty($m['prix'])): ?>
                                                    <div class="med-price"><?= number_format((float)$m['prix'], 0, ',', ' ') ?> FCFA</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="stock-pill <?= ((int)$m['quantite'] <= 5 ? 'stock-low' : 'stock-ok') ?>">
                                                    <?= (int)$m['quantite'] ?> unités
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (isProductAvailable($m)): ?>
                                                    <span class="badge badge-success">En stock</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Indisponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-group">
                                                    <a href="modifier.php?id=<?= (int)$m['id'] ?>" class="btn btn-warning edit-btn">Modifier</a>
                                                    <a href="supprimer.php?id=<?= (int)$m['id'] ?>" class="btn btn-danger delete-btn">Supprimer</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal confirmation -->
    <div id="customConfirmModal" class="custom-confirm-overlay" aria-hidden="true">
        <div class="custom-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <div class="custom-confirm-icon" aria-hidden="true">🗑</div>
            <div id="confirmTitle" class="custom-confirm-title">Supprimer cet élément ?</div>
            <div id="confirmMessage" class="custom-confirm-message">Cette action est définitive. L'élément sera retiré du référentiel.</div>
            <div class="custom-confirm-actions">
                <button type="button" id="cancelDeleteBtn" class="confirm-secondary-btn">Annuler</button>
                <button type="button" id="confirmDeleteBtn" class="confirm-primary-btn">Oui, supprimer</button>
            </div>
        </div>
    </div>

    <script>
        // Gestion modale flash
        const flashCloseButtons = document.querySelectorAll('.flash-close');
        flashCloseButtons.forEach(btn => btn.addEventListener('click', () => {
            const overlay = btn.closest('.flash-overlay');
            if (overlay) overlay.style.display = 'none';
        }));

        // Gestion modale de confirmation : suppression ou modification
        const modal = document.getElementById('customConfirmModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const cancelBtn = document.getElementById('cancelDeleteBtn');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmIcon = document.querySelector('.custom-confirm-icon');
        let pendingUrl = null;
        let currentAction = 'delete';

        function openConfirmModal(action, url) {
            currentAction = action;
            pendingUrl = url;

            if (action === 'edit') {
                confirmTitle.textContent = 'Modifier cet élément ?';
                confirmMessage.textContent = 'Voulez-vous vraiment modifier ce médicament ? Vous pourrez ensuite enregistrer les changements.';
                confirmBtn.textContent = 'Oui, modifier';
                confirmIcon.textContent = '✎';
                confirmIcon.style.background = '#eaf7ff';
                confirmIcon.style.color = '#1287d4';
            } else {
                confirmTitle.textContent = 'Supprimer cet élément ?';
                confirmMessage.textContent = 'Cette action est définitive. L\'élément sera retiré du référentiel.';
                confirmBtn.textContent = 'Oui, supprimer';
                confirmIcon.textContent = '🗑';
                confirmIcon.style.background = '#fce5eb';
                confirmIcon.style.color = '#e43a5f';
            }

            modal.classList.add('visible');
            modal.setAttribute('aria-hidden', 'false');
        }

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openConfirmModal('delete', btn.getAttribute('href'));
            });
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openConfirmModal('edit', btn.getAttribute('href'));
            });
        });

        cancelBtn.addEventListener('click', () => {
            modal.classList.remove('visible');
            modal.setAttribute('aria-hidden', 'true');
            pendingUrl = null;
            currentAction = 'delete';
        });

        confirmBtn.addEventListener('click', () => {
            if (pendingUrl) {
                window.location.href = pendingUrl;
            }
        });
    </script>
</body>
</html>
<?php $title='Tableau de bord des stocks';$nav='/admin';$headerAction=true;require __DIR__.'/layouts/admin_start.php'; ?>

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


                <div class="content-panel">
                    <div class="panel-head">
                        <div>
                            <h2>Inventaire des médicaments</h2>
                            <p class="panel-desc"></p>
                        </div>
                        <form method="GET" class="search-box">
                            <div class="search-input-wrap">
                                <span class="search-icon-symbol">🔍</span>
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher par nom commercial...">
                            </div>
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                            <?php if ($search !== ''): ?>
                                <a href="/admin" class="btn btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Nom du médicament</th>
                                    <th style="width: 140px;">Stock disponible</th>
                                    <th style="width: 150px;">Disponibilité</th>
                                    <th style="width: 290px; text-align: right;">Actions</th>
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
                                                <?php if ($m['prix'] !== null): ?>
                                                    <div class="med-price"><?= number_format((float)$m['prix'], 2, ',', ' ') ?> FCFA</div>
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
                                                    <a href="/admin/medicaments/<?= (int)$m['id'] ?>/stock" class="btn btn-primary">+ Stock</a><a href="/admin/medicaments/<?= (int)$m['id'] ?>/modifier" class="btn btn-warning edit-btn">Modifier</a>
                                                    <a href="/admin/medicaments/<?= (int)$m['id'] ?>/supprimer" class="btn btn-danger delete-btn">Supprimer</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

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
        function postAction(url) {
            const f = document.createElement('form'); f.method = 'POST'; f.action = url;
            const i = document.createElement('input'); i.type = 'hidden'; i.name = '_csrf'; i.value = <?= json_encode(csrfToken()) ?>; f.appendChild(i); document.body.appendChild(f); f.submit();
        }

        const flashCloseButtons = document.querySelectorAll('.flash-close');
        flashCloseButtons.forEach(btn => btn.addEventListener('click', () => {
            const overlay = btn.closest('.flash-overlay');
            if (overlay) overlay.style.display = 'none';
        }));

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
                if (currentAction === 'delete') { postAction(pendingUrl); } else { window.location.href = pendingUrl; }
            }
        });
    </script>

<?php require __DIR__.'/layouts/admin_end.php'; ?>

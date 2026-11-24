<?php
require_once 'db.php';

$query = trim($_GET['q'] ?? '');
$results = [];
$searched = !empty($query);

if ($searched) {
    try {
        $stmt = $pdo->prepare("SELECT id, nom, prix, quantite, is_actif FROM medicament WHERE nom LIKE ? ORDER BY nom ASC");
        $stmt->execute(['%' . $query . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $results = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Pharmacie OLG - Vérification de stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.35);
            --bg: #07130f;
            --card-bg: rgba(17, 42, 33, 0.75);
            --card-hover: rgba(22, 54, 43, 0.9);
            --text: #f0fdf4;
            --text-muted: #6ee7b7;
            --border: rgba(52, 211, 153, 0.18);
            --border-hover: rgba(52, 211, 153, 0.45);
            --success-bg: linear-gradient(135deg, rgba(6, 78, 59, 0.9), rgba(4, 120, 87, 0.6));
            --success-text: #6ee7b7;
            --danger-bg: linear-gradient(135deg, rgba(76, 5, 25, 0.9), rgba(159, 18, 57, 0.5));
            --danger-text: #fda4af;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(16, 185, 129, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(52, 211, 153, 0.08) 0%, transparent 50%);
            color: var(--text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* HEADER */
        header {
            background: rgba(7, 19, 15, 0.82);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
            gap: 1rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 1.15rem;
            white-space: nowrap;
        }
        .pharmacy-cross {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
            flex-shrink: 0;
        }
        .pharmacy-cross::before {
            content: '';
            width: 18px;
            height: 5px;
            background: white;
            position: absolute;
            border-radius: 99px;
        }
        .pharmacy-cross::after {
            content: '';
            width: 5px;
            height: 18px;
            background: white;
            position: absolute;
            border-radius: 99px;
        }

        .header-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #34d399;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.25);
            padding: 0.35rem 0.75rem;
            border-radius: 99px;
            white-space: nowrap;
        }
        .dot-pulse {
            width: 6px;
            height: 6px;
            background: #34d399;
            border-radius: 50%;
            box-shadow: 0 0 6px #34d399;
            animation: pulseGlow 2s infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* HERO SECTION */
        .hero {
            padding: 2.5rem 0 1.5rem;
            text-align: center;
            overflow: hidden;
        }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #34d399;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.25);
            padding: 0.3rem 0.75rem;
            border-radius: 99px;
            margin-bottom: 1rem;
        }

        .hero-ticker {
            width: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            position: relative;
            background: rgba(16, 185, 129, 0.05);
            padding: 0.5rem 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .hero h1 {
            font-size: 1.5rem;
            font-weight: 800;
            white-space: nowrap;
            display: inline-block;
            color: #34d399;
            text-shadow: 0 0 15px rgba(52, 211, 153, 0.5);
            animation: tickerScroll 15s linear infinite;
        }

        @keyframes tickerScroll {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }

        .hero p {
            font-size: 0.95rem;
            color: var(--text-muted);
            max-width: 480px;
            margin: 1rem auto 0;
            line-height: 1.45;
            padding: 0 0.5rem;
        }

        .create-account-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 0.9rem;
            border: 1px solid rgba(52, 211, 153, 0.3);
            border-radius: 10px;
            background: rgba(16, 185, 129, 0.12);
            color: #6ee7b7;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: 0.2s ease;
        }
        .create-account-btn:hover {
            background: #10b981;
            color: #fff;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .login-account-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 0.9rem;
            border: 1px solid rgba(52, 211, 153, 0.18);
            border-radius: 10px;
            background: transparent;
            color: #d1fae5;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .login-account-btn:hover {
            border-color: rgba(52, 211, 153, 0.42);
            background: rgba(16, 185, 129, 0.08);
        }

        /* MAIN SEARCH & CONTAINER */
        main {
            flex: 1;
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
            padding: 0 1rem 3rem;
        }

        .search-container {
            width: 100%;
        }
        .search-form {
            display: flex;
            width: 100%;
            gap: 0.65rem;
            align-items: stretch;
        }
        .search-box {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            padding: 0.45rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 15px 35px -15px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            display: flex;
            width: 100%;
            gap: 0.5rem;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .search-box:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 25px var(--primary-glow), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        .search-input-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            padding-left: 0.75rem;
            gap: 0.6rem;
            min-width: 0;
        }
        .search-input-wrap svg {
            color: #34d399;
            flex-shrink: 0;
            width: 18px;
            height: 18px;
        }
        .search-box input {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 0.95rem;
            color: #fff;
            font-family: inherit;
            padding: 0.5rem 0;
            font-weight: 500;
        }
        .search-box input::placeholder {
            color: rgba(110, 231, 183, 0.45);
        }
        .search-box button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            padding: 0.75rem 1.35rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 5px 15px -4px rgba(16, 185, 129, 0.5);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .search-box button:hover {
            background: linear-gradient(135deg, #34d399, #10b981);
        }

        /* RESULTS CARDS */
        .results {
            margin-top: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.25rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card:hover {
            background: var(--card-hover);
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        .card-left h3 {
            font-size: 1.2rem;
            color: #fff;
            font-weight: 700;
            word-break: break-word;
            letter-spacing: -0.01em;
        }
        .price {
            font-weight: 700;
            color: #34d399;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(16, 185, 129, 0.12);
            padding: 0.2rem 0.65rem;
            border-radius: 99px;
            border: 1px solid rgba(52, 211, 153, 0.25);
        }

        .msg-box {
            padding: 0.9rem 1.15rem;
            border-radius: 13px;
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
        }
        .msg-available {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid rgba(52, 211, 153, 0.35);
        }
        .msg-out {
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid rgba(244, 63, 94, 0.35);
        }
        .msg-icon {
            font-size: 1.15rem;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .empty-state {
            background: var(--card-bg);
            border: 1px dashed var(--border);
            border-radius: 16px;
            padding: 2.5rem 1.25rem;
            text-align: center;
            color: var(--text-muted);
        }

        /* FOOTER */
        footer {
            text-align: center;
            padding: 1.25rem;
            font-size: 0.75rem;
            color: rgba(110, 231, 183, 0.6);
            border-top: 1px solid var(--border);
            background: rgba(7, 19, 15, 0.6);
            backdrop-filter: blur(10px);
            margin-top: auto;
        }

        /* MEDIA QUERIES RESPONSIVE MOBILE */
        @media (max-width: 480px) {
            header {
                padding: 0.75rem 1rem;
            }
            .brand span {
                font-size: 1rem;
            }
            .pharmacy-cross {
                width: 32px;
                height: 32px;
            }
            .hero {
                padding: 2rem 0 1.2rem;
            }
            .hero h1 {
                font-size: 1.25rem;
            }
            .search-box {
                flex-direction: column;
                padding: 0.5rem;
                gap: 0.5rem;
                background: transparent;
                border: none;
                box-shadow: none;
            }
            .search-input-wrap {
                background: var(--card-bg);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 0.75rem;
                width: 100%;
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            }
            .search-box button {
                width: 100%;
                padding: 0.85rem;
                border-radius: 12px;
            }
            .header-actions {
                gap: 0.35rem;
            }
            .header-badge {
                display: none;
            }
            .create-account-btn,
            .login-account-btn {
                min-height: 36px;
                padding: 0 0.65rem;
                font-size: 0.72rem;
            }
        }
    </style>
</head>
<body>

    <header>
        <a href="recherche_patient.php" class="brand">
            <div class="pharmacy-cross"></div>
            <span>Pharmacie OLG</span>
        </a>
        <div class="header-actions">
            <?php if (!empty($_SESSION['client_id'])): ?>
                <a href="espace_client.php" class="login-account-btn">Mon espace</a>
            <?php else: ?>
                <a href="connexion_client.php" class="login-account-btn">Se connecter</a>
            <?php endif; ?>
            <a href="creer_compte.php" class="create-account-btn">Créer un compte client</a>
            <div class="header-badge">
                <span class="dot-pulse"></span>
                En ligne
            </div>
        </div>
    </header>

    <div class="hero">
        <div class="hero-pill">✨ Intelligence officinale</div>
        <div class="hero-ticker">
            <h1>+ + + TROUVEZ VOTRE MÉDICAMENT - DISPONIBILITÉ EN TEMPS RÉEL + + + </h1>
        </div>
        <p>Interrogez notre base en temps réel pour localiser et valider la disponibilité exacte de vos produits.</p>
    </div>

    <main>
        <div class="search-container">
            <form method="GET" action="" class="search-form" id="searchForm">
                <div class="search-box">
                    <div class="search-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" name="q" placeholder="Ex: Paracétamol, Amoxicilline..." value="<?= htmlspecialchars($query) ?>" required autofocus>
                    </div>
                    <button type="submit">Rechercher</button>
                </div>
            </form>
        </div>

        <div class="results">
            <?php if ($searched): ?>
                <?php if (empty($results)): ?>
                    <div class="empty-state">
                        <p style="font-weight: 700; color: #fff; font-size: 1rem; margin-bottom: 0.3rem;">🔍 Aucun résultat pour « <?= htmlspecialchars($query) ?> »</p>
                        <p style="font-size: 0.85rem;">Vérifiez l'orthographe du principe actif ou présentez-vous au comptoir de garde.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $med): ?>
                        <div class="card">
                            <div class="card-top">
                                <div class="card-left">
                                    <h3><?= htmlspecialchars($med['nom']) ?></h3>
                                    <?php if (!empty($med['prix'])): ?>
                                        <div class="price">🏷️ <?= formatMoney($med['prix']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ((int)$med['quantite'] > 0): ?>
                                    <div class="msg-box msg-available">
                                        <span class="msg-icon">🟢</span>
                                        <div><strong>Actif :</strong> Le médicament est bien disponible dans notre pharmacie, vous pouvez passer nous voir pour en acheter.</div>
                                    </div>
                                <?php else: ?>
                                    <div class="msg-box msg-out">
                                        <span class="msg-icon">🔴</span>
                                        <div><strong>Inactif :</strong> Désolé le médicament n'est pas disponible actuellement dans notre pharmacie, nous sommes vraiment désolés. Vous pouvez chercher ailleurs. 🤝</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> Pharmacie OLG. Données indicatives sous réserve de délivrance en officine.
    </footer>

</body>
</html>
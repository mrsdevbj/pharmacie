<?php


$flash = getFlash();
$search = substr(trim($_GET['q'] ?? ''),0,190);
$medicaments = Medicine::search($pdo,$search);
$stats = Medicine::stats($pdo);
$totalCount = max(1, (int)($stats['total'] ?? 1));
$dispoCount = (int)($stats['disponible'] ?? 0);
$rupteurCount = (int)($stats['rupture'] ?? 0);
$tauxDispo = round(($dispoCount / $totalCount) * 100);

require APP_ROOT . '/app/Views/index.php';

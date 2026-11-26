<?php
$query = substr(trim($_GET['q'] ?? ''), 0, 190);
$results = [];
$searched = !empty($query);

if ($searched) {
    try {
        $results = Medicine::search($pdo,$query,true);
    } catch (PDOException $e) {
        $results = [];
    }
}

require APP_ROOT . '/app/Views/recherche_patient.php';

<?php
require_once 'db.php';
require_once 'auth.php';
requireAdminAuth();

$id = $_GET['id'] ?? null;
if ($id !== null && $id !== '') {
    $stmt = $pdo->prepare('DELETE FROM medicament WHERE id = ?');
    $stmt->execute([(int) $id]);
}

header('Location: index.php');
exit;
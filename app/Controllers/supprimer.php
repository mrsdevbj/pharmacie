<?php


$id = $_GET['id'] ?? null;
if ($id !== null && $id !== '') {
    $stmt = $pdo->prepare('DELETE FROM medicament WHERE id = ?');
    try { $stmt->execute([(int) $id]); } catch (PDOException $e) { setFlash('Ce médicament est lié à une réservation et ne peut pas être supprimé.', 'error'); }
}

header('Location: /admin');
exit;
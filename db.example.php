<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost'; // Remplace par ton vrai hôte (ex: sql302.infinityfree.com)
$db   = 'olg';
$user = 'root'; // Ton identifiant MySQL / compte
$pass = ''; // Ton mot de passe InfinityFree

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];git init
git add .
git commit -m "Premier commit"
git branch -M main
git remote add origin https://github.com/mrsdevbj/pharmacie.git
git push -u origin main

try {
    $dsn = "mysql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

function formatMoney($value): string
{
    return number_format((float) $value, 2, ',', ' ') . ' FCFA';
}

function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash_message'])) {
        return null;
    }

    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $flash;
}

function isProductAvailable(array $med): bool
{
    return (int) $med['quantite'] > 0 && (int) $med['is_actif'] === 1;
}
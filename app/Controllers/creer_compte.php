<?php
$message = '';
$messageType = 'error';

if (!isset($_SESSION['account_csrf'])) {
    $_SESSION['account_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['account_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $message = 'La session du formulaire a expiré. Rechargez la page.';
    } else {
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $email = trim(strtolower((string) ($_POST['email'] ?? '')));
        $telephone = trim((string) ($_POST['telephone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($email) > 190 || strlen($nom) > 100 || strlen($telephone) > 30 || $nom === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $message = 'Veuillez remplir tous les champs obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Veuillez renseigner une adresse email valide.';
        } elseif (strlen($password) < 12 || strlen($password) > 72) {
            $message = 'Le mot de passe doit contenir entre 12 et 72 caractères.';
        } elseif ($password !== $confirmPassword) {
            $message = 'Les deux mots de passe ne correspondent pas.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id FROM client_users WHERE email = ? UNION SELECT id FROM patient_accounts WHERE email = ?');
                $stmt->execute([$email, $email]);

                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    $message = 'Un compte existe déjà avec cette adresse email.';
                } else {
                    $passwordHash = hashPassword($password);
                    $insert = $pdo->prepare('INSERT INTO client_users (nom, email, telephone, password) VALUES (?, ?, ?, ?)');
                    $insert->execute([
                        $nom,
                        $email,
                        $telephone !== '' ? $telephone : null,
                        $passwordHash,
                    ]);
                    $clientId = (int) $pdo->lastInsertId();

                    $patientStmt = $pdo->prepare('SELECT id FROM patient_accounts WHERE email = ? LIMIT 1');
                    $patientStmt->execute([$email]);
                    $patient = $patientStmt->fetch();

                    if ($patient) { throw new RuntimeException('Compte existant'); }
                    $patientInsert = $pdo->prepare('INSERT INTO patient_accounts (full_name, phone, email, password_hash) VALUES (?, ?, ?, ?)');
                    $patientInsert->execute([$nom, $telephone !== '' ? $telephone : null, $email, $passwordHash]);
                    $patientId = (int) $pdo->lastInsertId();
                    $pdo->commit();
                    session_regenerate_id(true);
                    $_SESSION['client_id'] = $clientId;
                    $_SESSION['client_patient_id'] = $patientId;
                    $_SESSION['client_nom'] = $nom;
                    $_SESSION['client_email'] = $email;
                    $_SESSION['client_auth_version'] = hash('sha256',$passwordHash);
                    $message = 'Votre compte client a été créé avec succès.';
                    $messageType = 'success';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = 'Impossible de créer le compte pour le moment.';
            }
        }
    }
}

require APP_ROOT . '/app/Views/creer_compte.php';

<?php
require_once __DIR__ . '/db.php';

unset($_SESSION['client_id'], $_SESSION['client_patient_id'], $_SESSION['client_nom'], $_SESSION['client_email']);
header('Location: recherche_patient.php');
exit;

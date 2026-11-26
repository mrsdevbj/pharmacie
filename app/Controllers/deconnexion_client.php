<?php
unset($_SESSION['client_id'], $_SESSION['client_patient_id'], $_SESSION['client_nom'], $_SESSION['client_email'], $_SESSION['client_auth_version']);
session_regenerate_id(true);
header('Location: /');
exit;

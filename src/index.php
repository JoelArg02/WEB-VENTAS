<?php
require_once 'auth/session.php';
date_default_timezone_set('America/Guayaquil');
// Si está logueado, redirigir al dashboard
if (SessionManager::isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Si no está logueado, redirigir al login
header('Location: auth/login.php');
exit();
?>

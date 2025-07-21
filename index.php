<?php
// Página de inicio - redirige al login o dashboard según el estado de la sesión
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /src/dashboard_functional.php');
} else {
    header('Location: /src/login.php');
}
exit;
?>

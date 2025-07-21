<?php
require_once '../auth/session.php';

SessionManager::logout();
header('Location: /auth/login.php?message=logout');
exit();
?>

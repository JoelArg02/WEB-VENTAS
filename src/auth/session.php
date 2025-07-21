<?php
class SessionManager {
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function login($userId, $userName, $userEmail, $userRole) {
        self::startSession();
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['login_time'] = time();
    }
    
    public static function logout() {
        self::startSession();
        session_unset();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /auth/login.php');
            exit();
        }
    }
    
    public static function getUserData() {
        self::startSession();
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
    
    public static function redirectIfLoggedIn() {
        if (self::isLoggedIn()) {
            header('Location: /dashboard.php');
            exit();
        }
    }
}
?>

<?php
require_once '../auth/session.php';
require_once '../auth/auth.php';

SessionManager::redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        $auth = new Auth();
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            $user = $result['user'];
            SessionManager::login(
                $user['id'], 
                $user['name'], 
                $user['email'], 
                $user['role']
            );
            header('Location: /dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

$title = 'Iniciar Sesión';
include '../includes/header.php';
?>

<body class="min-h-screen flex items-center justify-center p-4" style="background: url('/assets/bg.png') no-repeat center center fixed; background-size: cover;">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-sm">
        <div class="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center bg-white">
            <img src="/assets/logo.png" alt="Logo" class="w-full h-full object-contain rounded-full">
        </div>
        
        <h1 class="text-2xl font-bold text-center mb-6">Iniciar Sesión</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <input type="email" 
                   name="email" 
                   placeholder="Correo electrónico" 
                   class="w-full p-3 border rounded-lg" 
                   required 
                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
            
            <input type="password" 
                   name="password" 
                   placeholder="Contraseña" 
                   class="w-full p-3 border rounded-lg" 
                   required>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700">
                Entrar
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Usuario de prueba: admin@test.com | Contraseña: admin123
            </p>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

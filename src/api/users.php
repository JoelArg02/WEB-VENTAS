<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

require_once '../auth/session.php';
require_once '../auth/permissions.php';
require_once '../models/UserManager.php';

SessionManager::requireLogin();
$userData = SessionManager::getUserData();


while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        PermissionManager::requirePermission($userData['role'], 'users');

        $userManager = new UserManager();
        $users = $userManager->getAllUsers();

        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        PermissionManager::requirePermission($userData['role'], 'create_user');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Error: No se recibieron datos válidos. Verifique el formato JSON.');
        }

        // Validaciones detalladas de campos requeridos
        $errors = [];

        if (empty($input['name'])) {
            $errors[] = 'El campo "Nombre" es obligatorio';
        } elseif (strlen($input['name']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        }

        if (empty($input['email'])) {
            $errors[] = 'El campo "Email" es obligatorio';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del email no es válido';
        }

        if (empty($input['role'])) {
            $errors[] = 'El campo "Rol" es obligatorio';
        } elseif (!in_array($input['role'], ['admin', 'vendedor', 'bodega', 'cajero'])) {
            $errors[] = 'El rol seleccionado no es válido';
        }

        if (empty($input['password'])) {
            $errors[] = 'El campo "Contraseña" es obligatorio';
        } elseif (strlen($input['password']) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if (!empty($errors)) {
            throw new Exception('Errores de validación: ' . implode('. ', $errors));
        }

        $userManager = new UserManager();


        try {
            $existingUser = $userManager->getUserByEmail($input['email']);
            if ($existingUser) {
                throw new Exception('Error: Ya existe un usuario registrado con el email "' . $input['email'] . '"');
            }
        } catch (Exception $e) {

            if (strpos($e->getMessage(), 'no encontrado') === false) {
                throw $e;
            }
        }

        try {
            $result = $userManager->createUser($input);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario "' . $input['name'] . '" creado exitosamente con rol de ' . $input['role']
                ]);
            } else {
                throw new Exception('Error: No se pudo crear el usuario. Verifique que todos los datos sean correctos.');
            }
        } catch (Exception $e) {

            $errorMessage = $e->getMessage();

            if (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'email') !== false) {
                throw new Exception('Error: El email "' . $input['email'] . '" ya está registrado en el sistema');
            } elseif (strpos($errorMessage, 'Data too long') !== false) {
                throw new Exception('Error: Uno de los campos excede la longitud máxima permitida');
            } elseif (strpos($errorMessage, 'cannot be null') !== false) {
                throw new Exception('Error: Falta información requerida en la base de datos');
            } else {
                throw new Exception('Error al crear usuario: ' . $errorMessage);
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        PermissionManager::requirePermission($userData['role'], 'edit_user');

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;

        if (!$id) {
            throw new Exception('ID de usuario requerido');
        }

        $userManager = new UserManager();
        $result = $userManager->updateUser($id, $input);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Usuario actualizado exitosamente' : 'Error al actualizar usuario'
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        PermissionManager::requirePermission($userData['role'], 'delete_user');

        $id = $_GET['id'] ?? null;

        if (!$id) {
            throw new Exception('ID de usuario requerido');
        }

        $userManager = new UserManager();
        $result = $userManager->deleteUser($id);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Usuario eliminado exitosamente' : 'Error al eliminar usuario'
        ]);
    } else {
        throw new Exception('Método no permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

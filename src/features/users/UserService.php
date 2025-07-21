<?php
require_once __DIR__ . '/../../models/UserManager.php';
require_once __DIR__ . '/UserValidator.php';

class UserService {
    private $userManager;
    
    public function __construct() {
        $this->userManager = new UserManager();
    }
    
    public function getAllUsers() {
        return $this->userManager->getAllUsers();
    }
    
    public function createUser($input) {
        $errors = UserValidator::validateCreateUser($input);
        
        if (!empty($errors)) {
            throw new Exception('Errores de validación: ' . implode('. ', $errors));
        }
        
        try {
            $existingUser = $this->userManager->getUserByEmail($input['email']);
            if ($existingUser) {
                throw new Exception('Error: Ya existe un usuario registrado con el email "' . $input['email'] . '"');
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'no encontrado') === false) {
                throw $e;
            }
        }
        
        $result = $this->userManager->createUser($input);
        
        if (!$result) {
            throw new Exception('Error: No se pudo crear el usuario.');
        }
        
        return [
            'success' => true,
            'message' => 'Usuario "' . $input['name'] . '" creado exitosamente con rol de ' . $input['role']
        ];
    }
    
    public function updateUser($id, $input) {
        if (!$id) {
            throw new Exception('ID de usuario requerido');
        }
        
        $result = $this->userManager->updateUser($id, $input);
        
        return [
            'success' => $result,
            'message' => $result ? 'Usuario actualizado exitosamente' : 'Error al actualizar usuario'
        ];
    }
    
    public function deleteUser($id) {
        if (!$id) {
            throw new Exception('ID de usuario requerido');
        }
        
        $result = $this->userManager->deleteUser($id);
        
        return [
            'success' => $result,
            'message' => $result ? 'Usuario eliminado exitosamente' : 'Error al eliminar usuario'
        ];
    }
}

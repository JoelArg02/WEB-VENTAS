<?php
class UserValidator {
    public static function validateCreateUser($input) {
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
        
        return $errors;
    }
}

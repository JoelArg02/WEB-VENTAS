<?php
class ProductValidator {
    public static function validateCreateProduct($input) {
        $errors = [];
        
        if (empty($input['name'])) {
            $errors[] = 'El campo "Nombre" es obligatorio';
        } elseif (strlen($input['name']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        }
        
        if (!isset($input['price']) || $input['price'] === '') {
            $errors[] = 'El campo "Precio" es obligatorio';
        } elseif (!is_numeric($input['price']) || floatval($input['price']) <= 0) {
            $errors[] = 'El precio debe ser un número mayor a 0';
        }
        
        if (!isset($input['stock']) || $input['stock'] === '') {
            $errors[] = 'El campo "Stock" es obligatorio';
        } elseif (!is_numeric($input['stock']) || intval($input['stock']) < 0) {
            $errors[] = 'El stock debe ser un número mayor o igual a 0';
        }
        
        if (empty($input['category_id'])) {
            $errors[] = 'El campo "Categoría" es obligatorio';
        }
        
        if (!empty($input['expiry_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', $input['expiry_date']);
            if (!$date || $date->format('Y-m-d') !== $input['expiry_date']) {
                $errors[] = 'El formato de fecha de vencimiento no es válido';
            } elseif ($date < new DateTime('today')) {
                $errors[] = 'La fecha de vencimiento no puede ser anterior a hoy';
            }
        }
        
        return $errors;
    }
}

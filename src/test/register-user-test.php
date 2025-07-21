<?php
require_once '../config/database.php';
require_once '../auth/session.php';

// Configurar headers solo para requests AJAX
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json');
    http_response_code(200);
    exit();
}

class UserRegistration {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function registerUser($data) {
        try {
            // Validar datos requeridos
            $requiredFields = ['name', 'email', 'password', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "El campo {$field} es requerido"
                    ];
                }
            }
            
            // Validar formato de email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Formato de email inválido'
                ];
            }
            
            // Validar que el email no exista
            $checkQuery = "SELECT id FROM users WHERE email = :email";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':email', $data['email']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un usuario con este email'
                ];
            }
            
            // Validar longitud de contraseña
            if (strlen($data['password']) < 6) {
                return [
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ];
            }
            
            // Validar rol
            $validRoles = ['admin', 'vendedor', 'bodega'];
            if (!in_array($data['role'], $validRoles)) {
                return [
                    'success' => false,
                    'message' => 'Rol inválido. Roles permitidos: ' . implode(', ', $validRoles)
                ];
            }
            
            // Hashear contraseña de forma segura
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Preparar datos para inserción
            $name = trim($data['name']);
            $email = trim(strtolower($data['email']));
            $phone = isset($data['phone']) ? trim($data['phone']) : null;
            $role = $data['role'];
            $status = isset($data['status']) ? (int)$data['status'] : 1;
            $reason = isset($data['reason']) ? trim($data['reason']) : null;
            
            // Insertar usuario
            $query = "INSERT INTO users (name, email, phone, password, role, status, reason) 
                     VALUES (:name, :email, :phone, :password, :role, :status, :reason)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':reason', $reason);
            
            if ($stmt->execute()) {
                $userId = $this->conn->lastInsertId();
                
                return [
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente',
                    'user_id' => $userId,
                    'user_data' => [
                        'id' => $userId,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'role' => $role,
                        'status' => $status
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al insertar usuario en la base de datos'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error del servidor: ' . $e->getMessage()
            ];
        }
    }
    
    public function getAllUsers() {
        try {
            $query = "SELECT id, name, email, phone, role, status, reason FROM users ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $users = $stmt->fetchAll();
            
            return [
                'success' => true,
                'users' => $users
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ];
        }
    }
}

// Procesar solicitud
$userReg = new UserRegistration();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Configurar header JSON solo para POST
    header('Content-Type: application/json');
    
    // Obtener datos JSON del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Si no hay datos JSON, usar datos de formulario
    if (!$data) {
        $data = $_POST;
    }
    
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se recibieron datos'
        ]);
        exit();
    }
    
    $result = $userReg->registerUser($data);
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Endpoint para listar usuarios (opcional)
    if (isset($_GET['action']) && $_GET['action'] === 'list') {
        header('Content-Type: application/json');
        $result = $userReg->getAllUsers();
        echo json_encode($result);
    } else {
        // Mostrar formulario de prueba
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Registro de Usuario - Prueba</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 min-h-screen p-4">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold mb-6 text-center">Registrar Nuevo Usuario</h1>
                
                <div id="message" class="hidden mb-4 p-4 rounded-lg"></div>
                
                <form id="registerForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" id="name" name="name" required 
                               class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" id="email" name="email" required 
                               class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" id="phone" name="phone" 
                               class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                        <input type="password" id="password" name="password" required minlength="6"
                               class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                        <select id="role" name="role" required 
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccionar rol</option>
                            <option value="admin">Administrador</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="bodega">Bodega</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select id="status" name="status" 
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón (opcional)</label>
                        <textarea id="reason" name="reason" rows="2"
                                  class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500"
                                  placeholder="Motivo del estado o notas adicionales"></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Registrar Usuario
                    </button>
                </form>
                
                <div class="mt-6 pt-4 border-t">
                    <h3 class="font-medium mb-2">Ejemplos de uso con cURL:</h3>
                    <div class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">
                        <pre>curl -X POST http://localhost:8000/test/register-user-test.php \
-H "Content-Type: application/json" \
-d '{
  "name": "Nuevo Usuario",
  "email": "nuevo@test.com",
  "password": "password123",
  "role": "vendedor",
  "phone": "1234567890"
}'</pre>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button onclick="listUsers()" 
                            class="w-full bg-gray-600 text-white p-2 rounded-lg hover:bg-gray-700">
                        Ver Todos los Usuarios
                    </button>
                </div>
                
                <div id="usersList" class="mt-4"></div>
            </div>
            
            <script>
                document.getElementById('registerForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());
                    
                    try {
                        const response = await fetch('/test/register-user-test.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        });
                        
                        const result = await response.json();
                        
                        const messageDiv = document.getElementById('message');
                        messageDiv.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
                        
                        if (result.success) {
                            messageDiv.classList.add('bg-green-100', 'text-green-700');
                            messageDiv.textContent = result.message;
                            document.getElementById('registerForm').reset();
                        } else {
                            messageDiv.classList.add('bg-red-100', 'text-red-700');
                            messageDiv.textContent = result.message;
                        }
                        
                        messageDiv.classList.remove('hidden');
                        
                    } catch (error) {
                        console.error('Error:', error);
                        const messageDiv = document.getElementById('message');
                        messageDiv.classList.remove('hidden', 'bg-green-100', 'text-green-700');
                        messageDiv.classList.add('bg-red-100', 'text-red-700');
                        messageDiv.textContent = 'Error de conexión';
                    }
                });
                
                async function listUsers() {
                    try {
                        const response = await fetch('/test/register-user-test.php?action=list');
                        const result = await response.json();
                        
                        const listDiv = document.getElementById('usersList');
                        
                        if (result.success) {
                            let html = '<h3 class="font-medium mb-2">Usuarios Registrados:</h3>';
                            html += '<div class="bg-gray-50 p-3 rounded text-sm">';
                            
                            if (result.users.length === 0) {
                                html += '<p>No hay usuarios registrados.</p>';
                            } else {
                                result.users.forEach(user => {
                                    html += `<div class="border-b py-2">
                                        <strong>${user.name}</strong> (${user.email})<br>
                                        <span class="text-gray-600">Rol: ${user.role} | Estado: ${user.status ? 'Activo' : 'Inactivo'}</span>
                                    </div>`;
                                });
                            }
                            
                            html += '</div>';
                            listDiv.innerHTML = html;
                        } else {
                            listDiv.innerHTML = '<div class="bg-red-100 text-red-700 p-3 rounded">' + result.message + '</div>';
                        }
                        
                    } catch (error) {
                        console.error('Error:', error);
                        document.getElementById('usersList').innerHTML = '<div class="bg-red-100 text-red-700 p-3 rounded">Error al cargar usuarios</div>';
                    }
                }
            </script>
        </body>
        </html>
        <?php
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>

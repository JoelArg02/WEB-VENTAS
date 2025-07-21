<?php
class UsersComponent {
    private $userData;
    private $userRole;
    private $permissions;
    
    public function __construct($userData, $permissions) {
        $this->userData = $userData;
        $this->userRole = $userData['role'];
        $this->permissions = $permissions;
    }
    
    public function render() {
        ob_start();
        ?>
        <div class="space-y-6" id="usersTab">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h2>
                <?php if ($this->permissions['create_user']): ?>
                <button onclick="openCreateUserModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    + Nuevo Usuario
                </button>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Lista de Usuarios</h3>
                <div id="usersLoading" class="flex justify-center items-center h-32">
                    <div class="text-gray-500">Cargando usuarios...</div>
                </div>
                <div class="overflow-x-auto hidden" id="usersTable">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Rol</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Estado</th>
                                <?php if ($this->permissions['edit_user'] || $this->permissions['delete_user']): ?>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="usersTableBody">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
            async function loadUsersData() {
                try {
                    const result = await apiRequest('/api/users.php');
                    
                    if (result.success) {
                        renderUsersTable(result.data);
                        document.getElementById('usersLoading').classList.add('hidden');
                        document.getElementById('usersTable').classList.remove('hidden');
                    } else {
                        showError('Error al cargar usuarios: ' + result.message);
                    }
                } catch (error) {
                    showError('Error de conexión al cargar usuarios');
                    console.error('Users error:', error);
                }
            }
            
            function renderUsersTable(users) {
                const tbody = document.getElementById('usersTableBody');
                let html = '';
                
                users.forEach(user => {
                    const statusClass = user.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const statusText = user.status ? 'Activo' : 'Inactivo';
                    
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">${user.name}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${user.email}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">${user.role}</td>
                            <td class="px-4 py-2 text-sm"><span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span></td>`;
                            
                    <?php if ($this->permissions['edit_user'] || $this->permissions['delete_user']): ?>
                    html += '<td class="px-4 py-2 text-sm">';
                    <?php if ($this->permissions['edit_user']): ?>
                    html += `<button onclick="editUser(${user.id})" class="text-blue-600 hover:text-blue-800 mr-2">Editar</button>`;
                    <?php endif; ?>
                    <?php if ($this->permissions['delete_user']): ?>
                    html += `<button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-800">Eliminar</button>`;
                    <?php endif; ?>
                    html += '</td>';
                    <?php endif; ?>
                    
                    html += '</tr>';
                });
                
                tbody.innerHTML = html;
            }
            
            async function editUser(id) {
                showMessage('Función de edición en desarrollo', 'info');
            }
            
            async function deleteUser(id) {
                if (!confirm('¿Estás seguro de que deseas eliminar este usuario?')) return;
                
                try {
                    const result = await apiRequest(`/api/users.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    if (result.success) {
                        showMessage('Usuario eliminado exitosamente', 'success');
                        loadUsersData();
                    }
                } catch (error) {
                    showError('Error al eliminar usuario');
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>

    <script>
        // Scripts globales aquí
        function logout() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                window.location.href = '/auth/logout.php';
            }
        }
        
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleUserDropdown') === -1) {
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    </script>
</body>
</html>

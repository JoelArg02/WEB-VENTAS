# Sistema de Ventas con PHP y MySQL

## 🚀 Características

- **Autenticación segura** con PHP Sessions
- **Control de acceso** - Solo usuarios autenticados pueden acceder al dashboard
- **Dashboard responsivo** con TailwindCSS
- **Gestión de usuarios, productos, categorías y ventas**
- **Base de datos MySQL** con datos de prueba incluidos
- **Containerizado** con Docker Compose

## 📋 Requisitos

- Docker
- Docker Compose

## ⚡ Instalación Rápida

1. **Clonar y navegar al proyecto**
   ```bash
   cd WEB-VENTAS
   ```

2. **Iniciar el sistema**
   ```bash
   docker-compose up -d --build
   ```

3. **Acceder a la aplicación**
   - Aplicación: http://localhost:8000
   - phpMyAdmin: http://localhost:8080

## 👤 Credenciales de Prueba

- **Email**: admin@test.com
- **Contraseña**: admin123

## 📁 Estructura del Proyecto

```
WEB-VENTAS/
├── src/                     # Código PHP
│   ├── index.php           # Página inicial (redirige según autenticación)
│   ├── dashboard.php       # Panel principal (requiere login)
│   ├── auth/               # Sistema de autenticación
│   │   ├── login.php      # Página de inicio de sesión
│   │   ├── logout.php     # Cerrar sesión
│   │   ├── session.php    # Manejo de sesiones
│   │   └── auth.php       # Lógica de autenticación
│   ├── config/            # Configuración
│   │   └── database.php   # Conexión a base de datos
│   └── includes/          # Componentes comunes
│       ├── header.php     # Header HTML común
│       └── footer.php     # Footer y scripts comunes
├── db/                     # Base de datos
│   └── init-db.sql        # Estructura e datos iniciales
├── docker-compose.yml      # Configuración de contenedores
├── Dockerfile             # Imagen PHP personalizada
└── apache-config.conf     # Configuración Apache
```

## 🔐 Sistema de Autenticación

### Características de seguridad:
- **Contraseñas hasheadas** con `password_hash()`
- **Sesiones PHP** para mantener estado de login
- **Protección de rutas** - Redirect automático si no hay sesión
- **Logout seguro** que destruye toda la sesión

### Flujo de autenticación:
1. Usuario visita cualquier URL del sitio
2. Si no hay sesión activa → Redirect a `/auth/login.php`
3. Tras login exitoso → Redirect a `/dashboard.php`
4. Todas las páginas del dashboard verifican autenticación

## 🎯 Funcionalidades del Dashboard

- **Dashboard principal**: Resumen de ventas, productos con stock bajo, métricas
- **Gestión de usuarios**: CRUD completo de usuarios del sistema
- **Gestión de productos**: Inventario, precios, categorías
- **Gestión de categorías**: Organización de productos
- **Registro de ventas**: Historial y seguimiento de ventas

## 🗄️ Base de Datos

### Tablas principales:
- `users` - Usuarios del sistema con roles y autenticación
- `categories` - Categorías de productos
- `products` - Inventario de productos
- `sales` - Registro de ventas realizadas

### Datos de prueba incluidos:
- 3 usuarios (admin, vendedor, cajero)
- 4 categorías de productos
- 7 productos de ejemplo
- Contraseñas: `admin123` para todos los usuarios de prueba

## 🐳 Configuración Docker

### Servicios incluidos:
- **web**: PHP 8.2 + Apache con PDO MySQL
- **mysql**: MySQL 8.0 con base de datos inicializada
- **phpmyadmin**: Interfaz web para gestión de BD

### Puertos:
- `8000`: Aplicación web
- `8080`: phpMyAdmin  
- `3306`: MySQL (interno)

## 🛠️ Personalización

Para modificar la configuración de la base de datos, edita:
- `docker-compose.yml` - Variables de entorno
- `src/config/database.php` - Parámetros de conexión

## 🔍 Troubleshooting

1. **Error de conexión a BD**: Espera unos segundos a que MySQL termine de inicializar
2. **Permisos de archivos**: Asegúrate que Docker tenga acceso a la carpeta del proyecto
3. **Puerto ocupado**: Cambia los puertos en `docker-compose.yml` si 8000 u 8080 están en uso

## 📝 Desarrollo

Para añadir nuevas funcionalidades:
1. Usa `SessionManager::requireLogin()` en páginas que requieran autenticación
2. Accede a datos del usuario con `SessionManager::getUserData()`
3. Los componentes comunes están en `includes/`

¡El sistema está listo para usar! 🎉

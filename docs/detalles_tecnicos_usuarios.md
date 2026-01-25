# 📘 Detalles Técnicos: Módulo de Gestión de Usuarios

## 1. ¿Qué se hizo?
Se desarrolló un módulo completo CRUD (Crear, Leer, Actualizar, Borrar -soft-) para la administración de usuarios del sistema Solufeed, integrándose perfectamente con la estética y arquitectura existente ("Rural-Premium" y PHP Nativo).

### Funcionalidades Clave:
- **Listado de Usuarios**: Vista con filtros avanzados (Tipo, Estado, Búsqueda).
- **Creación de Usuarios**: Formulario validado para Altas de Admin y Personal de Campo.
- **Edición de Perfiles**: Modificación de datos y cambio seguro de contraseñas.
- **Gestión de Estado**: Activación/Desactivación rápida de usuarios.

## 2. ¿Dónde quedó ubicado?

El módulo reside en una nueva carpeta dentro de la estructura de administración, aislada pero integrada:

**Ruta física:** `c:\xampp\htdocs\solufeed\admin\usuarios\`

**Archivos Principales:**
- **`listar.php`**: El "Home" del módulo (Tabla de usuarios).
- **`crear.php`**: Formulario de alta.
- **`editar.php`**: Formulario de edición.
- **`toggle_estado.php`**: Lógica de backend para activar/desactivar.

**Acceso en la Aplicación:**
Se modificó el archivo `includes/header.php` para añadir una **nueva entrada en el menú lateral** bajo la sección "Gestión". Solo visible para Administradores.

## 3. ¿Cómo se implementó? (Arquitectura Técnica)

La implementación sigue estrictamente los patrones de diseño ya presentes en Solufeed para garantizar compatibilidad y estabilidad.

### A. Backend (PHP Puro + PDO)
- **Reutilización de Conexión**: Se utiliza `config/database.php` para obtener la conexión PDO existente (`getConnection()`). No se crearon nuevas conexiones.
- **Seguridad (Guards)**: Todos los archivos inician con `verificarAdmin()` (importado de `includes/functions.php`), protegiendo el módulo contra accesos no autorizados o de personal de campo.
- **Sentencias Preparadas**: Todas las consultas SQL utilizan `prepare()` y `execute()` para prevenir inyección SQL completamente.
- **Contraseñas**: Se utiliza `password_hash()` (Bcrypt) al crear/editar y `password_verify()` en el login.

### B. Base de Datos (MySQL)
Se creó una nueva tabla `usuario` independiente para no interferir con tablas operativas (lotes, insumos).
- **Estructura**: `id_usuario`, `nombre`, `email` (UNIQUE), `password_hash`, `tipo` (ENUM), `activo`.
- **Admin por Defecto**: El script de migración asegura que siempre exista al menos un admin (`admin@solufeed.com`) para evitar bloqueos del sistema.

### C. Frontend (HTML5 + CSS3)
- **Estilo "Rural-Premium"**: Se utilizaron las variables CSS globales (`var(--primary)`, `var(--bg-glass)`) definidas en `assets/css/styles.css` para mantener la identidad visual.
- **Componentes**:
    - *Glassmorphism* en tarjetas y contenedores.
    - *Badges* (Etiquetas) de colores para estados y roles.
    - *Iconos* nativos (Emojis) para mantener la carga ligera.
- **Responsive**: Diseño *Mobile-First*. La tabla se adapta con scroll horizontal en móviles sin romper el layout.

## 4. Resumen de Archivos

| Archivo | Función |
| :--- | :--- |
| `admin/usuarios/listar.php` | Controlador y Vista del listado general. |
| `admin/usuarios/crear.php` | Formulario y lógica de inserción (INSERT). |
| `admin/usuarios/editar.php` | Formulario y lógica de actualización (UPDATE). |
| `admin/usuarios/toggle_estado.php` | Script lógico (sin vista) para cambio de estado. |
| `database/crear_tabla_usuarios.sql` | Script SQL para crear la estructura de datos. |

---
*Documentación generada para Solufeed v1.0 - Enero 2026*

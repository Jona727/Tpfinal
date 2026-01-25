# 📝 Resumen de Cambios: Feature Usuarios y Establecimientos

Esta rama (`feature/usuarios-establecimientos`) introduce una reestructuración importante en la gestión de usuarios y la organización lógica del feedlot.

## 🚀 Nuevas Funcionalidades

### 1. Gestión de Establecimientos (`admin/establecimientos/`)
- Módulo completo para gestionar **Campos** físicos (ej: Campo Norte, Sur).
- Dashboard con métricas por campo (animales, lotes).
- **Asignación Masiva de Lotes**: Permite mover múltiples lotes a un campo específico desde `admin/establecimientos/gestionar_lotes.php`.

### 2. Visibilidad de Operarios
- **Filtrado de Lotes**:
    - Los usuarios tipo `CAMPO` ahora **SOLO ven los lotes asignados** a ellos.
    - Los `ADMIN` siguen viendo todo.
- **Asignación Rápida**: Nueva vista `admin/usuarios/asignar_lotes.php` (botón 🐮 en listado de usuarios) para asignar lotes a operarios mediante checkboxes.
- **Listado de Lotes**: Ahora muestra qué operarios están asignados a cada lote directamente en la tabla principal (`admin/lotes/listar.php`).

### 3. Mejoras en UX/UI
- **Búsqueda en Vivo (AJAX)**: Implementada en el listado de usuarios. Filtra sin recargar la página.
- **Limpieza**: Se eliminó la asignación de lotes del formulario "Editar Usuario" para simplificar la vista, moviéndola a la vista dedicada.

## 🛠️ Cambios Técnicos Relevantes

### Base de Datos
- Tabla nueva: `usuario_tropa` (Relación N:M Operario-Lote).
- Tabla nueva/verificada: `campo` (Entidad Establecimiento).

### Archivos Clave Modificados
- `admin/campo/index.php`: Consulta filtrada por `usuario_tropa`.
- `admin/alimentaciones/registrar.php` y `admin/pesadas/registrar.php`: Dropsdowns filtrados por permisos de usuario.
- `includes/header.php`: Nuevos elementos de menú.

## 🧪 Cómo probar (Merge Request Review)

1. **Migraciones**: Ejecutar los scripts SQL en `database/` o asegurarse que las tablas existan.
2. **Rol Operario**: 
   - Asignar lotes a un operario.
   - Loguearse como ese operario.
   - Verificar que solo ve esos lotes en el Hub y en los selectores.
3. **Establecimientos**:
   - Crear un campo nuevo.
   - Asignarle lotes usando la herramienta de asignación masiva.

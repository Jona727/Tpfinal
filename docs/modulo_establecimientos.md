# 🏭 Módulo: Gestión de Establecimientos

## Resumen
Este módulo permite administrar las ubicaciones físicas (Campos) donde se alojan los lotes de animales. Permite segmentar el feedlot en unidades lógicas como "Campo Norte", "Campo Sur", "Predio Alquiler", etc.

## Ubicación
- **Ruta**: `admin/establecimientos/`
- **Acceso**: Menú Lateral > Configuración > Establecimientos.

## Funcionalidades

### 1. Listar y Dashboard
Muestra todos los campos registrados con métricas clave en tiempo real:
- Total de Lotes Activos.
- Total de Cabezas (suma de animales en lotes activos).
- Ubicación/Referencia.

### 2. Gestión de Lotes (Asignación Masiva) `[NUEVO]`
Permite mover lotes de un establecimiento a otro de forma masiva.
- Haz clic en **🐮 Asignar Lotes** en la tarjeta del campo.
- Selecciona los lotes que quieres traer a este campo.
- **Nota**: Si seleccionas un lote que ya está en otro campo, el sistema lo moverá automáticamente al nuevo campo.

### 3. Crear Campo
Permite dar de alta un nuevo establecimiento. Solo requiere un nombre.

### 4. Editar Campo
Permite cambiar el nombre, ubicación y estado.
- **Activo/Inactivo**: Si se desactiva un campo, este dejará de aparecer en los selectores al crear nuevos lotes, pero mantendrá la integridad histórica de los lotes ya creados.

## Estructura de Datos
Tabla: `campo`
- `id_campo` (INT, PK)
- `nombre` (VARCHAR)
- `ubicacion` (VARCHAR, Opcional)
- `activo` (BOOL)

## Integración
Este módulo alimenta el selector de "Campo" en el formulario de creación de Lotes (`admin/lotes/crear.php`), permitiendo asociar cada tropa a su ubicación física real.

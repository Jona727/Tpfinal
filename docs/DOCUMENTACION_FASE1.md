# Documentación Técnica: Fase 1 - PWA y Cache Offline

## 1. Introducción
El objetivo de esta fase es dotar a **Solufeed** de capacidades progresivas (PWA) para permitir que los usuarios en zonas rurales (sin internet) puedan acceder a la interfaz y consultar datos cacheados.

## 2. Tecnologías Implementadas
- **Service Workers**: Scripts que corren en segundo plano interceptando peticiones de red.
- **Cache API**: Almacenamiento local de recursos (HTML, CSS, JS e imágenes).
- **Web App Manifest**: Configuración para instalación en dispositivos móviles.

## 3. Estrategia Detallada
Se ha implementado una estrategia híbrida en el archivo `sw.js`:

### A. Pre-cacheo Estático (Install Event)
Al instalarse el SW por primera vez, se descargan y guardan los archivos críticos (Shell de la App) y las **vistas operativas del campo**. Esto asegura que el operario tenga acceso al Mixer o Pesadas aunque nunca haya entrado en ellas previamente:
- `main.css`, `offline.html`, Fuentes.
- `admin/campo/index.php` (Dashboard Campo).
- `admin/alimentaciones/registrar.php` (Cargar Mixer).
- `admin/pesadas/registrar.php` (Registrar Pesada).
- `admin/campo/consultar_lotes.php` (Consulta de Lotes).

### B. Gestión Dinámica (Fetch Event)
Se utiliza una lógica de **Network First fallando a Cache**:
1. El SW intercepta la petición.
2. Intenta ir a buscar el archivo al servidor (Red).
3. Si lo consigue, guarda una copia actualizada en cache y la entrega al usuario.
4. Si falla (estás offline), busca el archivo en la cache local.
5. Si no hay internet y el archivo no está en cache, entrega `offline.html`.

## 4. Archivos Clave
- `sw.js`: Lógica del Service Worker.
- `offline.html`: Página de respaldo.
- `header.php`: Script de registro y detección de actualizaciones.

## 5. Instrucciones de Prueba
1. Abrir la consola del navegador (F12).
2. Pestaña **Application** -> **Service Workers**. Verificar que esté "Activated".
3. Pestaña **Network** -> Activar el Check **Offline**.
4. Recargar la página. La aplicación debería seguir visible.

# 🔍 Patrón de Búsqueda en Vivo (AJAX) - Solufeed

Este documento describe el patrón estándar para implementar búsquedas en vivo sin recargar la página en los listados de Solufeed.

## 🎯 Objetivo
Mejorar la experiencia de usuario permitiendo filtrar tablas instantáneamente al escribir o seleccionar opciones, sin reflushear la pantalla completa.

## 🏗️ Implementación

### 1. Backend (PHP)
El archivo PHP debe detectar si la petición es AJAX y retornar solo la tabla HTML.

```php
// Al final de la lógica de negocio, antes de pintar el HTML completo:

// AJAX HANDLER: Retorna solo la tabla
if (isset($_GET['ajax'])) {
    if (count($resultados) > 0) {
        ?>
        <table>
           <!-- ... THEAD y TBODY ... -->
        </table>
        <?php
    } else {
        // Mostrar Empty State
        echo '<div class="empty-state">...</div>';
    }
    exit; // ¡Importante terminar la ejecución aquí!
}
```

### 2. Frontend (HTML Structure)
La tabla debe estar envuelta en un contenedor con clase única para poder reemplazar su contenido.

```html
<!-- Filtros -->
<form class="filters-grid">
    <input type="text" name="busqueda" ...>
    <select name="filtro_x" ...>
</form>

<!-- Contenedor de la Tabla (Este es el que se actualiza) -->
<div class="tabla-contenedor">
    <table>...</table>
</div>
```

### 3. Frontend (JavaScript)
Incluir este script al final del archivo (o en un archivo JS común).

#### Funciones Clave:
- **`debounce`**: Retrasa la búsqueda hasta que el usuario deje de escribir (300ms).
- **`performSearch`**: Realiza la petición `fetch` y reemplaza el HTML.
- **`replaceState`**: Actualiza la URL del navegador sin recargar.

```javascript
document.addEventListener('DOMContentLoaded', () => {
    // 1. Selectores
    const searchInput = document.querySelector('input[name="busqueda"]');
    const tableContainer = document.querySelector('.tabla-contenedor'); // Clase del contenedor
    const inputs = document.querySelectorAll('select, input[type="date"]'); // Otros filtros

    // 2. Función Debounce Standard
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    // 3. Lógica de Búsqueda
    async function performSearch() {
        // Recolectar todos los valores del formulario
        const formData = new FormData(document.querySelector('form'));
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');

        // Feedback visual
        tableContainer.style.opacity = '0.5';

        try {
            const response = await fetch(`${window.location.pathname}?${params.toString()}`);
            const html = await response.text();
            tableContainer.innerHTML = html;
            
            // Actualizar URL (opcional)
            params.delete('ajax');
            window.history.replaceState({}, '', `?${params.toString()}`);
        } catch (e) {
            console.error(e);
        } finally {
            tableContainer.style.opacity = '1';
        }
    }

    // 4. Listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }

    inputs.forEach(input => {
        input.addEventListener('change', performSearch);
    });
});
```

## 🎨 Estilos Recomendados

Para dar feedback de carga suave:

```css
.tabla-contenedor {
    transition: opacity 0.2s ease;
}
```

## ✅ Ventajas
- **UX Superior**: Sensación de aplicación nativa.
- **Eficiencia**: Solo se transmite el fragmento HTML necesario, no todo el layout (header, sidebar, scripts).
- **SEO/Navegabilidad**: Al usar `history.replaceState`, si el usuario recarga la página, mantiene los filtros aplicados.

# 🔐 Documentación de Seguridad y Control de Acceso

Este documento explica cómo funciona el sistema de seguridad y los permisos de usuario en Solufeed v3.6.

## 🏗️ Arquitectura de Seguridad (Route Guards)

A diferencia de los frameworks de Single Page Application (SPA), donde el control de acceso se centraliza en un Router, en Solufeed utilizamos **"Guardias de Ruta"** al inicio de cada archivo PHP.

### 1. Tipos de Rutas

| Tipo | Acceso | Archivo Sugerido |
| :--- | :--- | :--- |
| **Pública** | Cualquiera | `login.php`, `sw.js` |
| **Privada** | Solo usuarios logueados | Capa base de `/admin` |
| **Administrativa** | Solo rol `ADMIN` | Dashboard, Dietas, Insumos, Reportes |
| **Operativa** | Solo rol `CAMPO` | Hub de Campo, Registros de campo |

### 2. Funciones de Validación (`includes/functions.php`)

El sistema utiliza tres funciones principales para proteger las páginas:

*   **`verificarSesion()`**: El nivel más básico. Comprueba si existe una sesión activa. Si no, redirige al `login.php`.
*   **`verificarAdmin()`**: Comprueba que el usuario tenga el tipo `ADMIN`. 
    *   *Inteligencia:* Si detecta a un usuario de `CAMPO`, lo redirige automáticamente a su zona de trabajo (`/admin/campo/index.php`) en lugar de expulsarlo.
*   **`verificarCampo()`**: Comprueba que el usuario tenga el tipo `CAMPO`.
    *   *Inteligencia:* Si un `ADMIN` intenta entrar, lo redirige al Dashboard administrativo.

## 🛡️ Implementación Técnica

Para proteger una página, se debe incluir el guardia al principio del archivo, inmediatamente después de los `require`:

```php
<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Este es el "Guardia de Ruta"
verificarAdmin(); 

// El resto del código solo se ejecutará si el guardia lo permite
?>
```

## 🔄 Redirecciones Automáticas

El sistema está diseñado para ser **"Resistente a Navegación Manual"**. Si un operario intenta escribir una URL administrativa en la barra de direcciones:
1. El archivo inicia.
2. `verificarAdmin()` detecta el rol incorrecto.
3. Se ejecuta un `header('Location: ...')` hacia el Hub de Campo.
4. Se ejecuta un `exit()` que detiene el procesamiento del servidor inmediatamente, garantizando que **ningún dato sensible se envíe al navegador**.

---
*Documentación generada para Solufeed v3.6 - Enero 2026*

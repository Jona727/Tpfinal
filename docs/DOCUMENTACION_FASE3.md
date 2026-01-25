# Documentación Técnica: Fase 3 - Persistencia de Sesión y Autenticación Offline

## 1. Introducción
Uno de los mayores retos de una PWA es permitir que el usuario mantenga su identidad y acceda a la aplicación incluso cuando no hay conexión para validar sus credenciales contra la base de datos central.

## 2. El Desafío Técnico
Tradicionalmente, el login depende de PHP y MySQL. Si el dispositivo está offline, PHP no puede ejecutarse en el servidor. Por lo tanto, debemos trasladar parte de la lógica de "Guardia de Seguridad" al cliente (navegador).

## 3. Estrategia de Implementación: "Sesión Espejo"

### A. Primer Login (Online)
Cuando el usuario inicia sesión con éxito por primera vez (con internet):
1. El servidor valida las credenciales.
2. El cliente (JS) captura el éxito y guarda un objeto `session_offline` en IndexedDB.
3. Este objeto contiene: `id`, `nombre`, `tipo_usuario` y un `token_seguro` (hash).

### B. Validaciones Offline
Para las siguientes entradas al sistema sin internet:
1. El **Service Worker** intercepta la petición a las páginas protegidas.
2. Un script de "Guardia" en JavaScript revisa si existe la `session_offline` en IndexedDB.
3. Si existe y es válida, permite la visualización de la interfaz cacheada.
4. Si no existe, redirige a `login.php`.

## 4. Seguridad Académica
Es vital explicar que el inicio de sesión offline no es "magia", sino una **confianza delegada**. 
- **Privacidad**: Solo se permite el acceso offline a usuarios que ya se hayan logueado con éxito previamente en ese mismo dispositivo.
- **Protección de Datos**: Los datos sensibles no se guardan en texto plano, sino cifrados o mediante tokens no reutilizables fuera del contexto de la PWA.

## 5. Pruebas de Verificación
1. Iniciar sesión con internet normalmente.
2. Cerrar el navegador.
3. Desactivar el internet.
4. Intentar entrar a `admin/campo/index.php`.
5. El sistema debería reconocer la sesión anterior y permitirte entrar sin pedir contraseña nuevamente, sirviendo la interfaz desde la Cache.

# Documentación Técnica: Fase 2 - Almacenamiento Local y Sincronización

## 1. Introducción
En esta fase, el sistema evoluciona de un simple cacheo de archivos a un manejo inteligente de **datos generados por el usuario**. Se implementa una cola de sincronización persistente que permite guardar formularios (alimentaciones, pesadas) sin conexión.

## 2. Tecnologías Implementadas
- **IndexedDB**: Una base de datos NoSQL integrada en el navegador. A diferencia de `localStorage`, es **asíncrona**, permite almacenar grandes volúmenes de datos y no bloquea el hilo principal de ejecución.
- **Navigator Online API**: Permite detectar cambios en el estado de la conexión en tiempo real.
- **Toast Notifications**: Feedback visual premium para informar al usuario sobre el estado de sus datos pendientes.

## 3. Arquitectura del Flujo de Datos

### A. Captura Offline
Cuando un operario de campo intenta "Guardar" un registro y el sistema detecta que no hay conexión:
1. Se intercepta el evento `submit`.
2. Los datos se serializan y se guardan en el Object Store `offline_queue` de IndexedDB.
3. Se muestra un **Toast de advertencia** indicando que el registro está a salvo en el dispositivo.

### B. Sincronización Automática (Background Sync)
Al detectar el evento `online` del navegador:
1. El `OfflineManager` despierta.
2. Recupera todos los registros pendientes de IndexedDB.
3. Los envía uno a uno (o en lote) al servidor mediante peticiones `fetch` (POST).
4. Si el servidor confirma la recepción (`status 200`), el registro se elimina de la base de datos local.

## 4. IndexedDB vs LocalStorage (Punto Académico)
Para este proyecto se ha elegido IndexedDB por los siguientes motivos:
- **Capacidad**: LocalStorage está limitado a ~5MB, IndexedDB permite cientos de MB.
- **Tipado**: IndexedDB permite guardar objetos complejos, blobs y archivos sin necesidad de `JSON.stringify`.
- **Rendimiento**: Al ser asíncrono, no ralentiza la interfaz de usuario durante operaciones de escritura pesadas.

## 5. Instrucciones de Prueba
1. Entrar en "Cargar Mixer" u otra sección de campo.
2. Desactivar el internet (Modo Offline en DevTools).
3. Completar el formulario y guardar. Verás el Toast amarillo de "Operación guardada localmente".
4. Abrir la pestaña **Application** -> **IndexedDB** -> **SolufeedDB** para ver el registro guardado.
5. Activar el internet.
6. Observar la pantalla: Aparecerá un mensaje de "Sincronizando..." y finalmente un Toast verde de éxito.

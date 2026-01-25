# 🧪 Guía de Verificación: Asignación de Lotes

## ✅ Funcionalidad Implementada
Ahora el sistema permite restringir qué lotes ve cada operario de campo.

Los administradores **siempre ven todos los lotes**.
Los usuarios CAMPO **solo ven los lotes que se les asignan**.

## 🔄 Pasos para Probar

### 1. Asignar Lotes a un Operario
1. Inicia sesión como **Admin**.
2. Ve a **Gestión > Usuarios**.
3. En la lista, busca un usuario con rol **Personal de Campo** (ej: `operario@test.com`).
4. Haz clic en el botón de **Asignación de Lotes** (Icono de Vaca 🐮).
   - *Nota: Este botón solo aparece para usuarios de tipo CAMPO.*
5. Verás una pantalla dedicada para seleccionar los lotes.
6. Marca los lotes deseados y haz clic en **Guardar Asignación**.

### 2. Verificar Vista del Admin
1. Ve a **Gestión > Lotes**.
2. Deberías ver **TODOS** los lotes activos (independientemente de lo que asignaste).
3. Esto confirma que tu acceso no se vio afectado.

### 3. Verificar Vista del Operario
1. Abre una ventana de incógnito en el navegador.
2. Inicia sesión con el usuario de campo (`operario@test.com`).
3. Observa el **Hub de Campo**:
    - El contador de "Lotes Activos" debe coincidir con la cantidad que asignaste.
    - El contador de "Pendientes" solo contará pendientes dentro de sus lotes asignados.
4. Ve a **"Ver Lotes"**:
    - La lista solo debe mostrar los lotes que marcaste.
5. Intenta ir a **"Cargar Mixer"**:
    - El desplegable "Lote" solo debe mostrar los lotes permitidos.

## 🛠️ Solución de Problemas

**Q: El operario no ve ningún lote.**
A: Asegúrate de haberle asignado lotes. Por defecto, un usuario nuevo no tiene lotes asignados (0 visibilidad).

**Q: No veo el botón de la vaca 🐮.**
A: Verifica que el usuario sea de tipo "CAMPO". El botón no aparece para administradores.

**Q: El operario ve lotes que desmarqué.**
A: Intenta cerrar sesión y volver a entrar. Aunque la verificación es en tiempo real, es buena práctica.

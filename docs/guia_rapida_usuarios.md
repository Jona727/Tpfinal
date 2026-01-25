# 🚀 Guía Rápida - Módulo de Usuarios

## ⚡ Instalación en 3 Pasos

### Paso 1️⃣: Ejecutar el Script SQL

**Opción A - phpMyAdmin (Recomendado):**
```
1. Abrir phpMyAdmin en tu navegador
2. Seleccionar la base de datos de Solufeed
3. Ir a la pestaña "SQL"
4. Abrir el archivo: database/crear_tabla_usuarios.sql
5. Copiar todo el contenido
6. Pegarlo en el editor SQL
7. Clic en "Continuar"
8. ✅ ¡Listo!
```

**Opción B - Línea de Comandos:**
```bash
# Navegar a la carpeta del proyecto
cd c:\xampp\htdocs\solufeed

# Ejecutar el script (reemplaza 'solufeed' con el nombre de tu BD)
mysql -u root -p solufeed < database/crear_tabla_usuarios.sql
```

### Paso 2️⃣: Primer Login

```
URL: http://localhost/solufeed/admin/login.php

Email: admin@solufeed.com
Contraseña: admin123
```

⚠️ **IMPORTANTE**: Cambia esta contraseña inmediatamente.

### Paso 3️⃣: Acceder al Módulo

```
1. Iniciar sesión como administrador
2. En el menú lateral → Gestión → Usuarios
3. ¡Ya puedes gestionar usuarios!
```

---

## 📋 Funciones Principales

### 🆕 Crear Usuario

```
Gestión > Usuarios > Nuevo Usuario

Completar:
- Nombre completo
- Email (será el usuario de login)
- Tipo: Admin 👔 o Campo 🧑‍🌾
- Contraseña (mínimo 6 caracteres)
- Confirmar contraseña
- Estado: Activo ✓ o Inactivo ✗

Clic en "Crear Usuario"
```

### ✏️ Editar Usuario

```
En el listado → Clic en ✏️

Puedes modificar:
- Nombre
- Email
- Tipo de usuario
- Estado (activo/inactivo)
- Contraseña (opcional)

Clic en "Guardar Cambios"
```

### 🔒 Activar/Desactivar

```
En el listado → Clic en 🔒 (desactivar) o 🔓 (activar)

Confirmar la acción

Usuario desactivado = No puede iniciar sesión
```

### 🔍 Buscar y Filtrar

```
Filtros disponibles:
- Buscar por nombre o email
- Filtrar por tipo (Admin/Campo)
- Filtrar por estado (Activo/Inactivo)

Clic en "Filtrar" para aplicar
Clic en "Limpiar" para resetear
```

---

## 🎯 Casos de Uso Comunes

### Caso 1: Agregar un Operario de Campo

```
1. Nuevo Usuario
2. Nombre: "Pedro González"
3. Email: "pedro@campo.com"
4. Tipo: Personal de Campo 🧑‍🌾
5. Contraseña: "pedro123"
6. Estado: Activo ✓
7. Crear Usuario
```

**Resultado**: Pedro podrá acceder al Hub de Campo para registrar alimentaciones y pesadas.

### Caso 2: Agregar un Administrador

```
1. Nuevo Usuario
2. Nombre: "María López"
3. Email: "maria@admin.com"
4. Tipo: Administrador 👔
5. Contraseña: "maria456"
6. Estado: Activo ✓
7. Crear Usuario
```

**Resultado**: María tendrá acceso completo al sistema (dashboard, reportes, configuración).

### Caso 3: Dar de Baja un Usuario (Sin Eliminarlo)

```
1. Buscar el usuario en el listado
2. Clic en 🔒
3. Confirmar

Estado cambia a: Inactivo ✗
```

**Resultado**: El usuario no podrá iniciar sesión, pero sus datos se conservan.

### Caso 4: Cambiar Contraseña de un Usuario

```
1. Editar usuario
2. Marcar checkbox "Cambiar Contraseña"
3. Ingresar nueva contraseña
4. Confirmar contraseña
5. Guardar Cambios
```

**Resultado**: El usuario deberá usar la nueva contraseña para iniciar sesión.

---

## 🎨 Interfaz

### Vista Principal (Listado)

```
┌─────────────────────────────────────────────────┐
│ 👥 Gestión de Usuarios    [➕ Nuevo Usuario]   │
├─────────────────────────────────────────────────┤
│                                                 │
│  📊 Estadísticas                                │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐          │
│  │  12  │ │  3   │ │  9   │ │  11  │          │
│  │Total │ │Admin │ │Campo │ │Activ │          │
│  └──────┘ └──────┘ └──────┘ └──────┘          │
│                                                 │
│  🔍 Filtros                                     │
│  [Buscar...] [Tipo▼] [Estado▼] [Filtrar]      │
│                                                 │
│  📋 Usuarios                                    │
│  ┌─────────────────────────────────────────┐   │
│  │ Nombre    Email      Tipo    Estado    │   │
│  │ Juan P.   juan@...   👔Admin  ✓Activo  │   │
│  │ María G.  maria@...  🧑‍🌾Campo  ✓Activo  │   │
│  │ Pedro L.  pedro@...  🧑‍🌾Campo  ✗Inact  │   │
│  └─────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

### Formulario de Creación

```
┌─────────────────────────────────────────────────┐
│ ➕ Crear Nuevo Usuario        [← Volver]       │
├─────────────────────────────────────────────────┤
│                                                 │
│  Nombre Completo *                              │
│  [____________________]                         │
│                                                 │
│  Correo Electrónico *                           │
│  [____________________]                         │
│                                                 │
│  Tipo de Usuario *                              │
│  ┌──────────┐  ┌──────────┐                   │
│  │    👔    │  │   🧑‍🌾    │                   │
│  │  Admin   │  │  Campo   │                   │
│  └──────────┘  └──────────┘                   │
│                                                 │
│  Contraseña *                                   │
│  [____________________]                         │
│  ████░░░░░░ Contraseña media                   │
│                                                 │
│  Confirmar Contraseña *                         │
│  [____________________]                         │
│                                                 │
│  ☑ Usuario Activo                              │
│                                                 │
│  [✓ Crear Usuario]  [🔄 Limpiar]              │
└─────────────────────────────────────────────────┘
```

---

## 🔐 Seguridad

### ✅ Medidas Implementadas

- **Autenticación**: Solo usuarios ADMIN pueden acceder
- **Contraseñas**: Hasheadas con bcrypt (nunca en texto plano)
- **SQL Injection**: Protegido con prepared statements
- **Validación**: Todos los inputs son validados y sanitizados
- **Email único**: No se pueden duplicar emails
- **Auto-protección**: Un admin no puede desactivarse a sí mismo

### 🔑 Contraseñas

```
Requisitos mínimos:
- Mínimo 6 caracteres
- Recomendado: Combinar letras, números y símbolos

Indicador de fortaleza:
🔴 Débil:   Menos de 8 caracteres
🟡 Media:   8-10 caracteres con mayúsculas
🟢 Fuerte: 10+ caracteres con números y símbolos
```

---

## ❓ Preguntas Frecuentes

### ¿Puedo eliminar usuarios?

No, el sistema usa "soft delete". Los usuarios se desactivan pero no se eliminan de la base de datos. Esto preserva el historial y las relaciones.

### ¿Qué pasa si olvido la contraseña de un usuario?

Como administrador, puedes editarlo y establecer una nueva contraseña marcando la opción "Cambiar Contraseña".

### ¿Puedo tener varios administradores?

Sí, puedes crear tantos usuarios ADMIN como necesites.

### ¿Los usuarios de campo pueden ver el módulo de usuarios?

No, solo los usuarios ADMIN pueden acceder a la gestión de usuarios.

### ¿Se puede recuperar un usuario desactivado?

Sí, simplemente actívalo nuevamente desde el listado (clic en 🔓).

---

## 🆘 Solución de Problemas

### Error: "Tabla usuario no existe"

```
Solución: Ejecutar el script SQL
database/crear_tabla_usuarios.sql
```

### Error: "Acceso denegado"

```
Solución: Verificar que estás logueado como ADMIN
Solo usuarios ADMIN pueden gestionar usuarios
```

### Error: "Email ya existe"

```
Solución: Usar un email diferente
Cada usuario debe tener un email único
```

### No aparece el menú "Usuarios"

```
Solución: Verificar que el archivo header.php
tenga el enlace al módulo de usuarios
```

---

## 📞 Contacto

Para soporte adicional, consultar:
- `admin/usuarios/README.md` - Documentación completa
- `docs/modulo_usuarios_implementacion.md` - Guía de implementación

---

**Versión**: 1.0  
**Última actualización**: 23 de Enero 2026  
**Estado**: ✅ Producción

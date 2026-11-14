# Formulario de Registro — Fundación Taboada

Sitio web de formulario único en **HTML5, CSS3, JavaScript y PHP (PDO) + MySQL**, preparado para subirse directamente a **Hostinger** (o probarse en local con XAMPP).

## Campos incluidos

- **Nombre** (`nombre`)
- **Foto** (`foto` — validada como JPG, PNG o WEBP hasta 5 MB; se guarda en `uploads/fotos/` y su ruta relativa en MySQL)
- **Carrera** (`carrera`)
- **Universidad** (`universidad`)
- **Correo** (`correo` — único en la tabla)
- **Teléfono** (`telefono`)
- **Fecha de cumpleaños** (`fecha_cumpleanos`)

## Estructura del proyecto

```text
├── .htaccess                  # Reglas de seguridad (bloquea listados, .sql, .md, archivos ocultos)
├── .gitignore                 # Evita subir credenciales reales y fotos de prueba a Git
├── index.php                  # Formulario de registro
├── procesar.php               # Endpoint POST: valida datos, guarda foto y registra en MySQL
├── assets/
│   ├── css/styles.css         # Estilos responsivos
│   └── js/app.js              # Vista previa de foto, validación y envío por fetch()
├── config/
│   ├── .htaccess              # Bloquea acceso HTTP directo a la configuración
│   ├── config.example.php     # Plantilla de referencia
│   ├── config.php             # Credenciales MySQL y ajustes generales (EDITAR ESTE ARCHIVO)
│   └── database.php           # Conexión PDO a MySQL con prepared statements
├── database/
│   ├── .htaccess              # Bloquea acceso HTTP directo a los scripts SQL
│   └── schema.sql             # Script para crear la tabla `registros` en phpMyAdmin
├── includes/
│   ├── .htaccess              # Bloquea acceso HTTP directo
│   ├── bootstrap.php          # Sesión segura, CSRF y helpers
│   └── validator.php          # Validación de campos y verificación real de imagen
└── uploads/
    ├── .htaccess              # Impide ejecución de scripts en la carpeta de subidas
    └── fotos/                 # Carpeta donde se almacenan las fotografías subidas
```

---

## Cómo conectar y subir a Hostinger

### 1. Crear la base de datos en Hostinger
1. Entra a tu panel de **Hostinger (hPanel)**.
2. Ve a **Sitios web** → **Administrar** → **Bases de datos** → **Gestión**.
3. Crea una nueva base de datos MySQL y su usuario (anota el **Nombre de la base de datos**, **Usuario** y **Contraseña**; en Hostinger suelen tener un prefijo como `u123456789_...`).

### 2. Crear la tabla en phpMyAdmin
1. En la misma sección de **Bases de datos**, haz clic en **Entrar a phpMyAdmin** junto a tu base de datos.
2. Asegúrate de seleccionar tu base de datos en la columna izquierda.
3. Ve a la pestaña **Importar**, selecciona el archivo `database/schema.sql` y haz clic en **Importar** (o **Continuar**).
4. Verifica que se haya creado la tabla `registros`.

### 3. Configurar tus credenciales en `config/config.php`
Si descargaste o clonaste este proyecto desde GitHub, copia primero `config/config.example.php` como `config/config.php` (este último está excluido en `.gitignore` para proteger tus credenciales). Luego abre `config/config.php` y reemplaza los valores de conexión:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u123456789_fundacion'); // Tu base de datos de Hostinger
define('DB_USER', 'u123456789_admin');     // Tu usuario MySQL de Hostinger
define('DB_PASS', 'TU_CONTRASENA_MYSQL');  // Tu contraseña MySQL
```

> **Nota:** En Hostinger, el host MySQL normalmente es `localhost`. Deja `APP_DEBUG` en `false` en producción para no exponer detalles internos en caso de error.

### 4. Subir los archivos a Hostinger
1. Entra a **Administrador de archivos** en hPanel (o conéctate por FTP con FileZilla).
2. Abre la carpeta `public_html/` (o una subcarpeta como `public_html/registro/` si quieres que esté en una ruta específica).
3. Sube todo el contenido del proyecto **manteniendo la estructura de carpetas** y asegurándote de incluir los archivos `.htaccess`.
4. Verifica que la carpeta `uploads/fotos/` tenga permisos `755` para que PHP pueda guardar las fotografías subidas.
5. Si quieres permitir que una misma persona registre el mismo correo más de una vez, elimina el índice `uk_registros_correo` en phpMyAdmin o quita `UNIQUE KEY uk_registros_correo` en `database/schema.sql` antes de importar.

---

## Prueba local opcional (XAMPP)

Si deseas probarlo localmente antes de subirlo a Hostinger:
1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\fundacion-taboada`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Abre `http://localhost/phpmyadmin`, crea la base de datos `fundacion_taboada` (cotejamiento `utf8mb4_unicode_ci`) e importa `database/schema.sql`.
4. Los valores predeterminados de `config/config.php` (`localhost`, `fundacion_taboada`, `root`, contraseña vacía) ya coinciden con una instalación estándar de XAMPP.
5. Abre `http://localhost/fundacion-taboada/` en tu navegador.

# Formulario de Registro — Fundación Taboada

Sitio web de formulario único en **HTML5, CSS3, JavaScript y PHP (PDO) + MySQL**, preparado para subirse directamente a **Hostinger** (o probarse en local con XAMPP).

## Campos incluidos (tabla `alumnos`)

- **Nombre** (`aloNombreCompleto`)
- **Foto** (`aloFoto` y `aloFotoMiniatura` — validada como JPG, PNG o WEBP hasta 5 MB; `aloFoto` guarda la imagen original en `LONGBLOB` y `aloFotoMiniatura` almacena una copia comprimida de baja calidad en JPEG de máx. 160×160 px para miniaturas)
- **Carrera** (`aloCarrera`)
- **Universidad** (`aloUniversidad`)
- **Correo** (`aloCorreo`)
- **Teléfono** (`aloTelefono`)
- **Fecha de cumpleaños** (`aloFechaNacimiento`)

## Estructura del proyecto

```text
├── .htaccess                  # Reglas de seguridad y ruta limpia /alumnos
├── .gitignore                 # Evita subir credenciales reales y fotos de prueba a Git
├── index.php                  # Formulario de registro
├── alumnos.php                # Vista privada de cards y modal (/alumnos)
├── foto.php                   # Entrega protegida de aloFotoMiniatura y aloFoto
├── procesar.php               # Endpoint POST: valida datos, guarda foto y registra en MySQL
├── assets/
│   ├── css/styles.css         # Estilos base y del formulario
│   ├── css/alumnos.css        # Estilos de cards, buscador, paginación y modal
│   ├── js/app.js              # Vista previa de foto, validación y envío por fetch()
│   └── js/alumnos.js          # Lógica de modal y carga bajo demanda de aloFoto
├── config/
│   ├── .htaccess              # Bloquea acceso HTTP directo a la configuración
│   ├── config.example.php     # Plantilla de referencia
│   ├── config.php             # Credenciales MySQL y ajustes generales (EDITAR ESTE ARCHIVO)
│   └── database.php           # Conexión PDO a MySQL con prepared statements
├── database/
│   ├── .htaccess              # Bloquea acceso HTTP directo a los scripts SQL
│   └── schema.sql             # Script para crear la tabla `alumnos` en phpMyAdmin
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
4. Verifica que se haya creado la tabla `alumnos`.

### 3. Configurar tus credenciales en `config/config.php`
Si descargaste o clonaste este proyecto desde GitHub, copia primero `config/config.example.php` como `config/config.php` (este último está excluido en `.gitignore` para proteger tus credenciales). Luego abre `config/config.php` y reemplaza los valores de conexión:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u123456789_taboada');   // Tu base de datos de Hostinger
define('DB_USER', 'u123456789_taboada');   // Tu usuario MySQL de Hostinger
define('DB_PASS', 'TU_CONTRASENA_MYSQL');  // Tu contraseña MySQL
define('ADMIN_ACCESS_PASSWORD', 'TU_CONTRASENA_DE_CONSULTA'); // Contraseña para entrar a /alumnos
```

> **Nota:** En Hostinger, el host MySQL puede ser `localhost` o la IP de MySQL remoto que te indique hPanel. Deja `APP_DEBUG` en `false` en producción para no exponer detalles internos en caso de error. La sección `/alumnos` no tiene enlaces visibles desde el formulario y requiere `ADMIN_ACCESS_PASSWORD`.

### 4. Subir los archivos a Hostinger
1. Entra a **Administrador de archivos** en hPanel (o conéctate por FTP con FileZilla).
2. Abre la carpeta `public_html/` (o una subcarpeta como `public_html/registro/` si quieres que esté en una ruta específica).
3. Sube todo el contenido del proyecto **manteniendo la estructura de carpetas** y asegurándote de incluir los archivos `.htaccess` y `config/config.php`.

---

## Prueba local opcional (XAMPP)

Si deseas probarlo localmente antes de subirlo a Hostinger:
1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\fundacion-taboada`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Abre `http://localhost/phpmyadmin`, crea la base de datos `fundacion_taboada` (cotejamiento `utf8mb4_unicode_ci`) e importa `database/schema.sql`.
4. Los valores predeterminados de `config/config.php` (`localhost`, `fundacion_taboada`, `root`, contraseña vacía) ya coinciden con una instalación estándar de XAMPP.
5. Abre `http://localhost/fundacion-taboada/` en tu navegador.

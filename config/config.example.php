<?php
/**
 * Plantilla de configuración de la aplicación y base de datos.
 *
 * Copia este archivo como `config.php` en el servidor (public_html/config/config.php)
 * y completa los datos de conexión MySQL proporcionados por Hostinger en:
 * hPanel -> Bases de datos -> Gestión.
 */

declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// --- Configuración de Base de Datos MySQL ---
define('DB_HOST', 'localhost'); // O la IP/host MySQL indicado en Hostinger
define('DB_PORT', '3306');
define('DB_NAME', 'u123456789_taboada');     // Reemplaza con tu base de datos de Hostinger
define('DB_USER', 'u123456789_taboada');     // Reemplaza con tu usuario MySQL de Hostinger
define('DB_PASS', 'CAMBIA_ESTA_CONTRASENA'); // Reemplaza con tu contraseña MySQL
define('DB_CHARSET', 'utf8mb4');

// --- Configuración general de la aplicación ---
define('APP_NAME', 'Fundación Taboada');
define('APP_TIMEZONE', 'America/Mexico_City');
// Mantener en false en producción (Hostinger) para no mostrar errores técnicos al usuario.
define('APP_DEBUG', false);
// Contraseña para acceder a la sección privada /alumnos (admite texto plano o password_hash)
define('ADMIN_ACCESS_PASSWORD', 'CAMBIA_ESTA_CONTRASENA_ADMIN');

// --- Configuración de fotografías y miniaturas ---
define('MAX_PHOTO_SIZE_BYTES', 5 * 1024 * 1024); // 5 MB
define('THUMBNAIL_MAX_DIMENSION', 480);           // Tamaño máximo de miniatura en px
define('THUMBNAIL_JPEG_QUALITY', 75);             // Calidad JPEG (0-100) para aloFotoMiniatura
define('UPLOAD_PHOTOS_DIR', dirname(__DIR__) . '/uploads/fotos');
define('UPLOAD_PHOTOS_RELATIVE_DIR', 'uploads/fotos');

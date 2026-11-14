<?php
/**
 * Plantilla de configuración de la aplicación y base de datos.
 *
 * Copia este archivo como `config.php` (si aún no existe) y completa
 * los datos de conexión MySQL proporcionados por Hostinger en:
 * hPanel -> Bases de datos -> Gestión.
 */

declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// --- Configuración de Base de Datos MySQL ---
// En Hostinger normalmente el host es 'localhost'.
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u123456789_fundacion'); // Reemplaza con tu base de datos de Hostinger
define('DB_USER', 'u123456789_admin');     // Reemplaza con tu usuario MySQL de Hostinger
define('DB_PASS', 'CAMBIA_ESTA_CONTRASENA'); // Reemplaza con tu contraseña MySQL
define('DB_CHARSET', 'utf8mb4');

// --- Configuración general de la aplicación ---
define('APP_NAME', 'Fundación Taboada');
define('APP_TIMEZONE', 'America/Mexico_City');
// Mantener en false en producción (Hostinger) para no mostrar errores técnicos al usuario.
define('APP_DEBUG', false);

// --- Configuración de subida de fotografías ---
define('MAX_PHOTO_SIZE_BYTES', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_PHOTOS_DIR', dirname(__DIR__) . '/uploads/fotos');
define('UPLOAD_PHOTOS_RELATIVE_DIR', 'uploads/fotos');

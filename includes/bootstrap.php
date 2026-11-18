<?php
/**
 * Inicialización común de la aplicación, sesión segura, CSRF y utilidades.
 */

declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$configFile = dirname(__DIR__) . '/config/config.php';
$exampleConfigFile = dirname(__DIR__) . '/config/config.example.php';

if (is_file($configFile)) {
    require_once $configFile;
} elseif (is_file($exampleConfigFile)) {
    require_once $exampleConfigFile;
} else {
    http_response_code(500);
    exit('Falta el archivo de configuración.');
}

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Indica si existe el archivo de configuración real con credenciales propias.
 */
function isDatabaseConfigured(): bool
{
    $configFile = dirname(__DIR__) . '/config/config.php';
    if (!is_file($configFile)) {
        return false;
    }

    return defined('DB_PASS') && DB_PASS !== 'CAMBIA_ESTA_CONTRASENA';
}

if (defined('APP_TIMEZONE') && APP_TIMEZONE !== '') {
    date_default_timezone_set(APP_TIMEZONE);
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Escapa texto para imprimirlo de forma segura en HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Envía cabeceras HTTP básicas de seguridad.
 */
function sendSecurityHeaders(): void
{
    if (headers_sent() || PHP_SAPI === 'cli') {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Obtiene o genera el token CSRF almacenado en sesión.
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF recibido en la petición.
 */
function verifyCsrfToken(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Indica si la solicitud espera una respuesta JSON (fetch / AJAX).
 */
function isAjaxRequest(): bool
{
    $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    if ($requestedWith === 'xmlhttprequest') {
        return true;
    }

    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    return strpos($accept, 'application/json') !== false;
}

/**
 * Devuelve la ruta base de la aplicación sin barra final (ej. '' o '/subcarpeta').
 */
function getBaseUrlPath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '/' || $dir === '.' || $dir === '\\') {
        return '';
    }

    return rtrim($dir, '/');
}

/**
 * Indica si existe una contraseña configurada para el acceso a /alumnos.
 */
function isAdminPasswordConfigured(): bool
{
    if (!defined('ADMIN_ACCESS_PASSWORD') || !is_string(ADMIN_ACCESS_PASSWORD)) {
        return false;
    }

    return ADMIN_ACCESS_PASSWORD !== '' && ADMIN_ACCESS_PASSWORD !== 'CAMBIA_ESTA_CONTRASENA_ADMIN';
}

/**
 * Verifica la contraseña ingresada para acceder a /alumnos.
 */
function verifyAdminPassword(string $password): bool
{
    if (!isAdminPasswordConfigured() || $password === '') {
        return false;
    }

    $configured = (string) ADMIN_ACCESS_PASSWORD;
    if (strpos($configured, '$2y$') === 0 || strpos($configured, '$argon2') === 0) {
        return password_verify($password, $configured);
    }

    return hash_equals($configured, $password);
}

/**
 * Indica si la sesión actual está autorizada para ver /alumnos y las fotografías.
 */
function isAdminAuthenticated(): bool
{
    return isAdminPasswordConfigured()
        && !empty($_SESSION['alumnos_authenticated'])
        && $_SESSION['alumnos_authenticated'] === true;
}

/**
 * Formatea una fecha YYYY-MM-DD en español legible sin depender de locale del sistema.
 */
function formatSpanishDate(?string $isoDate): string
{
    if ($isoDate === null || trim($isoDate) === '') {
        return 'Sin fecha registrada';
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', trim($isoDate));
    if ($dt === false) {
        return $isoDate;
    }

    $months = [
        1  => 'enero',
        2  => 'febrero',
        3  => 'marzo',
        4  => 'abril',
        5  => 'mayo',
        6  => 'junio',
        7  => 'julio',
        8  => 'agosto',
        9  => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    $day = (int) $dt->format('j');
    $month = $months[(int) $dt->format('n')] ?? $dt->format('m');
    $year = $dt->format('Y');

    return "{$day} de {$month} de {$year}";
}

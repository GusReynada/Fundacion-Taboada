<?php
/**
 * Entrega protegida de imágenes almacenadas en LONGBLOB (`aloFotoMiniatura` y `aloFoto`).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

sendSecurityHeaders();

if (!isAdminAuthenticated()) {
    http_response_code(403);
    exit('Acceso denegado.');
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    exit('Base de datos no configurada.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID inválido.');
}

$tipo = (string) ($_GET['tipo'] ?? 'miniatura');
$columnSql = $tipo === 'completa'
    ? 'aloFoto'
    : 'COALESCE(aloFotoMiniatura, aloFoto)';

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT {$columnSql} AS imagen FROM alumnos WHERE idAlumno = :id LIMIT 1");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
} catch (Throwable $e) {
    error_log('[Fundacion Taboada] Error al leer imagen: ' . $e->getMessage());
    http_response_code(500);
    exit('Error al obtener la imagen.');
}

if (!$row || !is_string($row['imagen']) || $row['imagen'] === '') {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$bytes = $row['imagen'];
$detectedMime = null;

if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeResult = $finfo->buffer($bytes);
    if (is_string($mimeResult) && $mimeResult !== '') {
        $detectedMime = $mimeResult;
    }
}

if ($detectedMime === null && function_exists('getimagesizefromstring')) {
    $info = @getimagesizefromstring($bytes);
    if (is_array($info) && !empty($info['mime'])) {
        $detectedMime = (string) $info['mime'];
    }
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if ($detectedMime === null || !in_array($detectedMime, $allowedMimes, true)) {
    http_response_code(415);
    exit('Formato de imagen no válido.');
}

$etag = '"' . md5($bytes) . '"';
$ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));

header('Cache-Control: private, max-age=3600');
header('ETag: ' . $etag);

if ($ifNoneMatch === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $detectedMime);
header('Content-Length: ' . strlen($bytes));
echo $bytes;

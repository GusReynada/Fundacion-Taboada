<?php
/**
 * Procesa el envío del formulario, valida datos, guarda la foto y registra en MySQL.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/validator.php';

sendSecurityHeaders();

/**
 * Envía respuesta JSON (para fetch/AJAX) o redirige a index.php (PRG fallback).
 *
 * @param array<string, string> $errors
 * @param array<string, string> $oldInput
 */
function sendResponse(
    bool $ok,
    int $statusCode,
    string $message,
    array $errors = [],
    array $oldInput = []
): void {
    if (isAjaxRequest()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok'         => $ok,
            'message'    => $message,
            'errors'     => $errors,
            'csrf_token' => getCsrfToken(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['form_flash'] = [
        'type'    => $ok ? 'success' : 'error',
        'message' => $message,
        'errors'  => $errors,
        'old'     => $ok ? [] : $oldInput,
    ];

    header('Location: index.php', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (isAjaxRequest()) {
        sendResponse(false, 405, 'Método no permitido.');
    }
    header('Location: index.php', true, 303);
    exit;
}

// Si se excede post_max_size en PHP, $_POST y $_FILES llegan vacíos.
if (
    empty($_POST)
    && empty($_FILES)
    && isset($_SERVER['CONTENT_LENGTH'])
    && (int) $_SERVER['CONTENT_LENGTH'] > 0
) {
    sendResponse(
        false,
        413,
        'El archivo enviado supera el tamaño máximo permitido por el servidor.'
    );
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    sendResponse(
        false,
        403,
        'La sesión de seguridad expiró o no es válida. Recarga la página e inténtalo de nuevo.'
    );
}

$validation = validateRegistrationInput($_POST, $_FILES, MAX_PHOTO_SIZE_BYTES, true);
$data = $validation['data'];

if (!$validation['valid'] || $validation['photo'] === null) {
    sendResponse(
        false,
        422,
        'Por favor revisa los campos marcados antes de enviar el formulario.',
        $validation['errors'],
        $data
    );
}

if (!isDatabaseConfigured()) {
    sendResponse(
        false,
        500,
        'Falta crear y configurar el archivo config/config.php con tus credenciales MySQL de Hostinger.',
        [],
        $data
    );
}

$photo = $validation['photo'];

$photoBytes = @file_get_contents($photo['tmp_name']);
if ($photoBytes === false || $photoBytes === '') {
    error_log('[Fundacion Taboada] No se pudo leer el archivo temporal de la fotografía.');
    sendResponse(
        false,
        500,
        'No se pudo procesar el archivo de la fotografía. Inténtalo nuevamente.',
        [],
        $data
    );
}

$thumbnailBytes = createPhotoThumbnail($photoBytes, $photo['mime']);

try {
    $pdo = getDatabaseConnection();
    $sql = 'INSERT INTO alumnos (
                aloNombreCompleto,
                aloFoto,
                aloCarrera,
                aloUniversidad,
                aloCorreo,
                aloTelefono,
                aloFechaNacimiento,
                aloFotoMiniatura
            ) VALUES (
                :nombre,
                :foto,
                :carrera,
                :universidad,
                :correo,
                :telefono,
                :fecha_nacimiento,
                :foto_miniatura
            )';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nombre', $data['nombre'], PDO::PARAM_STR);
    $stmt->bindValue(':foto', $photoBytes, PDO::PARAM_LOB);
    $stmt->bindValue(':carrera', $data['carrera'], PDO::PARAM_STR);
    $stmt->bindValue(':universidad', $data['universidad'], PDO::PARAM_STR);
    $stmt->bindValue(':correo', $data['correo'], PDO::PARAM_STR);
    $stmt->bindValue(':telefono', $data['telefono'], PDO::PARAM_STR);
    $stmt->bindValue(':fecha_nacimiento', $data['fecha_cumpleanos'], PDO::PARAM_STR);
    if ($thumbnailBytes !== null) {
        $stmt->bindValue(':foto_miniatura', $thumbnailBytes, PDO::PARAM_LOB);
    } else {
        $stmt->bindValue(':foto_miniatura', null, PDO::PARAM_NULL);
    }

    $stmt->execute();
} catch (PDOException $e) {
    $sqlState = (string) $e->getCode();
    $driverCode = (int) ($e->errorInfo[1] ?? 0);

    if ($sqlState === '23000' || $driverCode === 1062) {
        sendResponse(
            false,
            409,
            'Este correo electrónico ya se encuentra registrado.',
            ['correo' => 'Este correo electrónico ya está registrado.'],
            $data
        );
    }

    error_log('[Fundacion Taboada] Error MySQL al registrar: ' . $e->getMessage());
    $debugInfo = (defined('APP_DEBUG') && APP_DEBUG) ? ' (' . $e->getMessage() . ')' : '';

    sendResponse(
        false,
        500,
        'No se pudo conectar o guardar en la base de datos.' . $debugInfo,
        [],
        $data
    );
} catch (Throwable $e) {
    error_log('[Fundacion Taboada] Error inesperado: ' . $e->getMessage());
    $debugInfo = (defined('APP_DEBUG') && APP_DEBUG) ? ' (' . $e->getMessage() . ')' : '';

    sendResponse(
        false,
        500,
        'Ocurrió un error inesperado al procesar el registro.' . $debugInfo,
        [],
        $data
    );
}

sendResponse(
    true,
    201,
    '¡Registro guardado correctamente! Tus datos han sido almacenados.'
);

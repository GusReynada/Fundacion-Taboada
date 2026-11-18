<?php
/**
 * Validación de campos del formulario y verificación de la foto subida.
 */

declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

/**
 * Normaliza espacios en blanco redundantes.
 */
function normalizeText(string $value): string
{
    $normalized = preg_replace('/\s+/u', ' ', trim($value));
    return $normalized ?? trim($value);
}

/**
 * Longitud segura en UTF-8 con respaldo si mbstring no estuviera activo.
 */
function utf8Length(string $value): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}

/**
 * Conversión a minúsculas en UTF-8 con respaldo si mbstring no estuviera activo.
 */
function utf8Lower(string $value): string
{
    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

/**
 * Valida la imagen subida y devuelve metadatos seguros o un mensaje de error.
 *
 * @param array<string, mixed>|null $file Entrada de $_FILES['foto']
 * @return array{valid: bool, error: ?string, data: ?array{tmp_name: string, mime: string, extension: string, size: int}}
 */
function validatePhotoUpload(?array $file, int $maxFileSize, bool $checkIsUploadedFile = true): array
{
    if ($file === null || !isset($file['error'])) {
        return [
            'valid' => false,
            'error' => 'La foto es obligatoria.',
            'data'  => null,
        ];
    }

    $uploadError = (int) $file['error'];
    if ($uploadError !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'La foto supera el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'La foto supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL    => 'La foto se subió de forma incompleta. Intenta nuevamente.',
            UPLOAD_ERR_NO_FILE    => 'La foto es obligatoria.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir la foto en el servidor.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida de la foto.',
        ];

        return [
            'valid' => false,
            'error' => $errorMessages[$uploadError] ?? 'Ocurrió un error al subir la foto.',
            'data'  => null,
        ];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || ($checkIsUploadedFile ? !is_uploaded_file($tmpName) : !is_file($tmpName))) {
        return [
            'valid' => false,
            'error' => 'El archivo de foto no es válido.',
            'data'  => null,
        ];
    }

    $size = isset($file['size']) ? (int) $file['size'] : (int) filesize($tmpName);
    if ($size <= 0) {
        return [
            'valid' => false,
            'error' => 'El archivo de foto está vacío.',
            'data'  => null,
        ];
    }

    if ($size > $maxFileSize) {
        $maxMb = round($maxFileSize / (1024 * 1024), 1);
        return [
            'valid' => false,
            'error' => "La foto no debe superar los {$maxMb} MB.",
            'data'  => null,
        ];
    }

    $allowedMimeMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $detectedMime = null;
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeResult = $finfo->file($tmpName);
        if (is_string($mimeResult) && $mimeResult !== '') {
            $detectedMime = $mimeResult;
        }
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false || empty($imageInfo['mime'])) {
        return [
            'valid' => false,
            'error' => 'El archivo debe ser una imagen válida (JPG, PNG o WEBP).',
            'data'  => null,
        ];
    }

    $mimeToCheck = $detectedMime ?? $imageInfo['mime'];
    if (!isset($allowedMimeMap[$mimeToCheck]) || !isset($allowedMimeMap[$imageInfo['mime']])) {
        return [
            'valid' => false,
            'error' => 'Formato de imagen no permitido. Usa JPG, PNG o WEBP.',
            'data'  => null,
        ];
    }

    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000) {
        return [
            'valid' => false,
            'error' => 'Las dimensiones de la imagen no son válidas.',
            'data'  => null,
        ];
    }

    return [
        'valid' => true,
        'error' => null,
        'data'  => [
            'tmp_name'  => $tmpName,
            'mime'      => $mimeToCheck,
            'extension' => $allowedMimeMap[$mimeToCheck],
            'size'      => $size,
        ],
    ];
}

/**
 * Valida todos los datos del formulario de registro.
 *
 * @param array<string, mixed> $post
 * @param array<string, mixed> $files
 * @return array{
 *     valid: bool,
 *     data: array{
 *         nombre: string,
 *         carrera: string,
 *         universidad: string,
 *         correo: string,
 *         telefono: string,
 *         fecha_cumpleanos: string
 *     },
 *     photo: ?array{tmp_name: string, mime: string, extension: string, size: int},
 *     errors: array<string, string>
 * }
 */
function validateRegistrationInput(
    array $post,
    array $files,
    int $maxFileSize,
    bool $checkIsUploadedFile = true
): array {
    $nombre = normalizeText((string) ($post['nombre'] ?? ''));
    $carrera = normalizeText((string) ($post['carrera'] ?? ''));
    $universidad = normalizeText((string) ($post['universidad'] ?? ''));
    $correo = utf8Lower(trim((string) ($post['correo'] ?? '')));
    $telefono = normalizeText((string) ($post['telefono'] ?? ''));
    $fechaCumpleanos = trim((string) ($post['fecha_cumpleanos'] ?? ''));

    $errors = [];

    // Nombre
    if ($nombre === '') {
        $errors['nombre'] = 'El nombre es obligatorio.';
    } elseif (utf8Length($nombre) < 2 || utf8Length($nombre) > 200) {
        $errors['nombre'] = 'El nombre debe tener entre 2 y 200 caracteres.';
    }

    // Carrera
    if ($carrera === '') {
        $errors['carrera'] = 'La carrera es obligatoria.';
    } elseif (utf8Length($carrera) < 2 || utf8Length($carrera) > 150) {
        $errors['carrera'] = 'La carrera debe tener entre 2 y 150 caracteres.';
    }

    // Universidad
    if ($universidad === '') {
        $errors['universidad'] = 'La universidad es obligatoria.';
    } elseif (utf8Length($universidad) < 2 || utf8Length($universidad) > 150) {
        $errors['universidad'] = 'La universidad debe tener entre 2 y 150 caracteres.';
    }

    // Correo
    if ($correo === '') {
        $errors['correo'] = 'El correo electrónico es obligatorio.';
    } elseif (utf8Length($correo) > 250 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        $errors['correo'] = 'Ingresa un correo electrónico válido.';
    }

    // Teléfono
    if ($telefono === '') {
        $errors['telefono'] = 'El teléfono es obligatorio.';
    } elseif (!preg_match('/^[0-9+\-\s().]+$/', $telefono)) {
        $errors['telefono'] = 'El teléfono contiene caracteres no válidos.';
    } else {
        $digitsOnly = preg_replace('/\D+/', '', $telefono) ?? '';
        $digitCount = strlen($digitsOnly);
        if (utf8Length($telefono) > 25 || $digitCount < 7 || $digitCount > 15) {
            $errors['telefono'] = 'Ingresa un teléfono válido (entre 7 y 15 dígitos).';
        }
    }

    // Fecha de cumpleaños
    if ($fechaCumpleanos === '') {
        $errors['fecha_cumpleanos'] = 'La fecha de cumpleaños es obligatoria.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCumpleanos)) {
        $errors['fecha_cumpleanos'] = 'Formato de fecha no válido.';
    } else {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaCumpleanos);
        $lastErrors = DateTimeImmutable::getLastErrors();
        $hasParseErrors = is_array($lastErrors)
            && (($lastErrors['warning_count'] ?? 0) > 0 || ($lastErrors['error_count'] ?? 0) > 0);

        if ($parsedDate === false || $hasParseErrors || $parsedDate->format('Y-m-d') !== $fechaCumpleanos) {
            $errors['fecha_cumpleanos'] = 'La fecha de cumpleaños no es válida.';
        } else {
            $minDate = new DateTimeImmutable('1900-01-01');
            $today = new DateTimeImmutable('today');
            if ($parsedDate < $minDate || $parsedDate > $today) {
                $errors['fecha_cumpleanos'] = 'La fecha debe estar entre 1900 y la fecha actual.';
            }
        }
    }

    // Foto
    $photoValidation = validatePhotoUpload(
        is_array($files['foto'] ?? null) ? $files['foto'] : null,
        $maxFileSize,
        $checkIsUploadedFile
    );

    if (!$photoValidation['valid'] && $photoValidation['error'] !== null) {
        $errors['foto'] = $photoValidation['error'];
    }

    return [
        'valid' => empty($errors),
        'data'  => [
            'nombre'           => $nombre,
            'carrera'          => $carrera,
            'universidad'      => $universidad,
            'correo'           => $correo,
            'telefono'         => $telefono,
            'fecha_cumpleanos' => $fechaCumpleanos,
        ],
        'photo'  => $photoValidation['data'],
        'errors' => $errors,
    ];
}

/**
 * Genera una versión miniatura optimizada (JPEG máx. 480x480 px, calidad 75)
 * a partir de la fotografía original para almacenarla en `aloFotoMiniatura`.
 * Devuelve null si GD no está disponible o si la imagen no puede procesarse.
 */
function createPhotoThumbnail(
    string $binaryData,
    string $mime,
    int $maxDimension = 480,
    int $jpegQuality = 75,
    ?string $sourcePath = null
): ?string {
    if ($binaryData === '' || !function_exists('imagecreatefromstring')) {
        return null;
    }

    $source = @imagecreatefromstring($binaryData);
    if ($source === false) {
        return null;
    }

    // Corregir orientación EXIF en fotos JPEG tomadas desde dispositivos móviles
    if ($mime === 'image/jpeg' && $sourcePath !== null && is_file($sourcePath) && function_exists('exif_read_data')) {
        $exif = @exif_read_data($sourcePath);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = null;
        if ($orientation === 3) {
            $angle = 180;
        } elseif ($orientation === 6) {
            $angle = -90;
        } elseif ($orientation === 8) {
            $angle = 90;
        }
        if ($angle !== null && function_exists('imagerotate')) {
            $rotated = @imagerotate($source, $angle, 0);
            if ($rotated !== false) {
                $source = $rotated;
            }
        }
    }

    $origWidth = imagesx($source);
    $origHeight = imagesy($source);
    if ($origWidth < 1 || $origHeight < 1) {
        return null;
    }

    $safeMaxDimension = max(32, $maxDimension);
    $safeQuality = max(10, min(95, $jpegQuality));

    if ($origWidth <= $safeMaxDimension && $origHeight <= $safeMaxDimension) {
        $targetWidth = $origWidth;
        $targetHeight = $origHeight;
    } else {
        $scale = min($safeMaxDimension / $origWidth, $safeMaxDimension / $origHeight);
        $targetWidth = max(1, (int) round($origWidth * $scale));
        $targetHeight = max(1, (int) round($origHeight * $scale));
    }

    $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
    if ($thumb === false) {
        return null;
    }

    // Fondo blanco para evitar fondos negros al convertir PNG/WEBP con transparencia a JPEG
    imagealphablending($thumb, true);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    if ($white !== false) {
        imagefilledrectangle($thumb, 0, 0, $targetWidth, $targetHeight, $white);
    }

    if (!imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight)) {
        return null;
    }

    ob_start();
    $saved = false;
    if (function_exists('imagejpeg')) {
        $saved = imagejpeg($thumb, null, $safeQuality);
    } elseif (function_exists('imagewebp')) {
        $saved = imagewebp($thumb, null, $safeQuality);
    } elseif (function_exists('imagepng')) {
        $saved = imagepng($thumb, null, 9);
    }
    $output = ob_get_clean();

    if (!$saved || !is_string($output) || $output === '') {
        return null;
    }

    return $output;
}

<?php
/**
 * Página principal: Formulario de registro - Fundación Taboada.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

sendSecurityHeaders();

$csrfToken = getCsrfToken();
$flash = $_SESSION['form_flash'] ?? null;
unset($_SESSION['form_flash']);

$flashType = is_array($flash) ? (string) ($flash['type'] ?? '') : '';
$flashMessage = is_array($flash) ? (string) ($flash['message'] ?? '') : '';
$fieldErrors = (is_array($flash) && is_array($flash['errors'] ?? null)) ? $flash['errors'] : [];
$old = (is_array($flash) && is_array($flash['old'] ?? null)) ? $flash['old'] : [];

$today = date('Y-m-d');
$maxPhotoMb = (int) round(MAX_PHOTO_SIZE_BYTES / (1024 * 1024));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Registro | <?= e(APP_NAME) ?></title>
    <meta name="description" content="Formulario oficial de registro de datos para <?= e(APP_NAME) ?>.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="page-wrapper">
        <main class="form-card" aria-labelledby="formTitle">
            <header class="form-header">
                <span class="brand-badge"><?= e(APP_NAME) ?></span>
                <h1 id="formTitle">Formulario de Registro</h1>
                <p class="form-subtitle">
                    Completa tus datos personales y académicos. Toda la información se almacenará de forma segura.
                </p>
            </header>

            <div
                id="formAlert"
                class="alert <?= $flashMessage !== '' ? ('alert--' . ($flashType === 'success' ? 'success' : 'error') . ' is-visible') : '' ?>"
                role="status"
                aria-live="polite"
            >
                <?php if ($flashMessage !== ''): ?>
                    <span class="alert-text"><?= e($flashMessage) ?></span>
                <?php endif; ?>
            </div>

            <form
                id="registroForm"
                action="procesar.php"
                method="POST"
                enctype="multipart/form-data"
                data-max-photo-bytes="<?= e((string) MAX_PHOTO_SIZE_BYTES) ?>"
                novalidate
            >
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-grid">
                    <!-- Nombre -->
                    <div class="form-group form-group--full <?= isset($fieldErrors['nombre']) ? 'has-error' : '' ?>">
                        <label for="nombre">
                            Nombre <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="<?= e($old['nombre'] ?? '') ?>"
                            placeholder="Ej. María Fernanda López García"
                            maxlength="150"
                            autocomplete="name"
                            required
                            aria-describedby="error-nombre"
                            aria-invalid="<?= isset($fieldErrors['nombre']) ? 'true' : 'false' ?>"
                        >
                        <p class="field-error" id="error-nombre" data-error-for="nombre">
                            <?= e($fieldErrors['nombre'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Foto -->
                    <div class="form-group form-group--full <?= isset($fieldErrors['foto']) ? 'has-error' : '' ?>">
                        <label for="foto">
                            Foto <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="photo-dropzone" id="photoDropzone">
                            <div class="photo-preview" id="photoPreview">
                                <svg class="photo-placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 19.5a7.5 7.5 0 0115 0" />
                                </svg>
                                <img id="photoPreviewImg" src="" alt="Vista previa de la foto seleccionada" hidden>
                            </div>
                            <div class="photo-controls">
                                <p class="photo-status" id="photoFileName">Ninguna foto seleccionada</p>
                                <p class="photo-hint">Formatos permitidos: JPG, PNG o WEBP (máx. <?= e((string) $maxPhotoMb) ?> MB).</p>
                                <div class="photo-actions">
                                    <label for="foto" class="btn-secondary">Seleccionar foto</label>
                                    <button type="button" class="btn-ghost" id="removePhotoBtn" hidden>Quitar</button>
                                </div>
                            </div>
                            <input
                                type="file"
                                id="foto"
                                name="foto"
                                class="photo-input-sr"
                                accept="image/jpeg,image/png,image/webp"
                                required
                                aria-describedby="error-foto"
                                aria-invalid="<?= isset($fieldErrors['foto']) ? 'true' : 'false' ?>"
                            >
                        </div>
                        <p class="field-error" id="error-foto" data-error-for="foto">
                            <?= e($fieldErrors['foto'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Carrera -->
                    <div class="form-group <?= isset($fieldErrors['carrera']) ? 'has-error' : '' ?>">
                        <label for="carrera">
                            Carrera <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="carrera"
                            name="carrera"
                            value="<?= e($old['carrera'] ?? '') ?>"
                            placeholder="Ej. Ingeniería en Sistemas"
                            maxlength="150"
                            required
                            aria-describedby="error-carrera"
                            aria-invalid="<?= isset($fieldErrors['carrera']) ? 'true' : 'false' ?>"
                        >
                        <p class="field-error" id="error-carrera" data-error-for="carrera">
                            <?= e($fieldErrors['carrera'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Universidad -->
                    <div class="form-group <?= isset($fieldErrors['universidad']) ? 'has-error' : '' ?>">
                        <label for="universidad">
                            Universidad <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="universidad"
                            name="universidad"
                            value="<?= e($old['universidad'] ?? '') ?>"
                            placeholder="Ej. Universidad Nacional"
                            maxlength="150"
                            autocomplete="organization"
                            required
                            aria-describedby="error-universidad"
                            aria-invalid="<?= isset($fieldErrors['universidad']) ? 'true' : 'false' ?>"
                        >
                        <p class="field-error" id="error-universidad" data-error-for="universidad">
                            <?= e($fieldErrors['universidad'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Correo -->
                    <div class="form-group <?= isset($fieldErrors['correo']) ? 'has-error' : '' ?>">
                        <label for="correo">
                            Correo <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            value="<?= e($old['correo'] ?? '') ?>"
                            placeholder="ejemplo@correo.com"
                            maxlength="180"
                            autocomplete="email"
                            required
                            aria-describedby="error-correo"
                            aria-invalid="<?= isset($fieldErrors['correo']) ? 'true' : 'false' ?>"
                        >
                        <p class="field-error" id="error-correo" data-error-for="correo">
                            <?= e($fieldErrors['correo'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Teléfono -->
                    <div class="form-group <?= isset($fieldErrors['telefono']) ? 'has-error' : '' ?>">
                        <label for="telefono">
                            Teléfono <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            value="<?= e($old['telefono'] ?? '') ?>"
                            placeholder="Ej. +52 55 1234 5678"
                            maxlength="25"
                            autocomplete="tel"
                            required
                            aria-describedby="error-telefono"
                            aria-invalid="<?= isset($fieldErrors['telefono']) ? 'true' : 'false' ?>"
                        >
                        <p class="field-error" id="error-telefono" data-error-for="telefono">
                            <?= e($fieldErrors['telefono'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Fecha de cumpleaños -->
                    <div class="form-group form-group--full <?= isset($fieldErrors['fecha_cumpleanos']) ? 'has-error' : '' ?>">
                        <label for="fecha_cumpleanos">
                            Fecha de cumpleaños <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="date"
                            id="fecha_cumpleanos"
                            name="fecha_cumpleanos"
                            value="<?= e($old['fecha_cumpleanos'] ?? '') ?>"
                            min="1900-01-01"
                            max="<?= e($today) ?>"
                            autocomplete="bday"
                            required
                            aria-describedby="error-fecha_cumpleanos"
                            aria-invalid="<?= isset($fieldErrors['fecha_cumpleanos']) ? 'true' : 'false' ?>"
                        >
                        <p class="field-error" id="error-fecha_cumpleanos" data-error-for="fecha_cumpleanos">
                            <?= e($fieldErrors['fecha_cumpleanos'] ?? '') ?>
                        </p>
                    </div>
                </div>

                <div class="form-footer">
                    <p class="required-note"><span class="required">*</span> Todos los campos son obligatorios.</p>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-spinner" aria-hidden="true"></span>
                        <span class="btn-label">Enviar registro</span>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="assets/js/app.js" defer></script>
</body>
</html>

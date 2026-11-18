<?php
/**
 * Sección privada de consulta de registros en cards + modal de detalle (/alumnos).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

sendSecurityHeaders();

$baseUrl = getBaseUrlPath();
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$selfUrl = $baseUrl . '/alumnos';
if (is_string($requestPath) && preg_match('#/alumnos(\.php)?/?$#', $requestPath)) {
    $selfUrl = $requestPath;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!verifyCsrfToken(is_string($token) ? $token : null)) {
        $_SESSION['alumnos_auth_error'] = 'La sesión expiró. Recarga la página e inténtalo nuevamente.';
        header('Location: ' . $selfUrl, true, 303);
        exit;
    }

    if ($action === 'logout') {
        unset($_SESSION['alumnos_authenticated']);
        header('Location: ' . $selfUrl, true, 303);
        exit;
    }

    if ($action === 'login') {
        if (!isAdminPasswordConfigured()) {
            $_SESSION['alumnos_auth_error'] = 'Falta definir ADMIN_ACCESS_PASSWORD en config/config.php para habilitar el acceso.';
            header('Location: ' . $selfUrl, true, 303);
            exit;
        }

        $password = (string) ($_POST['password'] ?? '');
        if (verifyAdminPassword($password)) {
            session_regenerate_id(true);
            $_SESSION['alumnos_authenticated'] = true;
            unset($_SESSION['alumnos_auth_error']);
            header('Location: ' . $selfUrl, true, 303);
            exit;
        }

        usleep(300000);
        $_SESSION['alumnos_auth_error'] = 'La contraseña ingresada no es correcta.';
        header('Location: ' . $selfUrl, true, 303);
        exit;
    }
}

$csrfToken = getCsrfToken();
$authError = $_SESSION['alumnos_auth_error'] ?? '';
unset($_SESSION['alumnos_auth_error']);

$isAuthenticated = isAdminAuthenticated();
$passwordConfigured = isAdminPasswordConfigured();

$search = '';
$page = 1;
$perPage = 12;
$totalRecords = 0;
$totalPages = 1;
$alumnos = [];
$dbError = '';

if ($isAuthenticated) {
    $rawSearch = trim((string) ($_GET['q'] ?? ''));
    $search = function_exists('mb_substr')
        ? mb_substr($rawSearch, 0, 100, 'UTF-8')
        : substr($rawSearch, 0, 100);

    $rawPage = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $page = ($rawPage !== false && $rawPage !== null) ? $rawPage : 1;

    if (!isDatabaseConfigured()) {
        $dbError = 'Falta configurar config/config.php con las credenciales de la base de datos.';
    } else {
        try {
            $pdo = getDatabaseConnection();
            $whereSql = '';
            $likeValue = '';

            if ($search !== '') {
                $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
                $likeValue = '%' . $escapedSearch . '%';
                $whereSql = ' WHERE aloNombreCompleto LIKE :q_nombre
                                 OR aloCarrera LIKE :q_carrera
                                 OR aloUniversidad LIKE :q_universidad';
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM alumnos' . $whereSql);
            if ($search !== '') {
                $countStmt->bindValue(':q_nombre', $likeValue, PDO::PARAM_STR);
                $countStmt->bindValue(':q_carrera', $likeValue, PDO::PARAM_STR);
                $countStmt->bindValue(':q_universidad', $likeValue, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $totalRecords = (int) $countStmt->fetchColumn();

            $totalPages = max(1, (int) ceil($totalRecords / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;

            $listSql = 'SELECT
                            idAlumno,
                            aloNombreCompleto,
                            aloCarrera,
                            aloUniversidad,
                            aloCorreo,
                            aloTelefono,
                            aloFechaNacimiento
                        FROM alumnos'
                        . $whereSql .
                        ' ORDER BY idAlumno DESC
                          LIMIT :limit OFFSET :offset';

            $listStmt = $pdo->prepare($listSql);
            if ($search !== '') {
                $listStmt->bindValue(':q_nombre', $likeValue, PDO::PARAM_STR);
                $listStmt->bindValue(':q_carrera', $likeValue, PDO::PARAM_STR);
                $listStmt->bindValue(':q_universidad', $likeValue, PDO::PARAM_STR);
            }
            $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $listStmt->execute();

            $alumnos = $listStmt->fetchAll();
        } catch (Throwable $e) {
            error_log('[Fundacion Taboada] Error al consultar alumnos: ' . $e->getMessage());
            $debugInfo = (defined('APP_DEBUG') && APP_DEBUG) ? ' (' . $e->getMessage() . ')' : '';
            $dbError = 'No se pudieron cargar los registros en este momento.' . $debugInfo;
        }
    }
}

if (!function_exists('buildAlumnosPageUrl')) {
    /**
     * Construye URL de paginación preservando el término de búsqueda.
     */
    function buildAlumnosPageUrl(string $baseUrlPath, string $searchQuery, int $targetPage): string
    {
        $params = [];
        if ($searchQuery !== '') {
            $params['q'] = $searchQuery;
        }
        if ($targetPage > 1) {
            $params['pagina'] = $targetPage;
        }
        $queryString = http_build_query($params);

        return $queryString === '' ? $baseUrlPath : ($baseUrlPath . '?' . $queryString);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Registros de Alumnos | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($baseUrl . '/assets/css/styles.css') ?>">
    <link rel="stylesheet" href="<?= e($baseUrl . '/assets/css/alumnos.css') ?>">
</head>
<body>
    <?php if (!$isAuthenticated): ?>
        <div class="page-wrapper">
            <main class="form-card auth-card" aria-labelledby="authTitle">
                <header class="form-header">
                    <span class="brand-badge"><?= e(APP_NAME) ?></span>
                    <h1 id="authTitle">Acceso a Registros</h1>
                    <p class="form-subtitle">
                        Ingresa la contraseña de administración para consultar los alumnos registrados.
                    </p>
                </header>

                <?php if (!$passwordConfigured): ?>
                    <div class="alert alert--error is-visible" role="alert">
                        Falta configurar <code>ADMIN_ACCESS_PASSWORD</code> en <code>config/config.php</code> dentro del servidor.
                    </div>
                <?php elseif ($authError !== ''): ?>
                    <div class="alert alert--error is-visible" role="alert">
                        <?= e($authError) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= e($selfUrl) ?>" method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label for="adminPassword">
                            Contraseña <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="password-field">
                            <input
                                type="password"
                                id="adminPassword"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                required
                                <?= !$passwordConfigured ? 'disabled' : 'autofocus' ?>
                            >
                            <button
                                type="button"
                                class="btn-toggle-pass"
                                id="togglePasswordBtn"
                                aria-label="Mostrar u ocultar contraseña"
                            >
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <div class="auth-actions">
                        <button
                            type="submit"
                            class="btn-submit auth-submit"
                            <?= !$passwordConfigured ? 'disabled' : '' ?>
                        >
                            Ingresar
                        </button>
                    </div>
                </form>
            </main>
        </div>
    <?php else: ?>
        <div class="directory-wrapper">
            <header class="directory-header">
                <div class="directory-heading">
                    <span class="brand-badge"><?= e(APP_NAME) ?></span>
                    <h1>Alumnos Registrados</h1>
                    <p class="directory-summary">
                        <?php if ($search !== ''): ?>
                            <?= e((string) $totalRecords) ?> resultado<?= $totalRecords === 1 ? '' : 's' ?> para "<?= e($search) ?>"
                        <?php else: ?>
                            <?= e((string) $totalRecords) ?> registro<?= $totalRecords === 1 ? '' : 's' ?> en total
                        <?php endif; ?>
                    </p>
                </div>

                <form action="<?= e($selfUrl) ?>" method="POST" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn-logout">Cerrar sesión</button>
                </form>
            </header>

            <section class="directory-toolbar" aria-label="Buscador de alumnos">
                <form action="<?= e($selfUrl) ?>" method="GET" class="search-form">
                    <div class="search-input-wrap">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path stroke-linecap="round" d="M20 20l-3.5-3.5" />
                        </svg>
                        <input
                            type="search"
                            name="q"
                            id="searchInput"
                            value="<?= e($search) ?>"
                            placeholder="Buscar por nombre, carrera o universidad..."
                            maxlength="100"
                            aria-label="Buscar por nombre, carrera o universidad"
                        >
                    </div>
                    <button type="submit" class="btn-search">Buscar</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= e($selfUrl) ?>" class="btn-clear-search">Limpiar</a>
                    <?php endif; ?>
                </form>
            </section>

            <?php if ($dbError !== ''): ?>
                <div class="alert alert--error is-visible" role="alert">
                    <?= e($dbError) ?>
                </div>
            <?php elseif (empty($alumnos)): ?>
                <section class="empty-state" aria-live="polite">
                    <div class="empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 19.5a7.5 7.5 0 0115 0" />
                        </svg>
                    </div>
                    <h2>
                        <?= $search !== '' ? 'No se encontraron coincidencias' : 'Aún no hay registros guardados' ?>
                    </h2>
                    <p>
                        <?= $search !== ''
                            ? 'Intenta buscar con otro nombre, carrera o universidad.'
                            : 'Cuando alguien complete el formulario de registro, aparecerá aquí.' ?>
                    </p>
                    <?php if ($search !== ''): ?>
                        <a href="<?= e($selfUrl) ?>" class="btn-secondary">Ver todos los registros</a>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <main class="cards-grid" aria-label="Lista de alumnos registrados">
                    <?php foreach ($alumnos as $alumno): ?>
                        <?php
                        $idAlumno = (int) $alumno['idAlumno'];
                        $nombre = (string) $alumno['aloNombreCompleto'];
                        $carrera = (string) $alumno['aloCarrera'];
                        $universidad = (string) $alumno['aloUniversidad'];
                        $correo = (string) $alumno['aloCorreo'];
                        $telefono = (string) $alumno['aloTelefono'];
                        $fechaIso = $alumno['aloFechaNacimiento'] !== null ? (string) $alumno['aloFechaNacimiento'] : '';
                        $fechaLegible = formatSpanishDate($fechaIso);
                        $thumbUrl = $baseUrl . '/foto.php?id=' . $idAlumno . '&tipo=miniatura';
                        $fullPhotoUrl = $baseUrl . '/foto.php?id=' . $idAlumno . '&tipo=completa';
                        ?>
                        <button
                            type="button"
                            class="student-card"
                            data-id="<?= e((string) $idAlumno) ?>"
                            data-nombre="<?= e($nombre) ?>"
                            data-carrera="<?= e($carrera) ?>"
                            data-universidad="<?= e($universidad) ?>"
                            data-correo="<?= e($correo) ?>"
                            data-telefono="<?= e($telefono) ?>"
                            data-nacimiento="<?= e($fechaLegible) ?>"
                            data-foto-full="<?= e($fullPhotoUrl) ?>"
                        >
                            <div class="card-thumb">
                                <img
                                    src="<?= e($thumbUrl) ?>"
                                    alt="Miniatura de <?= e($nombre) ?>"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                            <div class="card-body">
                                <span class="card-id">Registro #<?= e((string) $idAlumno) ?></span>
                                <h2 class="card-name"><?= e($nombre) ?></h2>
                                <p class="card-detail">
                                    <span class="detail-label">Carrera:</span>
                                    <span><?= e($carrera) ?></span>
                                </p>
                                <p class="card-detail">
                                    <span class="detail-label">Universidad:</span>
                                    <span><?= e($universidad) ?></span>
                                </p>
                                <span class="card-cta">Ver ficha completa &rarr;</span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </main>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Paginación de registros">
                        <?php if ($page > 1): ?>
                            <a
                                href="<?= e(buildAlumnosPageUrl($selfUrl, $search, $page - 1)) ?>"
                                class="page-link"
                            >
                                &larr; Anterior
                            </a>
                        <?php else: ?>
                            <span class="page-link is-disabled" aria-disabled="true">&larr; Anterior</span>
                        <?php endif; ?>

                        <div class="page-numbers">
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            if ($startPage > 1):
                            ?>
                                <a href="<?= e(buildAlumnosPageUrl($selfUrl, $search, 1)) ?>" class="page-link">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="page-ellipsis" aria-hidden="true">…</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <?php if ($p === $page): ?>
                                    <span class="page-link is-active" aria-current="page"><?= e((string) $p) ?></span>
                                <?php else: ?>
                                    <a href="<?= e(buildAlumnosPageUrl($selfUrl, $search, $p)) ?>" class="page-link"><?= e((string) $p) ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="page-ellipsis" aria-hidden="true">…</span>
                                <?php endif; ?>
                                <a href="<?= e(buildAlumnosPageUrl($selfUrl, $search, $totalPages)) ?>" class="page-link"><?= e((string) $totalPages) ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a
                                href="<?= e(buildAlumnosPageUrl($selfUrl, $search, $page + 1)) ?>"
                                class="page-link"
                            >
                                Siguiente &rarr;
                            </a>
                        <?php else: ?>
                            <span class="page-link is-disabled" aria-disabled="true">Siguiente &rarr;</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Modal de detalle del alumno -->
        <div
            class="modal-backdrop"
            id="studentModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modalNombre"
            hidden
        >
            <div class="modal-dialog">
                <button
                    type="button"
                    class="modal-close"
                    id="modalCloseBtn"
                    aria-label="Cerrar ventana"
                >
                    &times;
                </button>

                <div class="modal-layout">
                    <div class="modal-photo-panel">
                        <div class="modal-photo-frame" id="modalPhotoFrame">
                            <div class="modal-photo-loader" id="modalPhotoLoader" aria-label="Cargando fotografía">
                                <span class="loader-ring" aria-hidden="true"></span>
                                <span>Cargando foto original...</span>
                            </div>
                            <img id="modalFoto" src="" alt="" hidden>
                            <p class="modal-photo-error" id="modalPhotoError" hidden>
                                No se pudo cargar la fotografía original.
                            </p>
                        </div>
                    </div>

                    <div class="modal-info-panel">
                        <span class="modal-badge" id="modalIdBadge">Registro</span>
                        <h2 class="modal-name" id="modalNombre"></h2>

                        <dl class="modal-details">
                            <div class="modal-detail-item">
                                <dt>Carrera</dt>
                                <dd id="modalCarrera"></dd>
                            </div>
                            <div class="modal-detail-item">
                                <dt>Universidad</dt>
                                <dd id="modalUniversidad"></dd>
                            </div>
                            <div class="modal-detail-item">
                                <dt>Correo</dt>
                                <dd><a id="modalCorreo" href="#"></a></dd>
                            </div>
                            <div class="modal-detail-item">
                                <dt>Teléfono</dt>
                                <dd><a id="modalTelefono" href="#"></a></dd>
                            </div>
                            <div class="modal-detail-item">
                                <dt>Fecha de cumpleaños</dt>
                                <dd id="modalNacimiento"></dd>
                            </div>
                        </dl>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary modal-btn-close" id="modalFooterCloseBtn">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="<?= e($baseUrl . '/assets/js/alumnos.js') ?>" defer></script>
</body>
</html>

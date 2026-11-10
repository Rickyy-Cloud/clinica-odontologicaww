<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';

/** Inicia la sesión con cookies endurecidas. */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

/** Responde JSON y termina la ejecución. */
function json_out(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_out(string $mensaje, int $codigo = 400): never
{
    json_out(['ok' => false, 'mensaje' => $mensaje], $codigo);
}

/** Exige sesión iniciada (para endpoints AJAX). */
function requerir_auth(): array
{
    iniciar_sesion();
    if (empty($_SESSION['id_usuario'])) {
        error_out('Sesión expirada. Inicia sesión nuevamente.', 401);
    }
    return [
        'id_usuario' => (int) $_SESSION['id_usuario'],
        'usuario'    => (string) $_SESSION['usuario'],
        'rol'        => (string) $_SESSION['rol'],
    ];
}

/** Valida el token CSRF en peticiones que modifican datos. */
function verificar_csrf(): void
{
    $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($enviado) || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        error_out('Token de seguridad inválido. Recarga la página.', 403);
    }
}

/** Lee el cuerpo JSON de la petición. */
function entrada_json(): array
{
    $crudo = file_get_contents('php://input');
    if ($crudo === false || $crudo === '') {
        return [];
    }
    $datos = json_decode($crudo, true);
    if (!is_array($datos)) {
        error_out('Cuerpo de la petición inválido.');
    }
    return $datos;
}

function texto(mixed $valor, int $max = 255): string
{
    $limpio = trim((string) $valor);
    return mb_substr($limpio, 0, $max);
}

function fecha_valida(string $fecha): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

/** Convierte UTF-8 a ISO-8859-1 para FPDF. */
function pdf_txt(?string $s): string
{
    $s = (string) $s;
    $r = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
    return $r === false ? $s : $r;
}

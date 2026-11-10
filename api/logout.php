<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

iniciar_sesion();
verificar_csrf();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

json_out(['ok' => true]);

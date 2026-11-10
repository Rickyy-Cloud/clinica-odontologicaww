<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

iniciar_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_out('Método no permitido.', 405);
}
verificar_csrf();

$d = entrada_json();
$usuario  = texto($d['usuario'] ?? '', 50);
$password = (string) ($d['password'] ?? '');

if ($usuario === '' || $password === '') {
    error_out('Ingresa usuario y contraseña.');
}

try {
    $st = db()->prepare('SELECT id_usuario, usuario, password, rol FROM usuarios WHERE usuario = ? LIMIT 1');
    $st->execute([$usuario]);
    $u = $st->fetch();

    $ok = false;
    if ($u) {
        $guardada = (string) $u['password'];
        if (password_get_info($guardada)['algo'] !== null) {
            // Contraseña ya almacenada con hash seguro
            $ok = password_verify($password, $guardada);
            if ($ok && password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
                $up = db()->prepare('UPDATE usuarios SET password = ? WHERE id_usuario = ?');
                $up->execute([password_hash($password, PASSWORD_DEFAULT), $u['id_usuario']]);
            }
        } else {
            // Compatibilidad: clave heredada en texto plano -> se migra a hash al primer ingreso
            $ok = hash_equals($guardada, $password);
            if ($ok) {
                try {
                    $up = db()->prepare('UPDATE usuarios SET password = ? WHERE id_usuario = ?');
                    $up->execute([password_hash($password, PASSWORD_DEFAULT), $u['id_usuario']]);
                } catch (PDOException $e) {
                    // La columna es muy corta para el hash: ver README (ALTER TABLE)
                }
            }
        }
    } else {
        // Evita revelar si el usuario existe (tiempo de respuesta similar)
        password_verify($password, '$2y$10$usesomesillystringforsaltuQ6qG1ZQ3o8mT3VYe2X1F1m7b1b3oW');
    }

    if (!$ok) {
        usleep(300000);
        error_out('Usuario o contraseña incorrectos.', 401);
    }

    session_regenerate_id(true);
    $_SESSION['id_usuario'] = (int) $u['id_usuario'];
    $_SESSION['usuario']    = $u['usuario'];
    $_SESSION['rol']        = (string) $u['rol'];
    $_SESSION['csrf']       = bin2hex(random_bytes(32));

    json_out(['ok' => true]);
} catch (PDOException $e) {
    error_out('Error de base de datos.', 500);
}

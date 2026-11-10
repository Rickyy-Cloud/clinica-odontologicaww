<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

requerir_auth();
$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET') {
    verificar_csrf();
}

function validar_procedimiento(array $d): array
{
    $nombre = texto($d['nombre_procedimiento'] ?? '', 100);
    $desc   = texto($d['descripcion'] ?? '', 500);
    $costo  = $d['costo'] ?? '';

    if ($nombre === '') {
        error_out('El nombre del procedimiento es obligatorio.');
    }
    if (!is_numeric($costo) || (float) $costo < 0 || (float) $costo > 99999999) {
        error_out('El costo debe ser un número mayor o igual a 0.');
    }
    return [
        'nombre_procedimiento' => $nombre,
        'descripcion'          => $desc === '' ? null : $desc,
        'costo'                => round((float) $costo, 2),
    ];
}

try {
    $pdo = db();

    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $st = $pdo->prepare('SELECT * FROM procedimientos WHERE id_procedimiento = ?');
                $st->execute([(int) $_GET['id']]);
                $fila = $st->fetch();
                if (!$fila) {
                    error_out('Procedimiento no encontrado.', 404);
                }
                json_out(['ok' => true, 'datos' => $fila]);
            }
            $st = $pdo->query('SELECT * FROM procedimientos ORDER BY nombre_procedimiento');
            json_out(['ok' => true, 'datos' => $st->fetchAll()]);

        case 'POST':
            $p = validar_procedimiento(entrada_json());
            $st = $pdo->prepare(
                'INSERT INTO procedimientos (nombre_procedimiento, descripcion, costo)
                 VALUES (:nombre_procedimiento, :descripcion, :costo)'
            );
            $st->execute($p);
            json_out(['ok' => true, 'mensaje' => 'Procedimiento registrado.'], 201);

        case 'PUT':
            $d  = entrada_json();
            $id = (int) ($d['id_procedimiento'] ?? 0);
            if ($id <= 0) {
                error_out('Procedimiento no válido.');
            }
            $p = validar_procedimiento($d);
            $p['id'] = $id;
            $st = $pdo->prepare(
                'UPDATE procedimientos
                 SET nombre_procedimiento = :nombre_procedimiento, descripcion = :descripcion, costo = :costo
                 WHERE id_procedimiento = :id'
            );
            $st->execute($p);
            json_out(['ok' => true, 'mensaje' => 'Procedimiento actualizado.']);

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                error_out('Procedimiento no válido.');
            }
            $c = $pdo->prepare('SELECT COUNT(*) FROM historias_clinicas WHERE id_procedimiento = ?');
            $c->execute([$id]);
            if ((int) $c->fetchColumn() > 0) {
                error_out('No se puede eliminar: el procedimiento ya fue usado en historias clínicas.', 409);
            }
            $st = $pdo->prepare('DELETE FROM procedimientos WHERE id_procedimiento = ?');
            $st->execute([$id]);
            json_out(['ok' => true, 'mensaje' => 'Procedimiento eliminado.']);

        default:
            error_out('Método no permitido.', 405);
    }
} catch (PDOException $e) {
    error_out('Error de base de datos.', 500);
}

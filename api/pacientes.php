<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

requerir_auth();
$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET') {
    verificar_csrf();
}

/** Valida y normaliza los datos de un paciente. */
function validar_paciente(array $d): array
{
    $p = [
        'documento'        => texto($d['documento'] ?? '', 20),
        'nombres'          => texto($d['nombres'] ?? '', 80),
        'apellidos'        => texto($d['apellidos'] ?? '', 80),
        'telefono'         => texto($d['telefono'] ?? '', 20),
        'email'            => texto($d['email'] ?? '', 100),
        'fecha_nacimiento' => texto($d['fecha_nacimiento'] ?? '', 10),
        'direccion'        => texto($d['direccion'] ?? '', 255),
    ];

    if ($p['documento'] === '' || $p['nombres'] === '' || $p['apellidos'] === '') {
        error_out('Documento, nombres y apellidos son obligatorios.');
    }
    if ($p['email'] !== '' && !filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
        error_out('El correo electrónico no es válido.');
    }
    if ($p['fecha_nacimiento'] !== '') {
        if (!fecha_valida($p['fecha_nacimiento']) || $p['fecha_nacimiento'] > date('Y-m-d')) {
            error_out('La fecha de nacimiento no es válida.');
        }
    }
    // Campos opcionales vacíos -> NULL
    foreach (['telefono', 'email', 'fecha_nacimiento', 'direccion'] as $c) {
        if ($p[$c] === '') {
            $p[$c] = null;
        }
    }
    return $p;
}

try {
    $pdo = db();

    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $st = $pdo->prepare('SELECT * FROM pacientes WHERE id_paciente = ?');
                $st->execute([(int) $_GET['id']]);
                $fila = $st->fetch();
                if (!$fila) {
                    error_out('Paciente no encontrado.', 404);
                }
                json_out(['ok' => true, 'datos' => $fila]);
            }

            $q = texto($_GET['q'] ?? '', 80);
            if ($q !== '') {
                $like = '%' . $q . '%';
                $st = $pdo->prepare(
                    'SELECT * FROM pacientes
                     WHERE documento LIKE ? OR nombres LIKE ? OR apellidos LIKE ?
                     ORDER BY apellidos, nombres LIMIT 300'
                );
                $st->execute([$like, $like, $like]);
            } else {
                $st = $pdo->query('SELECT * FROM pacientes ORDER BY apellidos, nombres LIMIT 300');
            }
            json_out(['ok' => true, 'datos' => $st->fetchAll()]);

        case 'POST':
            $p = validar_paciente(entrada_json());
            $dup = $pdo->prepare('SELECT 1 FROM pacientes WHERE documento = ?');
            $dup->execute([$p['documento']]);
            if ($dup->fetch()) {
                error_out('Ya existe un paciente con ese documento.', 409);
            }
            $st = $pdo->prepare(
                'INSERT INTO pacientes (documento, nombres, apellidos, telefono, email, fecha_nacimiento, direccion)
                 VALUES (:documento, :nombres, :apellidos, :telefono, :email, :fecha_nacimiento, :direccion)'
            );
            $st->execute($p);
            json_out(['ok' => true, 'mensaje' => 'Paciente registrado.', 'id' => (int) $pdo->lastInsertId()], 201);

        case 'PUT':
            $d  = entrada_json();
            $id = (int) ($d['id_paciente'] ?? 0);
            if ($id <= 0) {
                error_out('Paciente no válido.');
            }
            $p = validar_paciente($d);
            $dup = $pdo->prepare('SELECT 1 FROM pacientes WHERE documento = ? AND id_paciente <> ?');
            $dup->execute([$p['documento'], $id]);
            if ($dup->fetch()) {
                error_out('Otro paciente ya tiene ese documento.', 409);
            }
            $p['id'] = $id;
            $st = $pdo->prepare(
                'UPDATE pacientes SET documento = :documento, nombres = :nombres, apellidos = :apellidos,
                        telefono = :telefono, email = :email, fecha_nacimiento = :fecha_nacimiento,
                        direccion = :direccion
                 WHERE id_paciente = :id'
            );
            $st->execute($p);
            json_out(['ok' => true, 'mensaje' => 'Paciente actualizado.']);

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                error_out('Paciente no válido.');
            }
            $c = $pdo->prepare('SELECT COUNT(*) FROM historias_clinicas WHERE id_paciente = ?');
            $c->execute([$id]);
            if ((int) $c->fetchColumn() > 0) {
                error_out('No se puede eliminar: el paciente tiene historia clínica registrada.', 409);
            }
            $st = $pdo->prepare('DELETE FROM pacientes WHERE id_paciente = ?');
            $st->execute([$id]);
            json_out(['ok' => true, 'mensaje' => 'Paciente eliminado.']);

        default:
            error_out('Método no permitido.', 405);
    }
} catch (PDOException $e) {
    error_out('Error de base de datos.', 500);
}

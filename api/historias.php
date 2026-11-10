<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

$usuario = requerir_auth();
$metodo  = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET') {
    verificar_csrf();
}

try {
    $pdo = db();

    switch ($metodo) {
        case 'GET':
            $idPaciente = (int) ($_GET['id_paciente'] ?? 0);
            if ($idPaciente <= 0) {
                error_out('Selecciona un paciente.');
            }
            $pac = $pdo->prepare('SELECT * FROM pacientes WHERE id_paciente = ?');
            $pac->execute([$idPaciente]);
            $paciente = $pac->fetch();
            if (!$paciente) {
                error_out('Paciente no encontrado.', 404);
            }

            $st = $pdo->prepare(
                'SELECT h.id_historia, h.fecha_atencion, h.observaciones,
                        p.id_procedimiento, p.nombre_procedimiento, p.costo,
                        u.usuario AS atendido_por
                 FROM historias_clinicas h
                 INNER JOIN procedimientos p ON p.id_procedimiento = h.id_procedimiento
                 LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
                 WHERE h.id_paciente = ?
                 ORDER BY h.fecha_atencion DESC, h.id_historia DESC'
            );
            $st->execute([$idPaciente]);
            $filas = $st->fetchAll();
            $total = array_sum(array_map(static fn($f) => (float) $f['costo'], $filas));

            json_out(['ok' => true, 'paciente' => $paciente, 'historia' => $filas, 'total' => round($total, 2)]);

        case 'POST':
            // Registra una atención: una fila de historias_clinicas por cada procedimiento asignado
            $d      = entrada_json();
            $idPac  = (int) ($d['id_paciente'] ?? 0);
            $fecha  = texto($d['fecha_atencion'] ?? '', 10);
            $obs    = texto($d['observaciones'] ?? '', 2000);
            $procs  = $d['procedimientos'] ?? [];

            if ($idPac <= 0) {
                error_out('Selecciona un paciente.');
            }
            if (!fecha_valida($fecha) || $fecha > date('Y-m-d')) {
                error_out('La fecha de atención no es válida.');
            }
            if (!is_array($procs) || count($procs) === 0) {
                error_out('Selecciona al menos un procedimiento.');
            }
            $procs = array_values(array_unique(array_map('intval', $procs)));

            $v = $pdo->prepare('SELECT 1 FROM pacientes WHERE id_paciente = ?');
            $v->execute([$idPac]);
            if (!$v->fetch()) {
                error_out('Paciente no encontrado.', 404);
            }
            $marcas = implode(',', array_fill(0, count($procs), '?'));
            $vp = $pdo->prepare("SELECT COUNT(*) FROM procedimientos WHERE id_procedimiento IN ($marcas)");
            $vp->execute($procs);
            if ((int) $vp->fetchColumn() !== count($procs)) {
                error_out('Alguno de los procedimientos no existe.');
            }

            $pdo->beginTransaction();
            $ins = $pdo->prepare(
                'INSERT INTO historias_clinicas (id_paciente, id_procedimiento, fecha_atencion, observaciones, id_usuario)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($procs as $idProc) {
                $ins->execute([$idPac, $idProc, $fecha, $obs === '' ? null : $obs, $usuario['id_usuario']]);
            }
            $pdo->commit();
            json_out(['ok' => true, 'mensaje' => 'Atención registrada en la historia clínica.'], 201);

        case 'PUT':
            // Corrección de fecha u observaciones de un registro
            $d  = entrada_json();
            $id = (int) ($d['id_historia'] ?? 0);
            $fecha = texto($d['fecha_atencion'] ?? '', 10);
            $obs   = texto($d['observaciones'] ?? '', 2000);
            if ($id <= 0) {
                error_out('Registro no válido.');
            }
            if (!fecha_valida($fecha) || $fecha > date('Y-m-d')) {
                error_out('La fecha de atención no es válida.');
            }
            $st = $pdo->prepare('UPDATE historias_clinicas SET fecha_atencion = ?, observaciones = ? WHERE id_historia = ?');
            $st->execute([$fecha, $obs === '' ? null : $obs, $id]);
            json_out(['ok' => true, 'mensaje' => 'Registro actualizado.']);

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                error_out('Registro no válido.');
            }
            $st = $pdo->prepare('DELETE FROM historias_clinicas WHERE id_historia = ?');
            $st->execute([$id]);
            json_out(['ok' => true, 'mensaje' => 'Registro eliminado.']);

        default:
            error_out('Método no permitido.', 405);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_out('Error de base de datos.', 500);
}

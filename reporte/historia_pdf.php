<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/helpers.php';

iniciar_sesion();
if (empty($_SESSION['id_usuario'])) {
    http_response_code(401);
    exit('Sesión expirada.');
}

$fpdfRuta = __DIR__ . '/../fpdf/fpdf.php';
if (!is_file($fpdfRuta)) {
    http_response_code(500);
    exit('Falta la librería FPDF. Copia fpdf.php (y la carpeta font) dentro de /fpdf.');
}
require_once $fpdfRuta;

$idPaciente = (int) ($_GET['id_paciente'] ?? 0);
if ($idPaciente <= 0) {
    http_response_code(400);
    exit('Paciente no válido.');
}

try {
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM pacientes WHERE id_paciente = ?');
    $st->execute([$idPaciente]);
    $pac = $st->fetch();
    if (!$pac) {
        http_response_code(404);
        exit('Paciente no encontrado.');
    }

    $st = $pdo->prepare(
        'SELECT h.fecha_atencion, h.observaciones, p.nombre_procedimiento, p.costo, u.usuario AS atendido_por
         FROM historias_clinicas h
         INNER JOIN procedimientos p ON p.id_procedimiento = h.id_procedimiento
         LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
         WHERE h.id_paciente = ?
         ORDER BY h.fecha_atencion ASC, h.id_historia ASC'
    );
    $st->execute([$idPaciente]);
    $filas = $st->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    exit('Error de base de datos.');
}

class HistoriaPDF extends FPDF
{
    public function Header(): void
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 8, pdf_txt('Clínica Odontológica'), 0, 1, 'C');
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 6, pdf_txt('Historia Clínica del Paciente'), 0, 1, 'C');
        $this->Ln(2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(4);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, pdf_txt('Generado el ' . date('d/m/Y H:i') . ' - Página ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
}

$pdf = new HistoriaPDF('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

// Datos del paciente
$edad = '';
if (!empty($pac['fecha_nacimiento'])) {
    $edad = (string) (new DateTime($pac['fecha_nacimiento']))->diff(new DateTime())->y . ' años';
}
$datos = [
    'Documento'        => $pac['documento'],
    'Paciente'         => $pac['nombres'] . ' ' . $pac['apellidos'],
    'Teléfono'         => $pac['telefono'] ?? '',
    'Correo'           => $pac['email'] ?? '',
    'Fecha nacimiento' => !empty($pac['fecha_nacimiento'])
        ? date('d/m/Y', strtotime($pac['fecha_nacimiento'])) . ($edad ? " ($edad)" : '') : '',
    'Dirección'        => $pac['direccion'] ?? '',
];
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, pdf_txt('Datos del paciente'), 0, 1);
foreach ($datos as $etq => $val) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 6, pdf_txt($etq . ':'), 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, pdf_txt((string) $val), 0, 'L');
}
$pdf->Ln(4);

// Atenciones
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, pdf_txt('Atenciones registradas'), 0, 1);

if (count($filas) === 0) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 8, pdf_txt('El paciente aún no tiene atenciones registradas.'), 0, 1);
} else {
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(28, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(95, 7, 'Procedimiento', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Atendido por', 1, 0, 'C', true);
    $pdf->Cell(37, 7, 'Costo', 1, 1, 'C', true);

    $total = 0.0;
    foreach ($filas as $f) {
        $total += (float) $f['costo'];
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(28, 7, date('d/m/Y', strtotime($f['fecha_atencion'])), 'LR', 0, 'C');
        $pdf->Cell(95, 7, pdf_txt($f['nombre_procedimiento']), 'LR', 0);
        $pdf->Cell(30, 7, pdf_txt((string) $f['atendido_por']), 'LR', 0, 'C');
        $pdf->Cell(37, 7, '$ ' . number_format((float) $f['costo'], 2, ',', '.'), 'LR', 1, 'R');

        $obs = trim((string) $f['observaciones']);
        if ($obs !== '') {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(28, 6, '', 'LR', 0);
            $pdf->MultiCell(162, 5, pdf_txt('Obs.: ' . $obs), 'R', 'L');
        }
        $pdf->Cell(190, 0, '', 'T', 1);
    }

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(153, 8, 'TOTAL', 1, 0, 'R', true);
    $pdf->Cell(37, 8, '$ ' . number_format($total, 2, ',', '.'), 1, 1, 'R', true);
}

$nombreArchivo = 'historia_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $pac['documento']) . '.pdf';
$pdf->Output('D', $nombreArchivo);

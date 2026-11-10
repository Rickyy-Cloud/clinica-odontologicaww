<?php
declare(strict_types=1);
require_once __DIR__ . '/config/helpers.php';

iniciar_sesion();
if (empty($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
$usuario = htmlspecialchars((string) $_SESSION['usuario'], ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
    <title>Panel | Clínica Odontológica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="panel.php">Clínica Odontológica</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0" id="navModulos">
                <li class="nav-item"><a class="nav-link active" href="#" data-modulo="inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-modulo="pacientes">Pacientes</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-modulo="procedimientos">Procedimientos</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-modulo="historia">Historia clínica</a></li>
            </ul>
            <span class="navbar-text me-3">Usuario: <strong><?= $usuario ?></strong></span>
            <button class="btn btn-outline-light btn-sm" id="btnSalir">Cerrar sesión</button>
        </div>
    </div>
</nav>

<!-- Zona de mensajes -->
<div class="container mt-3" id="mensajes"></div>

<main class="container py-4">

    <!-- INICIO -->
    <section id="mod-inicio">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
            <h1 class="display-6 fw-bold">Bienvenido, <?= $usuario ?></h1>
            <p class="lead mb-0">Gestiona pacientes, procedimientos odontológicos y las historias clínicas desde el menú superior.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h5 card-title">Pacientes</h2>
                <p class="card-text">Registra, busca y edita los datos de los pacientes.</p>
                <a href="#" class="btn btn-primary" data-ir="pacientes">Ir a pacientes</a>
            </div></div></div>
            <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h5 card-title">Procedimientos</h2>
                <p class="card-text">Administra el catálogo de procedimientos y sus costos.</p>
                <a href="#" class="btn btn-primary" data-ir="procedimientos">Ir a procedimientos</a>
            </div></div></div>
            <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h5 card-title">Historia clínica</h2>
                <p class="card-text">Registra atenciones y descarga la historia en PDF.</p>
                <a href="#" class="btn btn-primary" data-ir="historia">Ir a historias</a>
            </div></div></div>
        </div>
    </section>

    <!-- PACIENTES -->
    <section id="mod-pacientes" class="d-none">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h4 mb-0">Pacientes</h2>
            <div class="d-flex gap-2">
                <input type="search" id="buscarPaciente" class="form-control" placeholder="Buscar por documento o nombre">
                <button class="btn btn-primary text-nowrap" id="btnNuevoPaciente">+ Nuevo paciente</button>
            </div>
        </div>
        <div class="card shadow-sm"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Documento</th><th>Paciente</th><th>Teléfono</th><th>Correo</th><th>Nacimiento</th><th class="text-end">Acciones</th></tr>
                </thead>
                <tbody id="tbodyPacientes"></tbody>
            </table>
        </div></div>
    </section>

    <!-- PROCEDIMIENTOS -->
    <section id="mod-procedimientos" class="d-none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Procedimientos odontológicos</h2>
            <button class="btn btn-primary" id="btnNuevoProc">+ Nuevo procedimiento</button>
        </div>
        <div class="card shadow-sm"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Procedimiento</th><th>Descripción</th><th class="text-end">Costo</th><th class="text-end">Acciones</th></tr>
                </thead>
                <tbody id="tbodyProcs"></tbody>
            </table>
        </div></div>
    </section>

    <!-- HISTORIA CLÍNICA -->
    <section id="mod-historia" class="d-none">
        <h2 class="h4 mb-3">Historia clínica</h2>

        <div class="card shadow-sm mb-3"><div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="filtroHistPac" class="form-label">Buscar paciente</label>
                    <input type="search" id="filtroHistPac" class="form-control" placeholder="Documento o nombre">
                </div>
                <div class="col-md-8">
                    <label for="selPacienteHist" class="form-label">Paciente</label>
                    <select id="selPacienteHist" class="form-select"></select>
                </div>
            </div>
        </div></div>

        <div id="panelHistoria" class="d-none">
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div id="datosPaciente"></div>
                    <button class="btn btn-danger" id="btnPDF">Descargar PDF</button>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Registrar nueva atención</div>
                <div class="card-body">
                    <form id="formAtencion" novalidate>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="fechaAtencion" class="form-label">Fecha de atención</label>
                                <input type="date" id="fechaAtencion" class="form-control" required>
                            </div>
                            <div class="col-md-9">
                                <span class="form-label d-block">Procedimientos realizados</span>
                                <div id="checksProcs" class="row row-cols-1 row-cols-md-2 g-1"></div>
                            </div>
                            <div class="col-12">
                                <label for="obsAtencion" class="form-label">Observaciones</label>
                                <textarea id="obsAtencion" class="form-control" rows="3" maxlength="2000"></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-success">Guardar atención</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Historial de atenciones</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Fecha</th><th>Procedimiento</th><th class="text-end">Costo</th><th>Observaciones</th><th>Atendió</th><th class="text-end">Acciones</th></tr>
                        </thead>
                        <tbody id="tbodyHistoria"></tbody>
                        <tfoot><tr class="table-light"><th colspan="2" class="text-end">Total</th><th class="text-end" id="totalHistoria">0.00</th><th colspan="3"></th></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- MODAL PACIENTE -->
<div class="modal fade" id="modalPaciente" tabindex="-1" aria-labelledby="tituloModalPac" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form id="formPaciente" novalidate>
            <div class="modal-header">
                <h3 class="modal-title h5" id="tituloModalPac">Paciente</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pId">
                <div class="row g-3">
                    <div class="col-md-4"><label for="pDocumento" class="form-label">Documento *</label><input type="text" id="pDocumento" class="form-control" maxlength="20" required></div>
                    <div class="col-md-4"><label for="pNombres" class="form-label">Nombres *</label><input type="text" id="pNombres" class="form-control" maxlength="80" required></div>
                    <div class="col-md-4"><label for="pApellidos" class="form-label">Apellidos *</label><input type="text" id="pApellidos" class="form-control" maxlength="80" required></div>
                    <div class="col-md-4"><label for="pTelefono" class="form-label">Teléfono</label><input type="tel" id="pTelefono" class="form-control" maxlength="20"></div>
                    <div class="col-md-4"><label for="pEmail" class="form-label">Correo electrónico</label><input type="email" id="pEmail" class="form-control" maxlength="100"></div>
                    <div class="col-md-4"><label for="pNacimiento" class="form-label">Fecha de nacimiento</label><input type="date" id="pNacimiento" class="form-control"></div>
                    <div class="col-12"><label for="pDireccion" class="form-label">Dirección</label><textarea id="pDireccion" class="form-control" rows="2" maxlength="255"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div></div>
</div>

<!-- MODAL PROCEDIMIENTO -->
<div class="modal fade" id="modalProc" tabindex="-1" aria-labelledby="tituloModalProc" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form id="formProc" novalidate>
            <div class="modal-header">
                <h3 class="modal-title h5" id="tituloModalProc">Procedimiento</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prId">
                <div class="mb-3"><label for="prNombre" class="form-label">Nombre *</label><input type="text" id="prNombre" class="form-control" maxlength="100" required></div>
                <div class="mb-3"><label for="prDesc" class="form-label">Descripción</label><textarea id="prDesc" class="form-control" rows="3" maxlength="500"></textarea></div>
                <div class="mb-3"><label for="prCosto" class="form-label">Costo *</label><input type="number" id="prCosto" class="form-control" min="0" step="0.01" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div></div>
</div>

<!-- MODAL EDITAR REGISTRO DE HISTORIA -->
<div class="modal fade" id="modalRegHist" tabindex="-1" aria-labelledby="tituloModalReg" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form id="formRegHist" novalidate>
            <div class="modal-header">
                <h3 class="modal-title h5" id="tituloModalReg">Editar registro</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rhId">
                <div class="mb-3"><label for="rhFecha" class="form-label">Fecha de atención</label><input type="date" id="rhFecha" class="form-control" required></div>
                <div class="mb-3"><label for="rhObs" class="form-label">Observaciones</label><textarea id="rhObs" class="form-control" rows="3" maxlength="2000"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div></div>
</div>

<!-- MODAL CONFIRMAR ELIMINACIÓN -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="tituloConfirmar" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h3 class="modal-title h5" id="tituloConfirmar">Confirmar</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body" id="textoConfirmar">¿Eliminar el registro?</div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarOk">Eliminar</button>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>

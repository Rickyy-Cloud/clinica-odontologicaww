<?php
declare(strict_types=1);
require_once __DIR__ . '/config/helpers.php';

iniciar_sesion();
if (!empty($_SESSION['id_usuario'])) {
    header('Location: panel.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
    <title>Ingreso | Clínica Odontológica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="col-12 col-sm-8 col-md-6 col-lg-4"><div class="card shadow border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center fw-bold mb-1">Clínica Odontológica</h1>
            <p class="text-center text-muted mb-4">Inicia sesión para continuar</p>

            <div id="alerta" class="d-none" role="alert"></div>

            <form id="formLogin" novalidate>
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" maxlength="50" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="btnLogin">Ingresar</button>
            </form>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/login.js"></script>
</body>
</html>

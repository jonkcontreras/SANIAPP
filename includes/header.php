<?php
$cfg = app_config();
$pageTitle = $pageTitle ?? 'SaniApp';
$user = current_user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#103b68">
  <meta name="description" content="SaniApp ayuda a consultar y controlar las fechas del acta sanitaria de vehículos que transportan alimentos.">
  <title><?= e($pageTitle) ?> | SaniApp</title>
  <link rel="icon" href="assets/saniapp-icon.svg" type="image/svg+xml">
  <link rel="manifest" href="manifest.json">
  <link rel="stylesheet" href="assets/styles.css?v=1.0.0">
  <script defer src="assets/app.js?v=1.0.0"></script>
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>" aria-label="SaniApp inicio">
    <span class="brand-mark"><img src="assets/saniapp-icon.svg" alt="" width="30" height="30"></span>
    <span><strong>SaniApp</strong><small>Control sanitario vehicular</small></span>
  </a>
  <?php if (is_logged_in()): ?>
  <nav class="nav-links" aria-label="Navegación principal">
    <a href="dashboard.php">Inicio</a>
    <a href="acta.php">Mi acta</a>
    <a href="historial.php">Historial</a>
    <a href="perfil.php">Perfil</a>
  </nav>
  <div class="user-menu"><span class="user-greeting">Hola, <?= e($user['nombre'] ?? 'usuario') ?></span><a class="nav-logout" href="logout.php">Salir</a></div>
  <?php else: ?>
  <nav class="nav-links"><a href="login.php">Ingresar</a><a class="nav-cta" href="registro.php">Crear cuenta</a></nav>
  <?php endif; ?>
</header>
<main class="container">
<?php foreach (get_flashes() as $item): ?>
  <div class="alert alert-<?= e($item['type']) ?>" role="status"><?= e($item['message']) ?></div>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$errors = [];
$data = ['nombre'=>'','apellido'=>'','ciudad'=>'','email'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $key => $_) $data[$key] = trim((string)($_POST[$key] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    foreach (['nombre'=>'nombre','apellido'=>'apellido','ciudad'=>'ciudad'] as $key=>$label) {
        if ($data[$key] === '') $errors[] = 'El campo ' . $label . ' es obligatorio.';
        if (strlen($data[$key]) > ($key === 'ciudad' ? 100 : 80)) $errors[] = 'El campo ' . $label . ' es demasiado largo.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';
    if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($password !== $confirm) $errors[] = 'Las contraseñas no coinciden.';
    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([strtolower($data['email'])]);
        if ($stmt->fetch()) $errors[] = 'Ya existe una cuenta con ese correo.';
    }
    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO users (nombre, apellido, ciudad, email, password_hash) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$data['nombre'],$data['apellido'],$data['ciudad'],strtolower($data['email']),password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            flash('success', 'Tu cuenta fue creada. Ya puedes registrar tu acta sanitaria.');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            error_log('SaniApp register error: '.$e->getMessage());
            $errors[] = 'No fue posible crear la cuenta. Verifica que el correo no esté registrado e inténtalo de nuevo.';
        }
    }
}
$pageTitle = 'Crear cuenta';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout auth-layout-narrow">
  <div class="auth-aside compact-aside"><div class="eyebrow">Empieza con SaniApp</div><h1>Tu información sanitaria, organizada en un solo lugar.</h1><p>Crea tu cuenta para registrar los datos de tu acta, consultar su estado y revisar fechas importantes.</p><div class="illustration-circle">✓</div></div>
  <div class="auth-card"><span class="section-kicker">Registro de usuario</span><h2>Crea tu cuenta</h2><p class="muted-text">Completa tus datos para empezar.</p>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="form-stack">
      <?= csrf_field() ?>
      <div class="form-grid"><label>Nombre<input name="nombre" autocomplete="given-name" value="<?= e($data['nombre']) ?>" required maxlength="80" placeholder="Tu nombre"></label><label>Apellido<input name="apellido" autocomplete="family-name" value="<?= e($data['apellido']) ?>" required maxlength="80" placeholder="Tu apellido"></label></div>
      <label>Ciudad<input name="ciudad" autocomplete="address-level2" value="<?= e($data['ciudad']) ?>" required maxlength="100" placeholder="Ej. Bogotá"></label>
      <label>Correo electrónico<input type="email" name="email" autocomplete="email" value="<?= e($data['email']) ?>" required maxlength="190" placeholder="nombre@correo.com"></label>
      <label>Contraseña<input type="password" name="password" autocomplete="new-password" required minlength="8" placeholder="Mínimo 8 caracteres"></label>
      <label>Confirmar contraseña<input type="password" name="confirm_password" autocomplete="new-password" required minlength="8" placeholder="Escribe de nuevo la contraseña"></label>
      <p class="field-help">Usa una contraseña que no utilices en otros sitios.</p>
      <button class="button button-primary button-full" type="submit">Crear cuenta <span aria-hidden="true">→</span></button>
    </form><p class="auth-bottom">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

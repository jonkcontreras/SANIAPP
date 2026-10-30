<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo válido.';
    if ($password === '') $errors[] = 'Ingresa tu contraseña.';
    if (!$errors) {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $account = $stmt->fetch();
        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            flash('success', 'Bienvenido a SaniApp.');
            redirect('dashboard.php');
        }
        $errors[] = 'El correo o la contraseña no son correctos.';
    }
}
$pageTitle = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
  <div class="auth-aside">
    <div class="eyebrow">Tu control sanitario, más fácil</div>
    <h1>Ten el estado de tu acta sanitaria a la mano.</h1>
    <p>Consulta la información que registraste y revisa si la fecha de vencimiento se acerca.</p>
    <div class="aside-feature"><span class="feature-icon">✓</span><span><strong>Estado de un vistazo</strong><small>Vigente, próxima a vencer o vencida.</small></span></div>
    <div class="aside-feature"><span class="feature-icon">⌁</span><span><strong>Tu información organizada</strong><small>Consulta desde computador, tableta o celular.</small></span></div>
  </div>
  <div class="auth-card">
    <span class="section-kicker">Bienvenido de nuevo</span><h2>Iniciar sesión</h2><p class="muted-text">Ingresa con el correo y la contraseña de tu cuenta.</p>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="form-stack" novalidate>
      <?= csrf_field() ?>
      <label>Correo electrónico<input type="email" name="email" autocomplete="email" value="<?= e($email) ?>" required maxlength="190" placeholder="nombre@correo.com"></label>
      <label>Contraseña<div class="password-wrap"><input id="login-password" type="password" name="password" autocomplete="current-password" required placeholder="Tu contraseña"><button class="password-toggle" type="button" data-toggle-password="login-password" aria-label="Mostrar contraseña">Ver</button></div></label>
      <div class="form-between"><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></div>
      <button class="button button-primary button-full" type="submit">Ingresar a SaniApp <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-bottom">¿Todavía no tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

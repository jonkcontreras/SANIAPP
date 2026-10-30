<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$sent = false;
$devLink = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare('SELECT id,email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $account = $stmt->fetch();
        if ($account) {
            db()->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()')->execute([(int)$account['id']]);
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $insert = db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))');
            $insert->execute([(int)$account['id'], $hash]);
            $url = make_absolute_url('reset_password.php?token=' . urlencode($token));
            if ((app_config()['app_env'] ?? 'production') === 'development') {
                $devLink = $url;
            } else {
                send_reset_email($account['email'], $url);
            }
        }
        $sent = true;
    }
}
$pageTitle = 'Recuperar contraseña';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout auth-layout-narrow"><div class="auth-aside compact-aside"><div class="eyebrow">Recupera tu acceso</div><h1>Vuelve a entrar a SaniApp.</h1><p>Solicita un enlace para crear una nueva contraseña y seguir consultando tus datos.</p><div class="illustration-circle">↻</div></div><div class="auth-card"><span class="section-kicker">Seguridad de la cuenta</span><h2>¿Olvidaste tu contraseña?</h2><p class="muted-text">Escribe el correo que utilizas para iniciar sesión.</p>
<?php if ($sent): ?><div class="alert alert-success">Si el correo corresponde a una cuenta registrada, se ha preparado la recuperación. Revisa tu correo si el servidor tiene el envío de mensajes configurado.</div><?php if ($devLink): ?><div class="dev-reset"><strong>Enlace local para pruebas</strong><p>Este enlace solo se muestra cuando la aplicación está en modo de desarrollo.</p><a href="<?= e($devLink) ?>">Restablecer contraseña</a></div><?php endif; ?><?php else: ?>
<form method="post" class="form-stack"><?= csrf_field() ?><label>Correo electrónico<input type="email" name="email" required autocomplete="email" maxlength="190" placeholder="nombre@correo.com"></label><button class="button button-primary button-full" type="submit">Solicitar recuperación</button></form><?php endif; ?>
<p class="auth-bottom"><a href="login.php">← Volver a iniciar sesión</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>

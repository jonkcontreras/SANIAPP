<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$valid = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = db()->prepare('SELECT id,user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
    $valid = (bool)$reset;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (!$valid) $errors[] = 'El enlace no es válido o ya venció. Solicita uno nuevo.';
    if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($password !== $confirm) $errors[] = 'Las contraseñas no coinciden.';
    if (!$errors && $reset) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([password_hash($password, PASSWORD_DEFAULT), (int)$reset['user_id']]);
            $done = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
            $done->execute([(int)$reset['user_id']]);
            $pdo->commit();
            flash('success', 'Tu contraseña fue actualizada. Inicia sesión con la nueva contraseña.');
            redirect('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('SaniApp password reset error: '.$e->getMessage());
            $errors[] = 'No fue posible actualizar la contraseña. Vuelve a intentarlo.';
        }
    }
}
$pageTitle='Cambiar contraseña';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout auth-layout-narrow"><div class="auth-aside compact-aside"><div class="eyebrow">Protege tu cuenta</div><h1>Configura una nueva contraseña.</h1><p>Por seguridad, el enlace de recuperación solo puede utilizarse durante un tiempo limitado y una sola vez.</p><div class="illustration-circle">✓</div></div><div class="auth-card"><span class="section-kicker">Recuperación de acceso</span><h2>Nueva contraseña</h2>
<?php if (!$valid): ?><div class="alert alert-error">Este enlace no es válido o ya venció. Solicita una nueva recuperación.</div><a class="button button-primary button-full" href="forgot_password.php">Solicitar otro enlace</a><?php else: ?>
<p class="muted-text">Elige una contraseña de al menos ocho caracteres.</p><?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><label>Nueva contraseña<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><label>Confirmar contraseña<input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></label><button class="button button-primary button-full" type="submit">Guardar nueva contraseña</button></form><?php endif; ?>
<p class="auth-bottom"><a href="login.php">Volver al inicio de sesión</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>

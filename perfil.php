<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();
$user = current_user();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $apellido = trim((string)($_POST['apellido'] ?? ''));
    $ciudad = trim((string)($_POST['ciudad'] ?? ''));
    if ($nombre === '' || $apellido === '' || $ciudad === '') $errors[] = 'Nombre, apellido y ciudad son obligatorios.';
    if (!$errors) {
        $stmt = db()->prepare('UPDATE users SET nombre=?, apellido=?, ciudad=? WHERE id=?');
        $stmt->execute([$nombre,$apellido,$ciudad,$_SESSION['user_id']]);
        flash('success','Tus datos de perfil se actualizaron.');
        redirect('perfil.php');
    }
    $user = array_merge($user ?? [], ['nombre'=>$nombre,'apellido'=>$apellido,'ciudad'=>$ciudad]);
}
$pageTitle='Mi perfil';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="section-kicker">Cuenta de usuario</span><h1>Mi perfil</h1><p>Consulta o actualiza los datos básicos de tu cuenta.</p></div></section>
<section class="form-panel profile-panel"><div class="profile-avatar"><?= e(strtoupper(substr($user['nombre'] ?? 'U',0,1).substr($user['apellido'] ?? '',0,1))) ?></div><div><h2><?= e(($user['nombre'] ?? '').' '.($user['apellido'] ?? '')) ?></h2><p class="muted-text">Miembro de SaniApp desde <?= e(format_date(substr((string)$user['created_at'],0,10))) ?></p></div>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-stack profile-form"><?= csrf_field() ?><div class="form-grid"><label>Nombre<input name="nombre" value="<?= e($user['nombre'] ?? '') ?>" required maxlength="80"></label><label>Apellido<input name="apellido" value="<?= e($user['apellido'] ?? '') ?>" required maxlength="80"></label></div><label>Ciudad<input name="ciudad" value="<?= e($user['ciudad'] ?? '') ?>" required maxlength="100"></label><label>Correo electrónico<input value="<?= e($user['email'] ?? '') ?>" readonly aria-describedby="email-help"></label><small id="email-help" class="field-help">El correo se utiliza para iniciar sesión y no se puede cambiar desde este formulario.</small><div class="form-actions"><button class="button button-primary" type="submit">Guardar perfil</button></div></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>

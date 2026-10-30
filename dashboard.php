<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();
$user = current_user();
$stmt = db()->prepare('SELECT * FROM actas WHERE user_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$acta = $stmt->fetch();
$allStmt = db()->prepare('SELECT COUNT(*) FROM actas WHERE user_id = ?');
$allStmt->execute([$_SESSION['user_id']]);
$totalActas = (int)$allStmt->fetchColumn();
$status = $acta ? acta_status($acta['fecha_vencimiento']) : null;
$pageTitle = 'Inicio';
require __DIR__ . '/includes/header.php';
?>
<section class="welcome-row"><div><span class="section-kicker">Panel principal</span><h1>Hola, <?= e($user['nombre'] ?? 'usuario') ?> <span class="wave">✦</span></h1><p class="muted-text">Aquí tienes el estado de tu acta sanitaria.</p></div><a class="button button-primary" href="acta_form.php<?= $acta ? '?id='.(int)$acta['id'] : '' ?>"><?= $acta ? 'Actualizar acta' : 'Registrar acta' ?> <span aria-hidden="true">＋</span></a></section>
<?php if ($acta): ?>
<section class="status-card status-card-<?= e($status['clase']) ?>">
  <div class="status-symbol" aria-hidden="true">✓</div>
  <div class="status-main"><span class="status-eyebrow">Estado de tu acta sanitaria</span><h2><?= e($status['estado']) ?></h2><p>Placa <strong><?= e($acta['placa']) ?></strong> · Vencimiento: <strong><?= e(format_date($acta['fecha_vencimiento'])) ?></strong></p></div>
  <div class="status-days"><?php if ($status['dias'] < 0): ?><strong><?= abs((int)$status['dias']) ?></strong><span>días vencida</span><?php else: ?><strong><?= (int)$status['dias'] ?></strong><span><?= $status['dias'] === 1 ? 'día restante' : 'días restantes' ?></span><?php endif; ?></div>
</section>
<?php if ($status['clase'] === 'warning'): ?><div class="notice notice-warning"><strong>Revisa la fecha de renovación.</strong> Consulta la información oficial de tu autoridad sanitaria para confirmar el trámite que corresponde.</div><?php elseif ($status['clase'] === 'danger'): ?><div class="notice notice-danger"><strong>La fecha registrada ya pasó.</strong> Verifica los requisitos y el procedimiento de renovación con la autoridad sanitaria competente.</div><?php endif; ?>
<div class="section-heading"><div><span class="section-kicker">Tu información</span><h2>Resumen del acta</h2></div><a class="text-link" href="acta.php">Ver detalles <span aria-hidden="true">→</span></a></div>
<section class="info-grid">
  <article class="info-card"><span class="info-icon">▤</span><span class="info-label">Número de acta</span><strong><?= e($acta['numero_acta'] ?: 'No registrado') ?></strong></article>
  <article class="info-card"><span class="info-icon">▣</span><span class="info-label">Propietario</span><strong><?= e($acta['propietario']) ?></strong></article>
  <article class="info-card"><span class="info-icon">♧</span><span class="info-label">Conductor</span><strong><?= e($acta['conductor']) ?></strong></article>
  <article class="info-card"><span class="info-icon">◷</span><span class="info-label">Fecha de inspección</span><strong><?= e(format_date($acta['fecha_inspeccion'])) ?></strong></article>
</section>
<?php else: ?>
<section class="empty-state"><div class="empty-icon">▤</div><h2>Aún no has registrado un acta sanitaria</h2><p>Agrega los datos que aparecen en tu documento para que SaniApp pueda mostrar el estado según la fecha de vencimiento registrada.</p><a class="button button-primary" href="acta_form.php">Registrar mi primera acta</a></section>
<?php endif; ?>
<section class="quick-actions"><div class="section-heading"><div><span class="section-kicker">Accesos rápidos</span><h2>¿Qué deseas hacer?</h2></div></div><div class="action-grid">
<a class="action-card" href="acta.php"><span class="action-icon blue">▤</span><span><strong>Consultar mi acta</strong><small>Revisa los datos registrados.</small></span><span class="action-arrow">→</span></a>
<a class="action-card" href="acta_form.php<?= $acta ? '?id='.(int)$acta['id'] : '' ?>"><span class="action-icon green">✎</span><span><strong><?= $acta ? 'Editar información' : 'Registrar acta' ?></strong><small>Agrega o actualiza los datos.</small></span><span class="action-arrow">→</span></a>
<a class="action-card" href="historial.php"><span class="action-icon purple">◷</span><span><strong>Historial de cambios</strong><small>Revisa registros y modificaciones.</small></span><span class="action-arrow">→</span></a>
<a class="action-card" href="perfil.php"><span class="action-icon gold">♙</span><span><strong>Mi perfil</strong><small>Consulta tus datos de usuario.</small></span><span class="action-arrow">→</span></a>
</div></section>
<p class="disclaimer">Importante: el estado se calcula con la fecha de vencimiento que ingresaste. SaniApp es una herramienta de seguimiento y no confirma la validez legal del acta ni reemplaza la consulta con la autoridad sanitaria.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>

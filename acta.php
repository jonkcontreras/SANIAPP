<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();
$stmt = db()->prepare('SELECT * FROM actas WHERE user_id = ? ORDER BY updated_at DESC, id DESC');
$stmt->execute([$_SESSION['user_id']]);
$actas = $stmt->fetchAll();
$pageTitle = 'Consultar acta';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="section-kicker">Consulta de información</span><h1>Mi acta sanitaria</h1><p>Consulta los datos que guardaste y el estado calculado a partir de la fecha de vencimiento.</p></div><a class="button button-primary" href="acta_form.php">＋ Registrar acta</a></section>
<?php if (!$actas): ?>
<section class="empty-state"><div class="empty-icon">▤</div><h2>No hay actas registradas</h2><p>Cuando ingreses la información de un acta, aparecerá aquí.</p><a class="button button-primary" href="acta_form.php">Registrar acta sanitaria</a></section>
<?php else: ?>
<div class="records-list">
<?php foreach ($actas as $acta): $status = acta_status($acta['fecha_vencimiento']); ?>
<article class="record-card"><div class="record-header"><div><span class="section-kicker">Acta <?= e($acta['numero_acta'] ?: '#'.(int)$acta['id']) ?></span><h2><?= e($acta['placa']) ?></h2><p class="muted-text">Propietario: <?= e($acta['propietario']) ?></p></div><span class="status-pill status-<?= e($status['clase']) ?>"><i></i><?= e($status['estado']) ?></span></div>
<div class="record-details"><div><span>Conductor</span><strong><?= e($acta['conductor']) ?></strong></div><div><span>Inspección</span><strong><?= e(format_date($acta['fecha_inspeccion'])) ?></strong></div><div><span>Vencimiento</span><strong><?= e(format_date($acta['fecha_vencimiento'])) ?></strong></div><div><span>Puntaje</span><strong><?= $acta['puntaje'] !== null ? e((string)$acta['puntaje']) : 'No registrado' ?></strong></div></div>
<div class="record-actions"><a class="button button-outline button-small" href="acta_detalle.php?id=<?= (int)$acta['id'] ?>">Ver detalles</a><a class="button button-outline button-small" href="acta_form.php?id=<?= (int)$acta['id'] ?>">Editar</a></div></article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<p class="disclaimer">El estado es orientativo y depende de la fecha ingresada por el usuario. Confirma siempre el vencimiento y los trámites con la autoridad sanitaria correspondiente.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>

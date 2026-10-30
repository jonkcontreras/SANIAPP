<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();
$stmt = db()->prepare('SELECT h.id,h.acta_id,h.accion,h.datos_anteriores,h.fecha_cambio,a.placa,a.numero_acta,a.fecha_vencimiento FROM acta_historial h LEFT JOIN actas a ON a.id=h.acta_id WHERE h.user_id = ? ORDER BY h.fecha_cambio DESC LIMIT 100');
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
$pageTitle = 'Historial';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="section-kicker">Trazabilidad</span><h1>Historial de actividad</h1><p>Consulta los registros y las modificaciones guardadas en tu cuenta.</p></div><a class="button button-outline" href="dashboard.php">← Volver al inicio</a></section>
<?php if (!$items): ?><section class="empty-state"><div class="empty-icon">◷</div><h2>Tu historial está vacío</h2><p>Cuando registres o edites un acta, verás aquí la actividad realizada.</p><a class="button button-primary" href="acta_form.php">Registrar acta</a></section><?php else: ?>
<section class="form-panel timeline-panel"><div class="timeline"><?php foreach ($items as $item): $old = $item['datos_anteriores'] ? json_decode($item['datos_anteriores'], true) : []; ?>
<article class="timeline-item"><span class="timeline-dot"></span><div class="timeline-content"><div class="timeline-top"><span class="history-action <?= $item['accion']==='Edición'?'history-edit':'history-create' ?>"><?= e($item['accion']) ?></span><time><?= e(date('d/m/Y H:i', strtotime($item['fecha_cambio']))) ?></time></div><h2><?= e($item['numero_acta'] ?: ('Acta #'.(int)$item['acta_id'])) ?> · <?= e(($old['placa'] ?? null) ?: ($item['placa'] ?? 'Registro')) ?></h2>
<?php if ($item['accion'] === 'Edición' && $old): ?><p class="muted-text">Se guardó una nueva versión de la información. El vencimiento anterior era <?= e(format_date($old['fecha_vencimiento'] ?? null)) ?>.</p><?php else: ?><p class="muted-text">Registro de acta en SaniApp. Fecha de vencimiento: <?= e(format_date($item['fecha_vencimiento'] ?? null)) ?>.</p><?php endif; ?>
<?php if ($item['placa']): ?><a class="text-link" href="acta_detalle.php?id=<?= (int)$item['acta_id'] ?>">Consultar acta actual →</a><?php endif; ?></div></article>
<?php endforeach; ?></div></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

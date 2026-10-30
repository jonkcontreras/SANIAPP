<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT * FROM actas WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$acta = $stmt->fetch();
if (!$acta) { flash('error', 'No encontramos el acta solicitada.'); redirect('acta.php'); }
$status = acta_status($acta['fecha_vencimiento']);
$pageTitle = 'Detalle del acta';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="section-kicker">Consulta de datos</span><h1>Detalle del acta sanitaria</h1><p>Revisa la información que guardaste en tu cuenta.</p></div><div class="heading-actions"><a class="button button-outline" href="acta.php">← Volver</a><a class="button button-primary" href="acta_form.php?id=<?= (int)$acta['id'] ?>">Editar acta</a></div></section>
<section class="detail-hero"><div class="detail-hero-icon">✓</div><div><span class="status-pill status-<?= e($status['clase']) ?>"><i></i><?= e($status['estado']) ?></span><h2>Placa <?= e($acta['placa']) ?></h2><p>Fecha de vencimiento registrada: <strong><?= e(format_date($acta['fecha_vencimiento'])) ?></strong></p></div></section>
<section class="form-panel detail-panel"><div class="section-heading"><div><span class="section-kicker">Información registrada</span><h2>Datos generales</h2></div></div><dl class="detail-grid">
<?php $fields = [
'Número del acta'=>$acta['numero_acta'] ?: 'No registrado', 'Propietario'=>$acta['propietario'], 'Conductor'=>$acta['conductor'], 'Placa del vehículo'=>$acta['placa'], 'Tipo de vehículo'=>$acta['tipo_vehiculo'] ?: 'No registrado', 'Tipo de alimento'=>$acta['tipo_alimento'] ?: 'No registrado', 'Fecha de inspección'=>format_date($acta['fecha_inspeccion']), 'Fecha de vencimiento'=>format_date($acta['fecha_vencimiento']), 'Puntaje de inspección'=>$acta['puntaje'] !== null ? (string)$acta['puntaje'] : 'No registrado', 'Observaciones'=>$acta['observaciones'] ?: 'Sin observaciones', 'Última actualización'=>date('d/m/Y H:i', strtotime($acta['updated_at']))
]; foreach ($fields as $label=>$value): ?><div class="detail-item"><dt><?= e($label) ?></dt><dd><?= nl2br(e((string)$value)) ?></dd></div><?php endforeach; ?>
</dl></section>
<div class="notice notice-info">Esta consulta no certifica la validez legal del documento. Compara estos datos con el acta oficial que te fue expedida.</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

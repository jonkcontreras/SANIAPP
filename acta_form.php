<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();
$userId = (int)$_SESSION['user_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$editing = false;
$data = ['numero_acta'=>'','propietario'=>'','conductor'=>'','placa'=>'','tipo_vehiculo'=>'','tipo_alimento'=>'','fecha_inspeccion'=>'','fecha_vencimiento'=>'','puntaje'=>'','observaciones'=>''];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM actas WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $existing = $stmt->fetch();
    if (!$existing) { flash('error', 'No encontramos ese registro en tu cuenta.'); redirect('dashboard.php'); }
    $editing = true;
    foreach ($data as $key => $_) $data[$key] = (string)($existing[$key] ?? '');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $key => $_) $data[$key] = trim((string)($_POST[$key] ?? ''));
    if ($data['propietario'] === '') $errors[] = 'El nombre del propietario es obligatorio.';
    if ($data['conductor'] === '') $errors[] = 'El nombre del conductor es obligatorio.';
    if ($data['placa'] === '') $errors[] = 'La placa del vehículo es obligatoria.';
    if ($data['fecha_inspeccion'] === '' || !valid_date_ymd($data['fecha_inspeccion'])) $errors[] = 'Registra una fecha de inspección válida.';
    if ($data['fecha_vencimiento'] === '' || !valid_date_ymd($data['fecha_vencimiento'])) $errors[] = 'Registra una fecha de vencimiento válida.';
    if ($data['fecha_inspeccion'] !== '' && $data['fecha_vencimiento'] !== '' && $data['fecha_vencimiento'] < $data['fecha_inspeccion']) $errors[] = 'La fecha de vencimiento no puede ser anterior a la inspección.';
    if ($data['puntaje'] !== '' && (!is_numeric($data['puntaje']) || (float)$data['puntaje'] < 0 || (float)$data['puntaje'] > 100)) $errors[] = 'El puntaje debe estar entre 0 y 100.';
    if (strlen($data['placa']) > 12) $errors[] = 'La placa es demasiado larga.';
    if (strlen($data['propietario']) > 160 || strlen($data['conductor']) > 160) $errors[] = 'Los nombres no pueden superar el límite permitido.';
    if (!$errors) {
        $payload = [
            $data['numero_acta'] !== '' ? $data['numero_acta'] : null,
            $data['propietario'], $data['conductor'], strtoupper(str_replace(' ', '', $data['placa'])),
            $data['tipo_vehiculo'] !== '' ? $data['tipo_vehiculo'] : null,
            $data['tipo_alimento'] !== '' ? $data['tipo_alimento'] : null,
            $data['fecha_inspeccion'], $data['fecha_vencimiento'],
            $data['puntaje'] !== '' ? (float)$data['puntaje'] : null,
            $data['observaciones'] !== '' ? $data['observaciones'] : null
        ];
        try {
            if ($editing) {
                $old = [];
                foreach (['numero_acta','propietario','conductor','placa','tipo_vehiculo','tipo_alimento','fecha_inspeccion','fecha_vencimiento','puntaje','observaciones'] as $key) $old[$key] = $existing[$key];
                $hist = db()->prepare('INSERT INTO acta_historial (acta_id,user_id,accion,datos_anteriores) VALUES (?,?,?,?)');
                $hist->execute([$id,$userId,'Edición',json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                $stmt = db()->prepare('UPDATE actas SET numero_acta=?, propietario=?, conductor=?, placa=?, tipo_vehiculo=?, tipo_alimento=?, fecha_inspeccion=?, fecha_vencimiento=?, puntaje=?, observaciones=? WHERE id=? AND user_id=?');
                $stmt->execute([...$payload, $id, $userId]);
                flash('success', 'Los datos del acta se actualizaron correctamente.');
            } else {
                $stmt = db()->prepare('INSERT INTO actas (user_id,numero_acta,propietario,conductor,placa,tipo_vehiculo,tipo_alimento,fecha_inspeccion,fecha_vencimiento,puntaje,observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$userId, ...$payload]);
                $newId = (int)db()->lastInsertId();
                $hist = db()->prepare('INSERT INTO acta_historial (acta_id,user_id,accion,datos_anteriores) VALUES (?,?,?,?)');
                $hist->execute([$newId,$userId,'Registro',null]);
                flash('success', 'El acta sanitaria se registró correctamente.');
            }
            redirect('dashboard.php');
        } catch (PDOException $e) {
            error_log('SaniApp acta save error: '.$e->getMessage());
            $errors[] = 'No fue posible guardar el acta. Verifica la conexión y vuelve a intentarlo.';
        }
    }
}
$pageTitle = $editing ? 'Editar acta sanitaria' : 'Registrar acta sanitaria';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="section-kicker">Datos del documento</span><h1><?= $editing ? 'Actualizar acta sanitaria' : 'Registrar acta sanitaria' ?></h1><p>Transcribe la información de tu documento. Revisa las fechas antes de guardar.</p></div><a class="button button-outline" href="dashboard.php">← Volver al inicio</a></section>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="form-panel"><div class="form-panel-heading"><span class="panel-icon">▤</span><div><h2>Información del acta</h2><p>Los campos marcados con * son obligatorios.</p></div></div>
<form method="post" class="form-stack">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $editing ? (int)$id : '' ?>">
  <div class="form-grid"><label>Número del acta <span class="optional">(opcional)</span><input name="numero_acta" maxlength="80" value="<?= e($data['numero_acta']) ?>" placeholder="Número que aparece en el documento"></label><label>Placa del vehículo *<input name="placa" required maxlength="12" value="<?= e($data['placa']) ?>" placeholder="Ej. ABC123" style="text-transform:uppercase"></label></div>
  <div class="form-grid"><label>Nombre del propietario *<input name="propietario" required maxlength="160" value="<?= e($data['propietario']) ?>" placeholder="Nombre completo o razón social"></label><label>Nombre del conductor *<input name="conductor" required maxlength="160" value="<?= e($data['conductor']) ?>" placeholder="Nombre completo"></label></div>
  <div class="form-grid"><label>Tipo de vehículo <span class="optional">(opcional)</span><select name="tipo_vehiculo"><option value="">Seleccionar</option><?php foreach (['Camión','Furgón','Camioneta','Motocarro','Otro'] as $v): ?><option value="<?= e($v) ?>" <?= $data['tipo_vehiculo']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label><label>Tipo de alimento transportado <span class="optional">(opcional)</span><input name="tipo_alimento" maxlength="160" value="<?= e($data['tipo_alimento']) ?>" placeholder="Ej. alimentos refrigerados"></label></div>
  <div class="form-grid date-fields"><label>Fecha de inspección sanitaria *<input type="date" name="fecha_inspeccion" id="fecha-inspeccion" value="<?= e($data['fecha_inspeccion']) ?>" required></label><label>Fecha de vencimiento del acta *<input type="date" name="fecha_vencimiento" id="fecha-vencimiento" value="<?= e($data['fecha_vencimiento']) ?>" required><small class="field-help">Ingresa la fecha indicada en el documento. El botón de ayuda propone un año, pero debes verificar la fecha real.</small><button type="button" id="suggest-expiration" class="small-link">Sugerir fecha a un año de la inspección</button></label></div>
  <div class="form-grid"><label>Puntaje de inspección <span class="optional">(opcional)</span><input type="number" name="puntaje" min="0" max="100" step="0.01" value="<?= e($data['puntaje']) ?>" placeholder="Entre 0 y 100"><small class="field-help">Usa la escala que aparece en tu acta; no registres un puntaje si el documento no lo trae.</small></label><label>Observaciones <span class="optional">(opcional)</span><textarea name="observaciones" rows="3" maxlength="3000" placeholder="Notas para tu seguimiento"><?= e($data['observaciones']) ?></textarea></label></div>
  <div class="notice notice-info"><strong>¿Cómo se calcula el estado?</strong> SaniApp compara la fecha de vencimiento ingresada con la fecha actual. Si ya pasó, muestra “Vencida”; si faltan hasta 30 días, muestra “Próxima a vencer”; en los demás casos, “Vigente”.</div>
  <div class="form-actions"><a class="button button-outline" href="dashboard.php">Cancelar</a><button class="button button-primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Guardar acta' ?> <span aria-hidden="true">→</span></button></div>
</form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>

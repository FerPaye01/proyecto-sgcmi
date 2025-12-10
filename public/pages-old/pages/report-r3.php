<?php
// R3: Utilización de Muelles
$filters = ['fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'), 'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d')];
$stmt = $pdo->prepare("SELECT vc.*, v.name as vessel_name, b.name as berth_name FROM portuario.vessel_call vc JOIN portuario.vessel v ON vc.vessel_id = v.id LEFT JOIN portuario.berth b ON vc.berth_id = b.id WHERE vc.atb IS NOT NULL AND vc.atd IS NOT NULL AND vc.atb >= :fecha_desde AND vc.atd <= :fecha_hasta ORDER BY vc.atb");
$stmt->execute($filters);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
include 'layout/header.php';
?>
<div class="page-header"><h1>📊 R3: Utilización de Muelles</h1><p>Utilización por franja horaria y conflictos de ventana</p></div>
<div class="card" style="margin-bottom: 2rem;"><div class="card-body"><form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;"><input type="hidden" name="page" value="report-r3"><div><label>Fecha Desde:</label><input type="date" name="fecha_desde" value="<?=htmlspecialchars($filters['fecha_desde'])?>" class="form-input"></div><div><label>Fecha Hasta:</label><input type="date" name="fecha_hasta" value="<?=htmlspecialchars($filters['fecha_hasta'])?>" class="form-input"></div><button type="submit" class="btn btn-primary">🔍 Filtrar</button></form></div></div>
<div class="stats-grid" style="margin-bottom: 2rem;"><div class="stat-card blue"><div class="stat-content"><div class="stat-number"><?=count($data)?></div><div class="stat-label">Total Llamadas</div></div></div></div>
<div class="card"><div class="card-header"><h3>📊 Llamadas por Muelle</h3></div><div class="card-body"><table class="data-table"><thead><tr><th>Nave</th><th>Muelle</th><th>ATB</th><th>ATD</th><th>Duración (h)</th></tr></thead><tbody><?php if (empty($data)): ?><tr><td colspan="5" style="text-align: center; color: #666;">No hay datos disponibles</td></tr><?php else: ?><?php foreach ($data as $row): ?><tr><td><?=htmlspecialchars($row['vessel_name'])?></td><td><?=htmlspecialchars($row['berth_name'] ?? 'N/A')?></td><td><?=date('d/m/Y H:i', strtotime($row['atb']))?></td><td><?=date('d/m/Y H:i', strtotime($row['atd']))?></td><td><?=round((strtotime($row['atd']) - strtotime($row['atb']))/3600, 2)?>h</td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
<?php include 'layout/footer.php'; ?>

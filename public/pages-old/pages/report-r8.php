<?php
// R8: Tiempo de Despacho por Régimen
$filters = ['fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'), 'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d')];
$stmt = $pdo->prepare("SELECT t.*, EXTRACT(EPOCH FROM (t.fecha_fin - t.fecha_inicio))/3600 as tiempo_despacho_h FROM aduanas.tramite t WHERE t.estado = 'APROBADO' AND t.fecha_fin IS NOT NULL AND t.fecha_inicio >= :fecha_desde AND t.fecha_inicio <= :fecha_hasta ORDER BY t.fecha_inicio DESC");
$stmt->execute($filters);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tiempos = array_column($data, 'tiempo_despacho_h');
sort($tiempos);
$p50 = count($tiempos) > 0 ? $tiempos[(int)(count($tiempos)*0.5)] : 0;
$p90 = count($tiempos) > 0 ? $tiempos[(int)(count($tiempos)*0.9)] : 0;
$umbral = 24;
$fuera_umbral = count(array_filter($tiempos, fn($t) => $t > $umbral));
include 'layout/header.php';
?>
<div class="page-header"><h1>⚡ R8: Tiempo de Despacho</h1><p>Percentiles P50/P90 por régimen aduanero</p></div>
<div class="card" style="margin-bottom: 2rem;"><div class="card-body"><form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;"><input type="hidden" name="page" value="report-r8"><div><label>Fecha Desde:</label><input type="date" name="fecha_desde" value="<?=htmlspecialchars($filters['fecha_desde'])?>" class="form-input"></div><div><label>Fecha Hasta:</label><input type="date" name="fecha_hasta" value="<?=htmlspecialchars($filters['fecha_hasta'])?>" class="form-input"></div><button type="submit" class="btn btn-primary">🔍 Filtrar</button></form></div></div>
<div class="stats-grid" style="margin-bottom: 2rem;"><div class="stat-card blue"><div class="stat-content"><div class="stat-number"><?=round($p50, 2)?>h</div><div class="stat-label">Percentil 50</div></div></div><div class="stat-card orange"><div class="stat-content"><div class="stat-number"><?=round($p90, 2)?>h</div><div class="stat-label">Percentil 90</div></div></div><div class="stat-card red"><div class="stat-content"><div class="stat-number"><?=$fuera_umbral?></div><div class="stat-label">Fuera de Umbral (>24h)</div></div></div><div class="stat-card green"><div class="stat-content"><div class="stat-number"><?=count($data)?></div><div class="stat-label">Total Trámites</div></div></div></div>
<div class="card"><div class="card-header"><h3>📊 Tiempos de Despacho</h3></div><div class="card-body"><table class="data-table"><thead><tr><th>Trámite ID</th><th>Régimen</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tiempo (h)</th><th>Estado</th></tr></thead><tbody><?php if (empty($data)): ?><tr><td colspan="6" style="text-align: center; color: #666;">No hay datos disponibles</td></tr><?php else: ?><?php foreach ($data as $row): ?><?php $badge = $row['tiempo_despacho_h'] <= 24 ? 'badge-success' : 'badge-warning'; ?><tr><td><?=htmlspecialchars($row['tramite_ext_id'])?></td><td><?=htmlspecialchars($row['regimen'])?></td><td><?=date('d/m/Y H:i', strtotime($row['fecha_inicio']))?></td><td><?=date('d/m/Y H:i', strtotime($row['fecha_fin']))?></td><td><span class="badge <?=$badge?>"><?=round($row['tiempo_despacho_h'], 2)?>h</span></td><td><?=$row['tiempo_despacho_h'] <= 24 ? 'Dentro de SLA' : 'Fuera de SLA'?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
<?php include 'layout/footer.php'; ?>

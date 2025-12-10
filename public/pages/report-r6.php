<?php
// R6: Productividad de Gates
$filters = ['fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-d'), 'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d')];
$stmt = $pdo->prepare("SELECT ge.*, g.name as gate_name, t.placa FROM terrestre.gate_event ge JOIN terrestre.gate g ON ge.gate_id = g.id JOIN terrestre.truck t ON ge.truck_id = t.id WHERE ge.event_ts >= :fecha_desde AND ge.event_ts <= :fecha_hasta ORDER BY ge.event_ts");
$stmt->execute($filters);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$entradas = count(array_filter($data, fn($r) => $r['action'] == 'ENTRADA'));
$salidas = count(array_filter($data, fn($r) => $r['action'] == 'SALIDA'));
include 'layout/header.php';
?>
<div class="page-header"><h1>🚪 R6: Productividad de Gates</h1><p>Vehículos por hora, tiempo de ciclo, horas pico</p></div>
<div class="card" style="margin-bottom: 2rem;"><div class="card-body"><form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;"><input type="hidden" name="page" value="report-r6"><div><label>Fecha Desde:</label><input type="date" name="fecha_desde" value="<?=htmlspecialchars($filters['fecha_desde'])?>" class="form-input"></div><div><label>Fecha Hasta:</label><input type="date" name="fecha_hasta" value="<?=htmlspecialchars($filters['fecha_hasta'])?>" class="form-input"></div><button type="submit" class="btn btn-primary">🔍 Filtrar</button></form></div></div>
<div class="stats-grid" style="margin-bottom: 2rem;"><div class="stat-card green"><div class="stat-content"><div class="stat-number"><?=$entradas?></div><div class="stat-label">Entradas</div></div></div><div class="stat-card blue"><div class="stat-content"><div class="stat-number"><?=$salidas?></div><div class="stat-label">Salidas</div></div></div><div class="stat-card orange"><div class="stat-content"><div class="stat-number"><?=count($data)?></div><div class="stat-label">Total Eventos</div></div></div></div>
<div class="card"><div class="card-header"><h3>📊 Eventos de Gates</h3></div><div class="card-body"><table class="data-table"><thead><tr><th>Gate</th><th>Placa</th><th>Acción</th><th>Timestamp</th></tr></thead><tbody><?php if (empty($data)): ?><tr><td colspan="4" style="text-align: center; color: #666;">No hay datos disponibles</td></tr><?php else: ?><?php foreach ($data as $row): ?><tr><td><?=htmlspecialchars($row['gate_name'])?></td><td><?=htmlspecialchars($row['placa'])?></td><td><span class="badge <?=$row['action']=='ENTRADA'?'badge-success':'badge-info'?>"><?=htmlspecialchars($row['action'])?></span></td><td><?=date('d/m/Y H:i:s', strtotime($row['event_ts']))?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
<?php include 'layout/footer.php'; ?>

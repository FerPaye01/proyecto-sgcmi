<?php
// R12: Cumplimiento de SLAs
$stmt = $pdo->query("SELECT a.*, COUNT(sm.id) as total_mediciones, SUM(CASE WHEN sm.cumplio = TRUE THEN 1 ELSE 0 END) as cumplidos FROM analytics.actor a LEFT JOIN analytics.sla_measure sm ON a.id = sm.actor_id GROUP BY a.id ORDER BY a.name");
$actores = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_actores = count($actores);
$actores_excelentes = 0;
$actores_criticos = 0;
foreach ($actores as &$actor) {
    $actor['pct_cumplimiento'] = $actor['total_mediciones'] > 0 ? round(($actor['cumplidos']/$actor['total_mediciones'])*100, 2) : 0;
    $actor['estado'] = $actor['pct_cumplimiento'] >= 90 ? 'EXCELENTE' : ($actor['pct_cumplimiento'] >= 75 ? 'BUENO' : ($actor['pct_cumplimiento'] >= 50 ? 'REGULAR' : 'CRÍTICO'));
    if ($actor['estado'] == 'EXCELENTE') $actores_excelentes++;
    if ($actor['estado'] == 'CRÍTICO') $actores_criticos++;
}
include 'layout/header.php';
?>
<div class="page-header"><h1>🎯 R12: Cumplimiento de SLAs</h1><p>Cumplimiento por actor, penalidades, incumplimientos</p></div>
<div class="stats-grid" style="margin-bottom: 2rem;"><div class="stat-card blue"><div class="stat-content"><div class="stat-number"><?=$total_actores?></div><div class="stat-label">Total Actores</div></div></div><div class="stat-card green"><div class="stat-content"><div class="stat-number"><?=$actores_excelentes?></div><div class="stat-label">Excelentes (≥90%)</div></div></div><div class="stat-card red"><div class="stat-content"><div class="stat-number"><?=$actores_criticos?></div><div class="stat-label">Críticos (<50%)</div></div></div><div class="stat-card purple"><div class="stat-content"><div class="stat-number"><?=$total_actores > 0 ? round(($actores_excelentes/$total_actores)*100, 2) : 0?>%</div><div class="stat-label">% Excelentes</div></div></div></div>
<div class="card"><div class="card-header"><h3>📊 Cumplimiento por Actor</h3></div><div class="card-body"><?php if (empty($actores)): ?><div style="text-align: center; padding: 2rem; color: #666;"><p>No hay datos de SLAs disponibles</p></div><?php else: ?><table class="data-table"><thead><tr><th>Actor</th><th>Tipo</th><th>Total Mediciones</th><th>Cumplidos</th><th>% Cumplimiento</th><th>Estado</th></tr></thead><tbody><?php foreach ($actores as $actor): ?><?php $badge = $actor['estado']=='EXCELENTE'?'badge-success':($actor['estado']=='BUENO'?'badge-info':($actor['estado']=='REGULAR'?'badge-warning':'badge-danger')); ?><tr><td><strong><?=htmlspecialchars($actor['name'])?></strong></td><td><?=htmlspecialchars($actor['tipo'])?></td><td><?=$actor['total_mediciones']?></td><td><?=$actor['cumplidos']?></td><td><strong><?=$actor['pct_cumplimiento']?>%</strong></td><td><span class="badge <?=$badge?>"><?=$actor['estado']?></span></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<?php include 'layout/footer.php'; ?>

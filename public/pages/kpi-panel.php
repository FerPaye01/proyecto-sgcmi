<?php
if(!hasPermission($pdo,$currentUser['id'],'KPI_READ'))die('Acceso denegado');
$kpis=$pdo->query("SELECT kd.id,kd.code,kd.name,kd.description,kv.valor,kv.meta,kv.periodo,CASE WHEN kv.meta IS NOT NULL AND kv.valor<=kv.meta THEN TRUE ELSE FALSE END as cumple_meta FROM analytics.kpi_definition kd LEFT JOIN LATERAL(SELECT * FROM analytics.kpi_value WHERE kpi_id=kd.id ORDER BY periodo DESC LIMIT 1)kv ON TRUE ORDER BY kd.code")->fetchAll(PDO::FETCH_ASSOC);
$kpiRealTime=[
'naves_programadas'=>$pdo->query("SELECT COUNT(*) FROM portuario.vessel_call WHERE estado_llamada='PROGRAMADA'")->fetchColumn(),
'citas_pendientes'=>$pdo->query("SELECT COUNT(*) FROM terrestre.appointment WHERE estado IN('PROGRAMADA','CONFIRMADA')")->fetchColumn(),
'tramites_proceso'=>$pdo->query("SELECT COUNT(*) FROM aduanas.tramite WHERE estado IN('INICIADO','EN_PROCESO')")->fetchColumn(),
'tramites_completos'=>$pdo->query("SELECT COUNT(*) FROM aduanas.tramite WHERE estado='COMPLETO'")->fetchColumn(),
];
$totalTramites=$kpiRealTime['tramites_proceso']+$kpiRealTime['tramites_completos'];
$pctTramitesOk=$totalTramites>0?round(($kpiRealTime['tramites_completos']/$totalTramites)*100,1):0;
include 'layout/header.php';
?>
<div class="page-header"><h1>📈 Panel de KPIs</h1><p>Indicadores Clave de Desempeño del Sistema SGCMI</p></div>
<div class="alert alert-info"><strong>🔄 Actualización en Tiempo Real</strong> - Los KPIs se calculan directamente desde la base de datos</div>
<h2 style="margin:30px 0 20px 0;font-size:20px">📊 KPIs Operacionales</h2>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">🚢</div><div class="stat-content"><div class="stat-number"><?=$kpiRealTime['naves_programadas']?></div><div class="stat-label">Naves Programadas</div></div></div>
<div class="stat-card green"><div class="stat-icon">🚛</div><div class="stat-content"><div class="stat-number"><?=$kpiRealTime['citas_pendientes']?></div><div class="stat-label">Citas Pendientes</div></div></div>
<div class="stat-card orange"><div class="stat-icon">📋</div><div class="stat-content"><div class="stat-number"><?=$kpiRealTime['tramites_proceso']?></div><div class="stat-label">Trámites en Proceso</div></div></div>
<div class="stat-card purple"><div class="stat-icon">✅</div><div class="stat-content"><div class="stat-number"><?=$pctTramitesOk?>%</div><div class="stat-label">Trámites Completos</div></div></div>
</div>
<h2 style="margin:30px 0 20px 0;font-size:20px">📉 KPIs Históricos</h2>
<div class="card"><div class="card-header"><h3>Indicadores Definidos en el Sistema</h3></div>
<div class="card-body"><table class="data-table"><thead><tr><th>Código</th><th>Nombre</th><th>Valor Actual</th><th>Meta</th><th>Cumplimiento</th></tr></thead><tbody>
<?php if(empty($kpis)):?><tr><td colspan="5" style="text-align:center;padding:40px;color:#6c757d">No hay KPIs definidos</td></tr><?php else:?>
<?php foreach($kpis as $kpi):?><tr><td><strong><?=htmlspecialchars($kpi['code'])?></strong></td><td><?=htmlspecialchars($kpi['name'])?></td><td><?=$kpi['valor']!==null?'<strong>'.number_format($kpi['valor'],2).'</strong>':'Sin datos'?></td><td><?=$kpi['meta']!==null?number_format($kpi['meta'],2):'N/A'?></td><td><?=$kpi['valor']!==null&&$kpi['meta']!==null?'<span class="badge badge-'.($kpi['cumple_meta']?'success':'danger').'">'.($kpi['cumple_meta']?'✓ Cumple':'✗ No Cumple').'</span>':'-'?></td></tr><?php endforeach;?>
<?php endif;?></tbody></table></div></div>
<?php include 'layout/footer.php';?>

<?php
if(!hasPermission($pdo,$currentUser['id'],'REPORT_READ'))die('Acceso denegado');
$fecha_desde=$_GET['fecha_desde']??date('Y-m-d',strtotime('-7 days'));
$fecha_hasta=$_GET['fecha_hasta']??date('Y-m-d');
$stmt=$pdo->prepare("SELECT vc.id,v.name as vessel_name,v.imo,vc.viaje_id,b.name as berth_name,vc.eta,vc.ata,vc.estado_llamada,EXTRACT(EPOCH FROM(vc.ata-vc.eta))/3600 as demora_eta_ata_h,CASE WHEN vc.ata IS NOT NULL AND ABS(EXTRACT(EPOCH FROM(vc.ata-vc.eta))/3600)<=2 THEN TRUE ELSE FALSE END as puntual_arribo FROM portuario.vessel_call vc JOIN portuario.vessel v ON vc.vessel_id=v.id LEFT JOIN portuario.berth b ON vc.berth_id=b.id WHERE vc.eta>=? AND vc.eta<=? ORDER BY vc.eta DESC");
$stmt->execute([$fecha_desde,$fecha_hasta]);
$data=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalLlamadas=count($data);
$conATA=count(array_filter($data,fn($d)=>$d['ata']!==null));
$puntuales=count(array_filter($data,fn($d)=>$d['puntual_arribo']));
$pctPuntualidad=$conATA>0?round(($puntuales/$conATA)*100,1):0;
include 'layout/header.php';
?>
<div class="page-header"><h1>📊 Reporte R1: Programación vs Ejecución</h1><p>Análisis de puntualidad y demoras en arribos de naves</p></div>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">🚢</div><div class="stat-content"><div class="stat-number"><?=$totalLlamadas?></div><div class="stat-label">Total Llamadas</div></div></div>
<div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-content"><div class="stat-number"><?=$pctPuntualidad?>%</div><div class="stat-label">Puntualidad Arribo</div></div></div>
</div>
<div class="filters"><form method="GET" action="index.php"><input type="hidden" name="page" value="report-r1">
<div class="form-group"><label>Fecha Desde</label><input type="date" name="fecha_desde" value="<?=htmlspecialchars($fecha_desde)?>"></div>
<div class="form-group"><label>Fecha Hasta</label><input type="date" name="fecha_hasta" value="<?=htmlspecialchars($fecha_hasta)?>"></div>
<button type="submit" class="btn btn-primary">Generar Reporte</button></form></div>
<div class="card"><div class="card-header"><h3>Detalle de Llamadas</h3></div>
<div class="card-body"><table class="data-table"><thead><tr><th>Nave</th><th>Viaje</th><th>Muelle</th><th>ETA</th><th>ATA</th><th>Demora (h)</th><th>Puntual</th></tr></thead><tbody>
<?php if(empty($data)):?><tr><td colspan="7" style="text-align:center;padding:40px;color:#6c757d">No hay datos</td></tr><?php else:?>
<?php foreach($data as $row):?><tr><td><?=htmlspecialchars($row['vessel_name'])?></td><td><?=htmlspecialchars($row['viaje_id'])?></td><td><?=htmlspecialchars($row['berth_name']??'N/A')?></td><td><?=date('d/m/Y H:i',strtotime($row['eta']))?></td><td><?=$row['ata']?date('d/m/Y H:i',strtotime($row['ata'])):'Pendiente'?></td><td><?=$row['demora_eta_ata_h']!==null?round($row['demora_eta_ata_h'],2).'h':'N/A'?></td><td><?=$row['ata']?'<span class="badge badge-'.($row['puntual_arribo']?'success':'danger').'">'.($row['puntual_arribo']?'✓ Sí':'✗ No').'</span>':'-'?></td></tr><?php endforeach;?>
<?php endif;?></tbody></table></div></div>
<div class="alert alert-info"><strong>📌 Criterios:</strong> Puntual si diferencia ETA-ATA ≤ 2 horas</div>
<?php include 'layout/footer.php';?>

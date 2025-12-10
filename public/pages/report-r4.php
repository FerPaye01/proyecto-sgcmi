<?php
if(!hasPermission($pdo,$currentUser['id'],'REPORT_READ'))die('Acceso denegado');
$fecha_desde=$_GET['fecha_desde']??date('Y-m-d',strtotime('-7 days'));
$fecha_hasta=$_GET['fecha_hasta']??date('Y-m-d');
$stmt=$pdo->prepare("SELECT a.id,t.placa,c.name as company_name,a.hora_programada,a.hora_llegada,a.estado,EXTRACT(EPOCH FROM(a.hora_llegada-a.hora_programada))/3600 as espera_h,CASE WHEN EXTRACT(EPOCH FROM(a.hora_llegada-a.hora_programada))/3600>6 THEN TRUE ELSE FALSE END as espera_excesiva FROM terrestre.appointment a JOIN terrestre.truck t ON a.truck_id=t.id JOIN terrestre.company c ON a.company_id=c.id WHERE a.hora_programada>=? AND a.hora_programada<=? AND a.hora_llegada IS NOT NULL ORDER BY a.hora_programada DESC");
$stmt->execute([$fecha_desde.' 00:00:00',$fecha_hasta.' 23:59:59']);
$data=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCitas=count($data);
$esperas=array_column($data,'espera_h');
$esperaPromedio=!empty($esperas)?round(array_sum($esperas)/count($esperas),2):0;
include 'layout/header.php';
?>
<div class="page-header"><h1>⏱️ Reporte R4: Tiempo de Espera de Camiones</h1><p>Análisis de tiempos de espera y eficiencia en atención</p></div>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">🚛</div><div class="stat-content"><div class="stat-number"><?=$totalCitas?></div><div class="stat-label">Citas con Llegada</div></div></div>
<div class="stat-card green"><div class="stat-icon">⏱️</div><div class="stat-content"><div class="stat-number"><?=abs($esperaPromedio)?>h</div><div class="stat-label">Espera Promedio</div></div></div>
</div>
<div class="filters"><form method="GET" action="index.php"><input type="hidden" name="page" value="report-r4">
<div class="form-group"><label>Fecha Desde</label><input type="date" name="fecha_desde" value="<?=htmlspecialchars($fecha_desde)?>"></div>
<div class="form-group"><label>Fecha Hasta</label><input type="date" name="fecha_hasta" value="<?=htmlspecialchars($fecha_hasta)?>"></div>
<button type="submit" class="btn btn-primary">Generar Reporte</button></form></div>
<div class="card"><div class="card-header"><h3>Detalle de Tiempos de Espera</h3></div>
<div class="card-body"><table class="data-table"><thead><tr><th>Placa</th><th>Empresa</th><th>Hora Programada</th><th>Hora Llegada</th><th>Tiempo Espera</th><th>Alerta</th></tr></thead><tbody>
<?php if(empty($data)):?><tr><td colspan="6" style="text-align:center;padding:40px;color:#6c757d">No hay datos</td></tr><?php else:?>
<?php foreach($data as $row):?><tr><td><strong><?=htmlspecialchars($row['placa'])?></strong></td><td><?=htmlspecialchars($row['company_name'])?></td><td><?=date('d/m/Y H:i',strtotime($row['hora_programada']))?></td><td><?=date('d/m/Y H:i',strtotime($row['hora_llegada']))?></td><td><strong><?=round($row['espera_h'],2)?> horas</strong></td><td><?=$row['espera_excesiva']?'<span class="badge badge-danger">⚠️ Excesiva</span>':'<span class="badge badge-success">✓ Normal</span>'?></td></tr><?php endforeach;?>
<?php endif;?></tbody></table></div></div>
<div class="alert alert-info"><strong>📌 Criterios:</strong> Espera excesiva si > 6 horas. SLA: 90% de citas < 6h</div>
<?php include 'layout/footer.php';?>

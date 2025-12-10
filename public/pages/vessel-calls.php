<?php
if(!hasPermission($pdo,$currentUser['id'],'SCHEDULE_READ'))die('Acceso denegado');
$fecha_desde=$_GET['fecha_desde']??date('Y-m-d',strtotime('-30 days'));
$fecha_hasta=$_GET['fecha_hasta']??date('Y-m-d',strtotime('+30 days'));
$berth_id=$_GET['berth_id']??'';
$berths=$pdo->query("SELECT * FROM portuario.berth WHERE active=TRUE ORDER BY code")->fetchAll(PDO::FETCH_ASSOC);
$sql="SELECT vc.*,v.name as vessel_name,v.imo,b.name as berth_name FROM portuario.vessel_call vc JOIN portuario.vessel v ON vc.vessel_id=v.id LEFT JOIN portuario.berth b ON vc.berth_id=b.id WHERE vc.eta>=? AND vc.eta<=?";
$params=[$fecha_desde,$fecha_hasta];
if($berth_id){$sql.=" AND vc.berth_id=?";$params[]=$berth_id;}
$sql.=" ORDER BY vc.eta DESC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);
$vesselCalls=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCalls=count($vesselCalls);
$programadas=count(array_filter($vesselCalls,fn($vc)=>$vc['estado_llamada']==='PROGRAMADA'));
include 'layout/header.php';
?>
<div class="page-header"><h1>🚢 Llamadas de Naves</h1><p>Gestión de programación y seguimiento de naves</p></div>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">📊</div><div class="stat-content"><div class="stat-number"><?=$totalCalls?></div><div class="stat-label">Total Llamadas</div></div></div>
<div class="stat-card orange"><div class="stat-icon">📅</div><div class="stat-content"><div class="stat-number"><?=$programadas?></div><div class="stat-label">Programadas</div></div></div>
</div>
<div class="filters"><form method="GET" action="index.php"><input type="hidden" name="page" value="vessel-calls">
<div class="form-group"><label>Fecha Desde</label><input type="date" name="fecha_desde" value="<?=htmlspecialchars($fecha_desde)?>"></div>
<div class="form-group"><label>Fecha Hasta</label><input type="date" name="fecha_hasta" value="<?=htmlspecialchars($fecha_hasta)?>"></div>
<div class="form-group"><label>Muelle</label><select name="berth_id"><option value="">Todos</option><?php foreach($berths as $berth):?><option value="<?=$berth['id']?>" <?=$berth_id==$berth['id']?'selected':''?>><?=htmlspecialchars($berth['name'])?></option><?php endforeach;?></select></div>
<button type="submit" class="btn btn-primary">Filtrar</button></form></div>
<div class="card"><div class="card-header"><h3>Listado de Llamadas</h3><a href="index.php?page=report-r1" class="btn-link">Ver Reporte R1 →</a></div>
<div class="card-body"><table class="data-table"><thead><tr><th>ID</th><th>Nave</th><th>IMO</th><th>Viaje</th><th>Muelle</th><th>ETA</th><th>Estado</th></tr></thead><tbody>
<?php if(empty($vesselCalls)):?><tr><td colspan="7" style="text-align:center;padding:40px;color:#6c757d">No se encontraron llamadas</td></tr><?php else:?>
<?php foreach($vesselCalls as $vc):?><tr><td><strong>#<?=$vc['id']?></strong></td><td><?=htmlspecialchars($vc['vessel_name'])?></td><td><?=htmlspecialchars($vc['imo'])?></td><td><?=htmlspecialchars($vc['viaje_id'])?></td><td><?=htmlspecialchars($vc['berth_name']??'N/A')?></td><td><?=$vc['eta']?date('d/m/Y H:i',strtotime($vc['eta'])):'N/A'?></td><td><span class="badge badge-info"><?=htmlspecialchars($vc['estado_llamada'])?></span></td></tr><?php endforeach;?>
<?php endif;?></tbody></table></div></div>
<?php include 'layout/footer.php';?>

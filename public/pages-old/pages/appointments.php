<?php
if(!hasPermission($pdo,$currentUser['id'],'APPOINTMENT_READ'))die('Acceso denegado');
$fecha=$_GET['fecha']??date('Y-m-d');
$estado=$_GET['estado']??'';
$sql="SELECT a.*,t.placa,c.name as company_name,c.ruc,vc.viaje_id,v.name as vessel_name FROM terrestre.appointment a JOIN terrestre.truck t ON a.truck_id=t.id JOIN terrestre.company c ON a.company_id=c.id LEFT JOIN portuario.vessel_call vc ON a.vessel_call_id=vc.id LEFT JOIN portuario.vessel v ON vc.vessel_id=v.id WHERE DATE(a.hora_programada)=?";
$params=[$fecha];
if($estado){$sql.=" AND a.estado=?";$params[]=$estado;}
$sql.=" ORDER BY a.hora_programada ASC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);
$appointments=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCitas=count($appointments);
$programadas=count(array_filter($appointments,fn($a)=>$a['estado']==='PROGRAMADA'));
include 'layout/header.php';
?>
<div class="page-header"><h1>🚛 Citas de Camiones</h1><p>Gestión de citas y control de acceso</p></div>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">📊</div><div class="stat-content"><div class="stat-number"><?=$totalCitas?></div><div class="stat-label">Total Citas</div></div></div>
<div class="stat-card orange"><div class="stat-icon">📅</div><div class="stat-content"><div class="stat-number"><?=$programadas?></div><div class="stat-label">Programadas</div></div></div>
</div>
<div class="filters"><form method="GET" action="index.php"><input type="hidden" name="page" value="appointments">
<div class="form-group"><label>Fecha</label><input type="date" name="fecha" value="<?=htmlspecialchars($fecha)?>"></div>
<div class="form-group"><label>Estado</label><select name="estado"><option value="">Todos</option><option value="PROGRAMADA" <?=$estado==='PROGRAMADA'?'selected':''?>>Programada</option><option value="COMPLETADA" <?=$estado==='COMPLETADA'?'selected':''?>>Completada</option></select></div>
<button type="submit" class="btn btn-primary">Filtrar</button></form></div>
<div class="card"><div class="card-header"><h3>Listado de Citas</h3><a href="index.php?page=report-r4" class="btn-link">Ver Reporte R4 →</a></div>
<div class="card-body"><table class="data-table"><thead><tr><th>ID</th><th>Placa</th><th>Empresa</th><th>Nave/Viaje</th><th>Hora Programada</th><th>Estado</th></tr></thead><tbody>
<?php if(empty($appointments)):?><tr><td colspan="6" style="text-align:center;padding:40px;color:#6c757d">No se encontraron citas</td></tr><?php else:?>
<?php foreach($appointments as $apt):?><tr><td><strong>#<?=$apt['id']?></strong></td><td><strong><?=htmlspecialchars($apt['placa'])?></strong></td><td><?=htmlspecialchars($apt['company_name'])?></td><td><?=$apt['vessel_name']?htmlspecialchars($apt['vessel_name']).'<br><small>'.htmlspecialchars($apt['viaje_id']).'</small>':'N/A'?></td><td><?=date('d/m/Y H:i',strtotime($apt['hora_programada']))?></td><td><span class="badge badge-info"><?=htmlspecialchars($apt['estado'])?></span></td></tr><?php endforeach;?>
<?php endif;?></tbody></table></div></div>
<?php include 'layout/footer.php';?>

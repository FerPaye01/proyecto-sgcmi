<?php
if(!hasPermission($pdo,$currentUser['id'],'ADUANA_READ'))die('Acceso denegado');
$estado=$_GET['estado']??'';
$sql="SELECT t.*,e.name as entidad_name,vc.viaje_id,v.name as vessel_name FROM aduanas.tramite t LEFT JOIN aduanas.entidad e ON t.entidad_id=e.id LEFT JOIN portuario.vessel_call vc ON t.vessel_call_id=vc.id LEFT JOIN portuario.vessel v ON vc.vessel_id=v.id WHERE 1=1";
$params=[];
if($estado){$sql.=" AND t.estado=?";$params[]=$estado;}
$sql.=" ORDER BY t.fecha_inicio DESC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);
$tramites=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalTramites=count($tramites);
$completos=count(array_filter($tramites,fn($t)=>$t['estado']==='COMPLETO'));
include 'layout/header.php';
?>
<div class="page-header"><h1>📋 Trámites Aduaneros</h1><p>Gestión y seguimiento de trámites de comercio exterior</p></div>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">📊</div><div class="stat-content"><div class="stat-number"><?=$totalTramites?></div><div class="stat-label">Total Trámites</div></div></div>
<div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-content"><div class="stat-number"><?=$completos?></div><div class="stat-label">Completos</div></div></div>
</div>
<div class="filters"><form method="GET" action="index.php"><input type="hidden" name="page" value="tramites">
<div class="form-group"><label>Estado</label><select name="estado"><option value="">Todos</option><option value="INICIADO" <?=$estado==='INICIADO'?'selected':''?>>Iniciado</option><option value="EN_PROCESO" <?=$estado==='EN_PROCESO'?'selected':''?>>En Proceso</option><option value="COMPLETO" <?=$estado==='COMPLETO'?'selected':''?>>Completo</option></select></div>
<button type="submit" class="btn btn-primary">Filtrar</button></form></div>
<div class="card"><div class="card-header"><h3>Listado de Trámites</h3></div>
<div class="card-body"><table class="data-table"><thead><tr><th>ID Trámite</th><th>Régimen</th><th>Nave/Viaje</th><th>Entidad</th><th>Fecha Inicio</th><th>Estado</th></tr></thead><tbody>
<?php if(empty($tramites)):?><tr><td colspan="6" style="text-align:center;padding:40px;color:#6c757d">No se encontraron trámites</td></tr><?php else:?>
<?php foreach($tramites as $t):?><tr><td><strong><?=htmlspecialchars($t['tramite_ext_id'])?></strong></td><td><span class="badge badge-info"><?=htmlspecialchars($t['regimen'])?></span></td><td><?=$t['vessel_name']?htmlspecialchars($t['vessel_name']).'<br><small>'.htmlspecialchars($t['viaje_id']).'</small>':'N/A'?></td><td><?=htmlspecialchars($t['entidad_name']??'N/A')?></td><td><?=date('d/m/Y H:i',strtotime($t['fecha_inicio']))?></td><td><span class="badge badge-<?=$t['estado']==='COMPLETO'?'success':'warning'?>"><?=htmlspecialchars($t['estado'])?></span></td></tr><?php endforeach;?>
<?php endif;?></tbody></table></div></div>
<?php include 'layout/footer.php';?>

<?php
// R11: Alertas Tempranas
$stmt = $pdo->query("SELECT * FROM analytics.alerts WHERE estado = 'ACTIVA' ORDER BY detected_at DESC LIMIT 50");
$alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$alertas_rojas = count(array_filter($alertas, fn($a) => $a['nivel'] == 'ROJO'));
$alertas_amarillas = count(array_filter($alertas, fn($a) => $a['nivel'] == 'AMARILLO'));
$alertas_verdes = count(array_filter($alertas, fn($a) => $a['nivel'] == 'VERDE'));
$estado_general = $alertas_rojas > 0 ? 'ROJO' : ($alertas_amarillas > 0 ? 'AMARILLO' : 'VERDE');
include 'layout/header.php';
?>
<div class="page-header"><h1>🚨 R11: Alertas Tempranas</h1><p>Congestión de muelles, acumulación de camiones</p></div>
<div class="stats-grid" style="margin-bottom: 2rem;"><div class="stat-card <?=$estado_general=='ROJO'?'red':($estado_general=='AMARILLO'?'orange':'green')?>"><div class="stat-content"><div class="stat-number"><?=$estado_general?></div><div class="stat-label">Estado General del Sistema</div></div></div><div class="stat-card red"><div class="stat-content"><div class="stat-number"><?=$alertas_rojas?></div><div class="stat-label">Alertas Críticas</div></div></div><div class="stat-card orange"><div class="stat-content"><div class="stat-number"><?=$alertas_amarillas?></div><div class="stat-label">Alertas Advertencia</div></div></div><div class="stat-card green"><div class="stat-content"><div class="stat-number"><?=$alertas_verdes?></div><div class="stat-label">Alertas Normales</div></div></div></div>
<div class="card"><div class="card-header"><h3>🚨 Alertas Activas</h3></div><div class="card-body"><?php if (empty($alertas)): ?><div style="text-align: center; padding: 2rem; color: #10b981;"><div style="font-size: 3rem;">✅</div><h3>Sistema Operando Normalmente</h3><p>No hay alertas activas en este momento</p></div><?php else: ?><table class="data-table"><thead><tr><th>Tipo</th><th>Nivel</th><th>Descripción</th><th>Valor</th><th>Umbral</th><th>Detectada</th></tr></thead><tbody><?php foreach ($alertas as $alerta): ?><?php $badge = $alerta['nivel']=='ROJO'?'badge-danger':($alerta['nivel']=='AMARILLO'?'badge-warning':'badge-success'); ?><tr><td><?=htmlspecialchars($alerta['tipo'])?></td><td><span class="badge <?=$badge?>"><?=htmlspecialchars($alerta['nivel'])?></span></td><td><?=htmlspecialchars($alerta['descripción'])?></td><td><strong><?=htmlspecialchars($alerta['valor'])?><?=htmlspecialchars($alerta['unidad'])?></strong></td><td><?=htmlspecialchars($alerta['umbral'])?><?=htmlspecialchars($alerta['unidad'])?></td><td><?=date('d/m/Y H:i', strtotime($alerta['detected_at']))?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<?php include 'layout/footer.php'; ?>

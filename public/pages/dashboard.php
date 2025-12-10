<?php
$stats = [
    'vessel_calls' => $pdo->query("SELECT COUNT(*) FROM portuario.vessel_call WHERE estado_llamada = 'PROGRAMADA'")->fetchColumn(),
    'appointments' => $pdo->query("SELECT COUNT(*) FROM terrestre.appointment WHERE estado IN ('PROGRAMADA','CONFIRMADA')")->fetchColumn(),
    'tramites_pending' => $pdo->query("SELECT COUNT(*) FROM aduanas.tramite WHERE estado IN ('INICIADO','EN_PROCESO')")->fetchColumn(),
    'users_active' => $pdo->query("SELECT COUNT(*) FROM admin.users WHERE is_active = TRUE")->fetchColumn(),
];
$recentVessels = $pdo->query("SELECT vc.*, v.name as vessel_name, b.name as berth_name FROM portuario.vessel_call vc JOIN portuario.vessel v ON vc.vessel_id = v.id LEFT JOIN portuario.berth b ON vc.berth_id = b.id ORDER BY vc.eta DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recentAppointments = $pdo->query("SELECT a.*, t.placa, c.name as company_name FROM terrestre.appointment a JOIN terrestre.truck t ON a.truck_id = t.id JOIN terrestre.company c ON a.company_id = c.id ORDER BY a.hora_programada DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
include 'layout/header.php';
?>
<div class="page-header"><h1>📊 Dashboard</h1><p>Bienvenido, <?=htmlspecialchars($currentUser['full_name'])?> (<?=htmlspecialchars($currentUser['role_name'])?>)</p></div>
<div class="stats-grid">
<div class="stat-card blue"><div class="stat-icon">🚢</div><div class="stat-content"><div class="stat-number"><?=$stats['vessel_calls']?></div><div class="stat-label">Naves Programadas</div></div></div>
<div class="stat-card green"><div class="stat-icon">🚛</div><div class="stat-content"><div class="stat-number"><?=$stats['appointments']?></div><div class="stat-label">Citas Pendientes</div></div></div>
<div class="stat-card orange"><div class="stat-icon">📋</div><div class="stat-content"><div class="stat-number"><?=$stats['tramites_pending']?></div><div class="stat-label">Trámites en Proceso</div></div></div>
<div class="stat-card purple"><div class="stat-icon">👥</div><div class="stat-content"><div class="stat-number"><?=$stats['users_active']?></div><div class="stat-label">Usuarios Activos</div></div></div>
</div>
<div class="dashboard-grid">
<div class="card"><div class="card-header"><h3>🚢 Llamadas de Naves Recientes</h3><a href="index.php?page=vessel-calls" class="btn-link">Ver todas →</a></div>
<div class="card-body"><table class="data-table"><thead><tr><th>Nave</th><th>Viaje</th><th>Muelle</th><th>ETA</th><th>Estado</th></tr></thead><tbody>
<?php foreach($recentVessels as $vc):?><tr><td><?=htmlspecialchars($vc['vessel_name'])?></td><td><?=htmlspecialchars($vc['viaje_id'])?></td><td><?=htmlspecialchars($vc['berth_name']??'N/A')?></td><td><?=date('d/m/Y H:i',strtotime($vc['eta']))?></td><td><span class="badge badge-info"><?=htmlspecialchars($vc['estado_llamada'])?></span></td></tr><?php endforeach;?>
</tbody></table></div></div>
<div class="card"><div class="card-header"><h3>🚛 Citas de Camiones Recientes</h3><a href="index.php?page=appointments" class="btn-link">Ver todas →</a></div>
<div class="card-body"><table class="data-table"><thead><tr><th>Placa</th><th>Empresa</th><th>Hora Programada</th><th>Estado</th></tr></thead><tbody>
<?php foreach($recentAppointments as $apt):?><tr><td><strong><?=htmlspecialchars($apt['placa'])?></strong></td><td><?=htmlspecialchars($apt['company_name'])?></td><td><?=date('d/m/Y H:i',strtotime($apt['hora_programada']))?></td><td><span class="badge badge-success"><?=htmlspecialchars($apt['estado'])?></span></td></tr><?php endforeach;?>
</tbody></table></div></div>
</div>
<!-- Sección de Reportes Disponibles -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>📊 Reportes Disponibles</h3>
        <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Acceso rápido a los 12 reportes del sistema</p>
    </div>
    <div class="card-body">
        <!-- Reportes Portuarios -->
        <div style="margin-bottom: 2rem;">
            <h4 style="color: #2563eb; margin-bottom: 1rem; font-size: 1.1rem;">🚢 Módulo Portuario</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <a href="index.php?page=report-r1" class="report-card">
                    <div class="report-icon" style="background: #dbeafe;">📅</div>
                    <div>
                        <div class="report-title">R1: Programación vs Ejecución</div>
                        <div class="report-desc">Comparación ETA/ETB vs ATA/ATB, puntualidad de arribo</div>
                    </div>
                </a>
                <a href="index.php?page=report-r2" class="report-card">
                    <div class="report-icon" style="background: #dbeafe;">⏱️</div>
                    <div>
                        <div class="report-title">R2: Turnaround de Naves</div>
                        <div class="report-desc">Tiempo de permanencia en puerto (ATA → ATD)</div>
                    </div>
                </a>
                <a href="index.php?page=report-r3" class="report-card">
                    <div class="report-icon" style="background: #dbeafe;">📊</div>
                    <div>
                        <div class="report-title">R3: Utilización de Muelles</div>
                        <div class="report-desc">Utilización por franja horaria, conflictos de ventana</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Reportes Terrestres -->
        <div style="margin-bottom: 2rem;">
            <h4 style="color: #16a34a; margin-bottom: 1rem; font-size: 1.1rem;">🚛 Módulo Terrestre</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <a href="index.php?page=report-r4" class="report-card">
                    <div class="report-icon" style="background: #dcfce7;">⏳</div>
                    <div>
                        <div class="report-title">R4: Tiempo de Espera</div>
                        <div class="report-desc">Espera de camiones desde llegada hasta atención</div>
                    </div>
                </a>
                <a href="index.php?page=report-r5" class="report-card">
                    <div class="report-icon" style="background: #dcfce7;">✅</div>
                    <div>
                        <div class="report-title">R5: Cumplimiento de Citas</div>
                        <div class="report-desc">Clasificación: A tiempo, Tarde, No Show</div>
                    </div>
                </a>
                <a href="index.php?page=report-r6" class="report-card">
                    <div class="report-icon" style="background: #dcfce7;">🚪</div>
                    <div>
                        <div class="report-title">R6: Productividad de Gates</div>
                        <div class="report-desc">Vehículos por hora, tiempo de ciclo, horas pico</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Reportes Aduaneros -->
        <div style="margin-bottom: 2rem;">
            <h4 style="color: #dc2626; margin-bottom: 1rem; font-size: 1.1rem;">📋 Módulo Aduanero</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <a href="index.php?page=report-r7" class="report-card">
                    <div class="report-icon" style="background: #fee2e2;">📦</div>
                    <div>
                        <div class="report-title">R7: Estado de Trámites por Nave</div>
                        <div class="report-desc">Trámites completos pre-arribo, lead time</div>
                    </div>
                </a>
                <a href="index.php?page=report-r8" class="report-card">
                    <div class="report-icon" style="background: #fee2e2;">⚡</div>
                    <div>
                        <div class="report-title">R8: Tiempo de Despacho</div>
                        <div class="report-desc">Percentiles P50/P90 por régimen aduanero</div>
                    </div>
                </a>
                <a href="index.php?page=report-r9" class="report-card">
                    <div class="report-icon" style="background: #fee2e2;">⚠️</div>
                    <div>
                        <div class="report-title">R9: Incidencias Documentación</div>
                        <div class="report-desc">Rechazos, reprocesamientos, tiempo de subsanación</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Reportes Analytics -->
        <div>
            <h4 style="color: #7c3aed; margin-bottom: 1rem; font-size: 1.1rem;">📈 Módulo Analytics</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <a href="index.php?page=kpi-panel" class="report-card">
                    <div class="report-icon" style="background: #ede9fe;">📊</div>
                    <div>
                        <div class="report-title">R10: Panel de KPIs</div>
                        <div class="report-desc">KPIs consolidados con tendencias y comparativas</div>
                    </div>
                </a>
                <a href="index.php?page=report-r11" class="report-card">
                    <div class="report-icon" style="background: #ede9fe;">🚨</div>
                    <div>
                        <div class="report-title">R11: Alertas Tempranas</div>
                        <div class="report-desc">Congestión de muelles, acumulación de camiones</div>
                    </div>
                </a>
                <a href="index.php?page=report-r12" class="report-card">
                    <div class="report-icon" style="background: #ede9fe;">🎯</div>
                    <div>
                        <div class="report-title">R12: Cumplimiento de SLAs</div>
                        <div class="report-desc">Cumplimiento por actor, penalidades, incumplimientos</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}
.report-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
.report-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.report-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}
.report-desc {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>

<?php include 'layout/footer.php';?>

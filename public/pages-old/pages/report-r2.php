<?php
// R2: Turnaround de Naves
$filters = [
    'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'),
    'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d'),
];

// Obtener datos del reporte
$stmt = $pdo->prepare("
    SELECT 
        vc.id,
        v.name as vessel_name,
        v.imo,
        vc.viaje_id,
        b.name as berth_name,
        vc.ata,
        vc.atd,
        EXTRACT(EPOCH FROM (vc.atd - vc.ata))/3600 as turnaround_h
    FROM portuario.vessel_call vc
    JOIN portuario.vessel v ON vc.vessel_id = v.id
    LEFT JOIN portuario.berth b ON vc.berth_id = b.id
    WHERE vc.ata IS NOT NULL 
    AND vc.atd IS NOT NULL
    AND vc.ata >= :fecha_desde
    AND vc.ata <= :fecha_hasta
    ORDER BY vc.ata DESC
");
$stmt->execute($filters);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular KPIs
$turnarounds = array_column($data, 'turnaround_h');
$kpis = [
    'total_naves' => count($data),
    'turnaround_promedio' => count($turnarounds) > 0 ? round(array_sum($turnarounds) / count($turnarounds), 2) : 0,
    'turnaround_min' => count($turnarounds) > 0 ? round(min($turnarounds), 2) : 0,
    'turnaround_max' => count($turnarounds) > 0 ? round(max($turnarounds), 2) : 0,
];

// Calcular P95
if (count($turnarounds) > 0) {
    sort($turnarounds);
    $index = (int)ceil(0.95 * count($turnarounds)) - 1;
    $kpis['p95_turnaround'] = round($turnarounds[$index], 2);
} else {
    $kpis['p95_turnaround'] = 0;
}

include 'layout/header.php';
?>

<div class="page-header">
    <h1>⏱️ R2: Turnaround de Naves</h1>
    <p>Tiempo de permanencia en puerto (ATA → ATD)</p>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="report-r2">
            <div>
                <label>Fecha Desde:</label>
                <input type="date" name="fecha_desde" value="<?=htmlspecialchars($filters['fecha_desde'])?>" class="form-input">
            </div>
            <div>
                <label>Fecha Hasta:</label>
                <input type="date" name="fecha_hasta" value="<?=htmlspecialchars($filters['fecha_hasta'])?>" class="form-input">
            </div>
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="?page=report-r2" class="btn btn-secondary">🔄 Limpiar</a>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card blue">
        <div class="stat-content">
            <div class="stat-number"><?=$kpis['total_naves']?></div>
            <div class="stat-label">Total Naves</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-content">
            <div class="stat-number"><?=$kpis['turnaround_promedio']?>h</div>
            <div class="stat-label">Turnaround Promedio</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-content">
            <div class="stat-number"><?=$kpis['p95_turnaround']?>h</div>
            <div class="stat-label">Percentil 95</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-content">
            <div class="stat-number"><?=$kpis['turnaround_min']?>h - <?=$kpis['turnaround_max']?>h</div>
            <div class="stat-label">Rango (Min - Max)</div>
        </div>
    </div>
</div>

<!-- Tabla de datos -->
<div class="card">
    <div class="card-header">
        <h3>📊 Detalle de Turnaround por Nave</h3>
        <button onclick="exportToCSV()" class="btn btn-secondary">📥 Exportar CSV</button>
    </div>
    <div class="card-body">
        <table class="data-table" id="reportTable">
            <thead>
                <tr>
                    <th>Nave</th>
                    <th>IMO</th>
                    <th>Viaje</th>
                    <th>Muelle</th>
                    <th>ATA</th>
                    <th>ATD</th>
                    <th>Turnaround (h)</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="8" style="text-align: center; color: #666;">No hay datos disponibles para el periodo seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <?php
                        $turnaround = $row['turnaround_h'];
                        $badge_class = $turnaround <= 48 ? 'badge-success' : ($turnaround <= 72 ? 'badge-warning' : 'badge-danger');
                        ?>
                        <tr>
                            <td><strong><?=htmlspecialchars($row['vessel_name'])?></strong></td>
                            <td><?=htmlspecialchars($row['imo'])?></td>
                            <td><?=htmlspecialchars($row['viaje_id'])?></td>
                            <td><?=htmlspecialchars($row['berth_name'] ?? 'N/A')?></td>
                            <td><?=date('d/m/Y H:i', strtotime($row['ata']))?></td>
                            <td><?=date('d/m/Y H:i', strtotime($row['atd']))?></td>
                            <td><strong><?=round($turnaround, 2)?>h</strong></td>
                            <td><span class="badge <?=$badge_class?>"><?=$turnaround <= 48 ? 'Óptimo' : ($turnaround <= 72 ? 'Aceptable' : 'Excedido')?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('reportTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        for (let col of cols) {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        }
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reporte_r2_turnaround_<?=date("Y-m-d")?>.csv';
    a.click();
}
</script>

<?php include 'layout/footer.php'; ?>

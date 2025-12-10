<?php
// SGCMI Database Viewer - Simple Web Interface
// Acceder en: http://localhost:8000/db-viewer.php

$host = '127.0.0.1';
$port = '5432';
$dbname = 'sgcmi';
$user = 'postgres';
$password = '1234';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$schema = $_GET['schema'] ?? 'admin';
$table = $_GET['table'] ?? '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCMI - Database Viewer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        .grid { display: grid; grid-template-columns: 250px 1fr; gap: 20px; }
        .sidebar { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content; }
        .sidebar h3 { margin-bottom: 15px; color: #333; font-size: 16px; }
        .schema-list { list-style: none; }
        .schema-list li { margin-bottom: 10px; }
        .schema-list a { display: block; padding: 10px; background: #f8f9fa; border-radius: 5px; text-decoration: none; color: #495057; transition: all 0.2s; }
        .schema-list a:hover, .schema-list a.active { background: #667eea; color: white; }
        .main { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; }
        .stat-card h4 { font-size: 14px; opacity: 0.9; margin-bottom: 5px; }
        .stat-card .number { font-size: 32px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .table-list { list-style: none; margin-top: 10px; }
        .table-list li { margin-bottom: 5px; }
        .table-list a { color: #667eea; text-decoration: none; font-size: 14px; }
        .table-list a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚢 SGCMI - Database Viewer</h1>
        <p>Sistema de Gestión y Coordinación Multimodal Integrado</p>
    </div>

    <div class="container">
        <?php if (!$table): ?>
        <div class="stats">
            <?php
            $schemas = ['admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit'];
            foreach ($schemas as $s) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$s'");
                $count = $stmt->fetchColumn();
                echo "<div class='stat-card'>";
                echo "<h4>" . strtoupper($s) . "</h4>";
                echo "<div class='number'>$count</div>";
                echo "<small>tablas</small>";
                echo "</div>";
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="grid">
            <div class="sidebar">
                <h3>📊 Schemas</h3>
                <ul class="schema-list">
                    <?php
                    $schemas = ['admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit'];
                    foreach ($schemas as $s) {
                        $active = $schema === $s ? 'active' : '';
                        echo "<li><a href='?schema=$s' class='$active'>$s</a>";
                        
                        if ($schema === $s) {
                            $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$s' ORDER BY table_name");
                            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            if ($tables) {
                                echo "<ul class='table-list'>";
                                foreach ($tables as $t) {
                                    echo "<li><a href='?schema=$s&table=$t'>→ $t</a></li>";
                                }
                                echo "</ul>";
                            }
                        }
                        echo "</li>";
                    }
                    ?>
                </ul>
            </div>

            <div class="main">
                <?php if ($table): ?>
                    <h2>📋 <?= htmlspecialchars($schema) ?>.<?= htmlspecialchars($table) ?></h2>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM $schema.$table LIMIT 100");
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($rows) {
                            echo "<p style='color: #6c757d; margin: 10px 0;'>Mostrando " . count($rows) . " registros</p>";
                            echo "<table>";
                            echo "<thead><tr>";
                            foreach (array_keys($rows[0]) as $col) {
                                echo "<th>" . htmlspecialchars($col) . "</th>";
                            }
                            echo "</tr></thead><tbody>";
                            
                            foreach ($rows as $row) {
                                echo "<tr>";
                                foreach ($row as $val) {
                                    if (is_bool($val)) {
                                        $badge = $val ? 'badge-success' : 'badge-warning';
                                        $text = $val ? 'TRUE' : 'FALSE';
                                        echo "<td><span class='badge $badge'>$text</span></td>";
                                    } else {
                                        echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
                                    }
                                }
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                        } else {
                            echo "<p style='color: #6c757d; padding: 40px; text-align: center;'>No hay datos en esta tabla</p>";
                        }
                    } catch (PDOException $e) {
                        echo "<p style='color: #dc3545;'>Error: " . $e->getMessage() . "</p>";
                    }
                    ?>
                <?php else: ?>
                    <h2>👋 Bienvenido al SGCMI Database Viewer</h2>
                    <p style='color: #6c757d; margin: 20px 0;'>Selecciona un schema de la izquierda para explorar las tablas y datos.</p>
                    
                    <h3 style='margin-top: 30px;'>📈 Resumen del Sistema</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stats = [
                                ['Schemas', 7, 'success'],
                                ['Tablas Totales', 22, 'success'],
                                ['Usuarios', $pdo->query("SELECT COUNT(*) FROM admin.users")->fetchColumn(), 'success'],
                                ['Roles', $pdo->query("SELECT COUNT(*) FROM admin.roles")->fetchColumn(), 'success'],
                                ['Permisos', $pdo->query("SELECT COUNT(*) FROM admin.permissions")->fetchColumn(), 'success'],
                                ['Naves', $pdo->query("SELECT COUNT(*) FROM portuario.vessel")->fetchColumn(), 'info'],
                                ['Llamadas', $pdo->query("SELECT COUNT(*) FROM portuario.vessel_call")->fetchColumn(), 'info'],
                                ['Citas', $pdo->query("SELECT COUNT(*) FROM terrestre.appointment")->fetchColumn(), 'info'],
                                ['Trámites', $pdo->query("SELECT COUNT(*) FROM aduanas.tramite")->fetchColumn(), 'info'],
                            ];
                            
                            foreach ($stats as $stat) {
                                echo "<tr>";
                                echo "<td>{$stat[0]}</td>";
                                echo "<td><strong>{$stat[1]}</strong></td>";
                                echo "<td><span class='badge badge-{$stat[2]}'>✓ OK</span></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
session_start();
$pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=sgcmi", "postgres", "1234");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$page = $_GET['page'] ?? 'login';
$requiresAuth = !in_array($page, ['login', 'do-login', 'logout']);

if ($requiresAuth && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT u.*, r.code as role_code, r.name as role_name FROM admin.users u JOIN admin.user_roles ur ON u.id = ur.user_id JOIN admin.roles r ON ur.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

function hasPermission($pdo, $userId, $permission) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin.role_permissions rp JOIN admin.user_roles ur ON rp.role_id = ur.role_id JOIN admin.permissions p ON rp.permission_id = p.id WHERE ur.user_id = ? AND p.code = ?");
    $stmt->execute([$userId, $permission]);
    return $stmt->fetchColumn() > 0;
}

switch ($page) {
    case 'login': include 'pages/login.php'; break;
    case 'do-login': include 'pages/do-login.php'; break;
    case 'logout': session_destroy(); header('Location: index.php?page=login'); break;
    case 'dashboard': include 'pages/dashboard.php'; break;
    
    // Módulos principales
    case 'vessel-calls': include 'pages/vessel-calls.php'; break;
    case 'appointments': include 'pages/appointments.php'; break;
    case 'tramites': include 'pages/tramites.php'; break;
    
    // Reportes Portuarios (R1-R3)
    case 'report-r1': include 'pages/report-r1.php'; break;
    case 'report-r2': include 'pages/report-r2.php'; break;
    case 'report-r3': include 'pages/report-r3.php'; break;
    
    // Reportes Terrestres (R4-R6)
    case 'report-r4': include 'pages/report-r4.php'; break;
    case 'report-r5': include 'pages/report-r5.php'; break;
    case 'report-r6': include 'pages/report-r6.php'; break;
    
    // Reportes Aduaneros (R7-R9)
    case 'report-r7': include 'pages/report-r7.php'; break;
    case 'report-r8': include 'pages/report-r8.php'; break;
    case 'report-r9': include 'pages/report-r9.php'; break;
    
    // Reportes Analytics (R10-R12)
    case 'kpi-panel': 
    case 'report-r10': include 'pages/kpi-panel.php'; break;
    case 'report-r11': include 'pages/report-r11.php'; break;
    case 'report-r12': include 'pages/report-r12.php'; break;
    
    default: include 'pages/dashboard.php';
}

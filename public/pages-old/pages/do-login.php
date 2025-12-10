<?php
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['error'] = 'Por favor ingrese usuario y contraseña';
    header('Location: index.php?page=login');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admin.users WHERE username = ? AND is_active = TRUE");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Usuario o contraseña incorrectos';
    header('Location: index.php?page=login');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['full_name'] = $user['full_name'];

$stmt = $pdo->prepare("INSERT INTO audit.audit_log (event_ts, actor_user, action, object_schema, object_table, details) VALUES (NOW(), ?, 'LOGIN', 'admin', 'users', ?::jsonb)");
$stmt->execute([$user['username'], json_encode(['user_id' => $user['id'], 'ip' => $_SERVER['REMOTE_ADDR']])]);

header('Location: index.php?page=dashboard');

<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>SGCMI - Login</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}.login-container{background:#fff;padding:40px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.2);width:100%;max-width:400px}.logo{text-align:center;margin-bottom:30px}.logo h1{font-size:32px;color:#667eea;margin-bottom:5px}.logo p{color:#6c757d;font-size:14px}.form-group{margin-bottom:20px}label{display:block;margin-bottom:8px;color:#495057;font-weight:500}input{width:100%;padding:12px;border:2px solid #e9ecef;border-radius:6px;font-size:14px}input:focus{outline:none;border-color:#667eea}button{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer}button:hover{transform:translateY(-2px)}.demo-users{margin-top:30px;padding-top:20px;border-top:1px solid #e9ecef}.demo-users h3{font-size:14px;color:#6c757d;margin-bottom:10px}.user-badge{display:inline-block;padding:6px 12px;margin:4px;background:#f8f9fa;border-radius:4px;font-size:12px;cursor:pointer}.user-badge:hover{background:#667eea;color:#fff}.alert{padding:12px;margin-bottom:20px;border-radius:6px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}</style>
</head><body>
<div class="login-container">
<div class="logo"><h1>🚢 SGCMI</h1><p>Sistema de Gestión y Coordinación Multimodal Integrado</p></div>
<?php if(isset($_SESSION['error'])):?><div class="alert"><?=htmlspecialchars($_SESSION['error'])?><?php unset($_SESSION['error']);?></div><?php endif;?>
<form action="index.php?page=do-login" method="POST">
<div class="form-group"><label for="username">Usuario</label><input type="text" id="username" name="username" required autofocus></div>
<div class="form-group"><label for="password">Contraseña</label><input type="password" id="password" name="password" required></div>
<button type="submit">Iniciar Sesión</button>
</form>
<div class="demo-users"><h3>👥 Usuarios Demo (click para autocompletar):</h3>
<span class="user-badge" onclick="fillLogin('admin')">admin</span>
<span class="user-badge" onclick="fillLogin('planificador')">planificador</span>
<span class="user-badge" onclick="fillLogin('analista')">analista</span>
<span class="user-badge" onclick="fillLogin('gates')">gates</span>
<span class="user-badge" onclick="fillLogin('aduana')">aduana</span>
<p style="margin-top:10px;font-size:12px;color:#6c757d">Contraseña para todos: <strong>password123</strong></p>
</div></div>
<script>function fillLogin(u){document.getElementById('username').value=u;document.getElementById('password').value='password123';}</script>
</body></html>

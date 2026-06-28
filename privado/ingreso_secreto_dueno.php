<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        // Buscamos al administrador en la base de datos usando PDO
        $stmt = $pdo->prepare("SELECT id, password_hash, secreto_2fa FROM administradores WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        // Verificamos si existe y si la contraseña coincide con el hash guardado
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Guardamos en la sesión que pasó el primer paso de autenticación
            $_SESSION['auth_paso1'] = $admin['id'];
            $_SESSION['auth_secreto_temp'] = $admin['secreto_2fa'];
            
            // Redirigimos al segundo paso en el teléfono
            header("Location: verificar_telefono.php");
            exit;
        } else {
            $error = "Credenciales de acceso no válidas.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Área Privada</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #1a252f; }
        .error { color: #e74c3c; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Control de Acceso</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="usuario" placeholder="Usuario Administrador" required autocomplete="off">
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Siguiente Paso</button>
        </form>
    </div>
</body>
</html>
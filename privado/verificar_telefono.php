<?php
session_start();

if (!isset($_SESSION['auth_paso1'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/GoogleAuthenticator.php';
require_once __DIR__ . '/../csrf_helper.php';

$ga = new PHPGangsta_GoogleAuthenticator();

$max_intentos = 5;
$ventana_minutos = 15;
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$admin_id = $_SESSION['auth_paso1'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
$stmt->execute([$ip, $ventana_minutos]);
$intentos_recientes = $stmt->fetchColumn();
$bloqueado = $intentos_recientes >= $max_intentos;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    if ($bloqueado) {
        $error = "Demasiados intentos. Espera $ventana_minutos minutos.";
    } else {
        $codigo_2fa = trim($_POST['codigo_2fa']);
        $secreto = $_SESSION['auth_secreto_temp'];
        $es_valido = $ga->verifyCode($secreto, $codigo_2fa, 2);

        if ($es_valido) {
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
            session_regenerate_id(true);
            $_SESSION['dueno_logeado'] = true;
            $_SESSION['dueno_id'] = $admin_id;
            unset($_SESSION['auth_paso1']);
            unset($_SESSION['auth_secreto_temp']);
            header("Location: panel_control.php");
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
            $stmt->execute([$ip, '2fa_user_' . $admin_id]);
            $error = "El código digital ingresado no es válido o ya expiró.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Seguridad</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        h3 { color: #333; }
        p { color: #666; font-size: 14px; line-height: 1.5; }
        input[type="text"] { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; text-align: center; font-size: 20px; letter-spacing: 4px; }
        button { width: 100%; padding: 10px; background-color: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #219653; }
        .error { color: #e74c3c; font-size: 14px; }
    </style>
</head>
<body>
    <div class="auth-box">
        <h3>Verificación de Identidad</h3>
        <p>Abre la aplicación de autenticación en tu teléfono móvil e ingresa el código de seguridad actual de 6 dígitos.</p>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($bloqueado): ?>
            <p class="error" style="margin-top:10px;">⏳ Demasiados códigos inválidos. Espera <?php echo $ventana_minutos; ?> minutos.</p>
        <?php else: ?>
        <form method="POST" action="">
            <?php echo csrf_campo(); ?>
            <input type="text" name="codigo_2fa" placeholder="000000" maxlength="6" required autocomplete="off">
            <button type="submit">Verificar y Entrar</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
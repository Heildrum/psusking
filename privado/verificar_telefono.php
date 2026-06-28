<?php
session_start();

// Seguridad: Si no ha completado el paso 1, no puede estar aquí
if (!isset($_SESSION['auth_paso1'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}

require_once 'GoogleAuthenticator.php';
$ga = new PHPGangsta_GoogleAuthenticator();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_2fa = trim($_POST['codigo_2fa']);
    $secreto = $_SESSION['auth_secreto_temp'];

    // Verifica el código del teléfono. El "2" permite una tolerancia de 2 bloques de 30 segundos 
    // por si el reloj de tu teléfono tiene una pequeña diferencia horaria con el servidor
    $es_valido = $ga->verifyCode($secreto, $codigo_2fa, 2);

    if ($es_valido) {
        // ¡Autenticación completa con éxito!
        $_SESSION['dueno_logeado'] = true;
        $_SESSION['dueno_id'] = $_SESSION['auth_paso1'];
        
        // Limpiamos las variables temporales del paso anterior
        unset($_SESSION['auth_paso1']);
        unset($_SESSION['auth_secreto_temp']);
        
        // Redirigimos a tu panel privado de gestión de perfumes
        header("Location: panel_control.php");
        exit;
    } else {
        $error = "El código digital ingresado no es válido o ya expiró.";
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
        <form method="POST" action="">
            <input type="text" name="codigo_2fa" placeholder="000000" maxlength="6" required autocomplete="off">
            <button type="submit">Verificar y Entrar</button>
        </form>
    </div>
</body>
</html>
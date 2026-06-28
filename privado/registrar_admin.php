<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/GoogleAuthenticator.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$ga = new PHPGangsta_GoogleAuthenticator();

$usuario = 'admin';
$password = 'Psusking2024';
$secreto_2fa = $ga->createSecret();

$password_hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO administradores (usuario, password_hash, secreto_2fa) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), secreto_2fa = VALUES(secreto_2fa)");
$stmt->execute([$usuario, $password_hash, $secreto_2fa]);

$otpauth_uri = 'otpauth://totp/Psusking:' . $usuario . '?secret=' . $secreto_2fa . '&issuer=Psusking';

$options = new QROptions;
$options->outputType = QRCode::OUTPUT_IMAGE_PNG;
$options->scale = 10;
$qr_b64 = (new QRCode($options))->render($otpauth_uri);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Registrado</title>
    <style>
        body{font-family:Arial;padding:40px;background:#f4f4f9;}
        .box{background:white;padding:30px;border-radius:8px;max-width:600px;margin:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
        code{background:#eee;padding:4px 8px;border-radius:4px;}
        img.qr{border:2px solid #ddd;border-radius:8px;max-width:250px;}
    </style>
</head>
<body>
    <div class="box">
        <h2>✅ Administrador Registrado</h2>
        <p><strong>Usuario:</strong> <code><?php echo $usuario; ?></code></p>
        <p><strong>Contraseña:</strong> <code><?php echo $password; ?></code></p>
        <p><strong>Secreto 2FA:</strong> <code><?php echo $secreto_2fa; ?></code></p>
        <h3>📱 Escanea este QR con Google Authenticator:</h3>
        <img src="<?php echo $qr_b64; ?>" alt="QR Code" class="qr">
        <p style="color:#666;font-size:14px;">También puedes tocar <strong>+</strong> → <strong>Ingresar clave de configuración</strong>:</p>
        <table style="background:#f9f9f9;padding:15px;border-radius:8px;margin:10px 0;">
            <tr><td style="font-weight:bold;padding:5px;">Cuenta:</td><td style="padding:5px;"><code style="font-size:16px;">Psusking (admin)</code></td></tr>
            <tr><td style="font-weight:bold;padding:5px;">Clave:</td><td style="padding:5px;"><code style="font-size:20px;letter-spacing:2px;"><?php echo $secreto_2fa; ?></code></td></tr>
            <tr><td style="font-weight:bold;padding:5px;">Tipo:</td><td style="padding:5px;"><code>Basado en tiempo</code></td></tr>
        </table>
        <p style="margin-top:20px;"><a href="ingreso_secreto_dueno.php" style="background:#2c3e50;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;">Ir al Login</a></p>
    </div>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$pedido_id = filter_input(INPUT_GET, 'pedido_id', FILTER_VALIDATE_INT);
if (!$pedido_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    header("Location: index.php");
    exit;
}

$cuenta = $pdo->query("SELECT * FROM cuenta_bancaria WHERE id = 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transferencia'])) {
    $stmt = $pdo->prepare("UPDATE pedidos SET estado_pago = 'Pendiente' WHERE id = ? AND estado_pago = 'Por_Pagar'");
    $stmt->execute([$pedido_id]);

    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elixirstorespa@gmail.com';
        $mail->Password   = 'ivxf jegt ghts nmat';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('elixirstorespa@gmail.com', 'Elixir Perfumería');
        $mail->addAddress('elixirstorespa@gmail.com');
        $mail->addReplyTo($pedido['correo_cliente'], $pedido['nombre_cliente']);

        $mail->isHTML(true);
        $mail->Subject = "Nuevo pago pendiente - Pedido #$pedido_id";
        $mail->Body    = "
            <h2>Nuevo pago pendiente de aprobación</h2>
            <p><strong>Pedido:</strong> #$pedido_id</p>
            <p><strong>Cliente:</strong> {$pedido['nombre_cliente']}</p>
            <p><strong>Correo:</strong> {$pedido['correo_cliente']}</p>
            <p><strong>Monto:</strong> \$" . number_format($pedido['total'], 0, ',', '.') . "</p>
            <br>
            <a href=\"http://{$_SERVER['HTTP_HOST']}/Elixir/privado/panel_control.php?seccion=pedidos\" style=\"background:#27ae60;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;\">Ir al Panel</a>
        ";

        $mail->send();
        $error_msg = '';
    } catch (Exception $e) {
        $error_msg = $mail->ErrorInfo;
    }

    if ($error_msg) {
        die("Error al enviar el correo: " . htmlspecialchars($error_msg));
    }

    header("Location: pago.php?pedido_id=$pedido_id&pendiente=1");
    exit;
}

$exito = isset($_GET['exito']);
$pendiente = isset($_GET['pendiente']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago - Elixir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f9; color: #333; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { font-size: 28px; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .pago-box { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .pago-box h2 { margin-bottom: 10px; color: #1a1a2e; }
        .total-pedido { font-size: 32px; font-weight: bold; color: #27ae60; text-align: center; padding: 20px 0; }
        .info-pedido { text-align: center; color: #666; font-size: 14px; margin-bottom: 20px; }
        .cuenta-box { background: linear-gradient(135deg, #f8f9fa, #eef1f5); border: 2px dashed #0f3460; border-radius: 12px; padding: 24px; margin: 20px 0; }
        .cuenta-box table { width: 100%; font-size: 15px; }
        .cuenta-box td { padding: 8px 6px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .cuenta-box td:first-child { font-weight: 600; color: #555; white-space: nowrap; width: 100px; }
        .cuenta-box td:last-child { color: #1a1a2e; }
        .btn-pagar { background: #27ae60; color: white; border: none; padding: 14px; border-radius: 8px; font-size: 18px; cursor: pointer; width: 100%; }
        .btn-pagar:hover { background: #219653; }
        .btn-volver { display: inline-block; background: #0f3460; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-size: 16px; margin-top: 10px; }
        .exito-box, .pendiente-box { text-align: center; padding: 30px; }
        .exito-box h2 { color: #27ae60; font-size: 24px; }
        .pendiente-box h2 { color: #f39c12; font-size: 24px; }
        .exito-box p, .pendiente-box p { margin: 15px 0; font-size: 16px; color: #666; }
        .instruccion { text-align: center; font-size: 15px; color: #666; margin-bottom: 10px; }
        @media (max-width: 768px) {
            .header h1 { font-size: 22px; }
            .container { padding: 20px 12px; }
            .pago-box { padding: 20px 16px; }
            .pago-box h2 { font-size: 20px; }
            .total-pedido { font-size: 26px; }
            .cuenta-box { padding: 16px; }
            .cuenta-box td { font-size: 14px; }
            .cuenta-box td:first-child { width: 80px; }
            .cuenta-box td:last-child { font-size: 14px; }
            .btn-pagar { font-size: 16px; padding: 12px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PSUSKING</h1>
        <p>Pasarela de Pago</p>
    </div>
    <div class="container">
        <?php if ($exito): ?>
            <div class="pago-box">
                <div class="exito-box">
                    <h2>✅ Pago Aprobado</h2>
                    <p>Tu pedido #<?php echo $pedido_id; ?> ha sido pagado con éxito.</p>
                    <p>Recibirás la confirmación en tu correo electrónico.</p>
                    <a href="index.php" class="btn-volver">Volver a la tienda</a>
                </div>
            </div>
        <?php elseif ($pendiente): ?>
            <div class="pago-box">
                <div class="pendiente-box">
                    <h2>⏳ Pago en revisión</h2>
                    <p>Tu pedido #<?php echo $pedido_id; ?> está pendiente de confirmación.</p>
                    <p>Te enviaremos un correo una vez que el pago sea verificado.</p>
                    <a href="index.php" class="btn-volver">Volver a la tienda</a>
                </div>
            </div>
        <?php elseif ($pedido['estado_pago'] === 'Pendiente'): ?>
            <div class="pago-box">
                <div class="pendiente-box">
                    <h2>⏳ Pago en revisión</h2>
                    <p>Tu pedido #<?php echo $pedido_id; ?> ya fue reportado y está pendiente de confirmación.</p>
                    <a href="index.php" class="btn-volver">Volver a la tienda</a>
                </div>
            </div>
        <?php elseif ($pedido['estado_pago'] === 'Aprobado'): ?>
            <div class="pago-box">
                <div class="exito-box">
                    <h2>✅ Pago Aprobado</h2>
                    <p>Tu pedido #<?php echo $pedido_id; ?> ya fue confirmado.</p>
                    <a href="index.php" class="btn-volver">Volver a la tienda</a>
                </div>
            </div>
        <?php else: ?>
            <div class="pago-box">
                <h2 style="margin-bottom:5px;">Transferencia Bancaria</h2>
                <p class="info-pedido">Pedido #<?php echo $pedido_id; ?></p>
                <div class="total-pedido">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></div>
                <p class="instruccion">Realiza la transferencia a la siguiente cuenta:</p>
                <div class="cuenta-box">
                    <table>
                        <tr><td>Banco</td><td><strong><?php echo htmlspecialchars($cuenta['banco']); ?></strong></td></tr>
                        <tr><td>Tipo</td><td><?php echo htmlspecialchars($cuenta['tipo_cuenta']); ?></td></tr>
                        <tr><td>Número</td><td style="font-size:18px;letter-spacing:2px;font-weight:bold;color:#1a1a2e;"><?php echo htmlspecialchars($cuenta['numero_cuenta']); ?></td></tr>
                        <tr><td>Titular</td><td><?php echo htmlspecialchars($cuenta['titular']); ?></td></tr>
                        <tr><td>RUT</td><td><?php echo htmlspecialchars($cuenta['rut']); ?></td></tr>
                    </table>
                </div>
                <form method="POST">
                    <button type="submit" name="transferencia" class="btn-pagar" onclick="return confirm('¿Confirmas que ya realizaste la transferencia por $<?php echo number_format($pedido['total'], 0, ',', '.'); ?>?')">Ya transferí</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

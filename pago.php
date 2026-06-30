<?php
session_start();
require_once __DIR__ . '/conexion.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $banco = $_POST['banco'] ?? '';
    if (!empty($banco)) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado_pago = 'Aprobado', token_pago = ? WHERE id = ?");
        $token = 'SIM_' . bin2hex(random_bytes(8));
        $stmt->execute([$token, $pedido_id]);

        $stmt_p = $pdo->prepare("INSERT INTO payments (order_id, gateway_payment_id, amount, status, payment_method) VALUES (?, ?, ?, 'approved', ?)");
        $stmt_p->execute([$pedido_id, $token, $pedido['total'], $banco]);

        header("Location: pago.php?pedido_id=$pedido_id&exito=1");
        exit;
    }
}
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
        .banco-opcion { display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid #eee; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: border-color 0.2s; }
        .banco-opcion:hover, .banco-opcion input:checked + .banco-info { border-color: #0f3460; }
        .banco-opcion input[type="radio"] { accent-color: #0f3460; }
        .banco-info h4 { font-size: 16px; }
        .banco-info p { font-size: 13px; color: #666; }
        .btn-pagar { background: #0f3460; color: white; border: none; padding: 14px; border-radius: 8px; font-size: 18px; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn-pagar:hover { background: #1a4a7a; }
        .exito-box { text-align: center; padding: 30px; }
        .exito-box h2 { color: #27ae60; font-size: 24px; }
        .exito-box p { margin: 15px 0; font-size: 16px; color: #666; }
        .exito-box .btn-volver { display: inline-block; background: #27ae60; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-size: 16px; margin-top: 10px; }
        .info-pedido { text-align: center; color: #666; font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PSUSKING</h1>
        <p>Pasarela de Pago</p>
    </div>
    <div class="container">
        <?php if (isset($_GET['exito'])): ?>
            <div class="pago-box">
                <div class="exito-box">
                    <h2>✅ Pago Aprobado</h2>
                    <p>Tu pedido #<?php echo $pedido_id; ?> ha sido pagado con éxito.</p>
                    <p>Recibirás la confirmación en tu correo electrónico.</p>
                    <a href="index.php" class="btn-volver">Volver a la tienda</a>
                </div>
            </div>
        <?php else: ?>
            <div class="pago-box">
                <h2>Selecciona tu banco</h2>
                <p class="info-pedido">Pedido #<?php echo $pedido_id; ?></p>
                <div class="total-pedido">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></div>
                <form method="POST">
                    <label class="banco-opcion">
                        <input type="radio" name="banco" value="banco_estado" required>
                        <div class="banco-info">
                            <h4>Banco Estado</h4>
                            <p>Cuenta RUT / Transferencia electrónica</p>
                        </div>
                    </label>
                    <label class="banco-opcion">
                        <input type="radio" name="banco" value="banco_chile">
                        <div class="banco-info">
                            <h4>Banco de Chile</h4>
                            <p>Transferencia / Redcompra</p>
                        </div>
                    </label>
                    <label class="banco-opcion">
                        <input type="radio" name="banco" value="santander">
                        <div class="banco-info">
                            <h4>Santander</h4>
                            <p>Transferencia / Tarjetas</p>
                        </div>
                    </label>
                    <label class="banco-opcion">
                        <input type="radio" name="banco" value="webpay">
                        <div class="banco-info">
                            <h4>Webpay Plus</h4>
                            <p>Tarjetas de crédito / débito (Transbank)</p>
                        </div>
                    </label>
                    <button type="submit" class="btn-pagar">Pagar ahora</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

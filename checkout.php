<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/csrf_helper.php';

if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header("Location: index.php");
    exit;
}

$ids = implode(',', array_map('intval', array_keys($_SESSION['carrito'])));
$productos = $pdo->query("SELECT * FROM productos WHERE id IN ($ids)")->fetchAll();
$productos_map = [];
foreach ($productos as $p) {
    $productos_map[$p['id']] = $p;
}

$total = 0;
foreach ($_SESSION['carrito'] as $id => $cant) {
    if (isset($productos_map[$id])) {
        $total += $productos_map[$id]['precio'] * $cant;
    }
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (empty($nombre) || empty($correo)) {
        $mensaje = 'Completa todos los campos obligatorios.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO pedidos (nombre_cliente, correo_cliente, total, estado_pago) VALUES (?, ?, ?, 'Pendiente')");
            $stmt->execute([$nombre, $correo, $total]);
            $pedido_id = $pdo->lastInsertId();

            $stmt_detalle = $pdo->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['carrito'] as $id => $cant) {
                if (isset($productos_map[$id])) {
                    $stmt_detalle->execute([$pedido_id, $id, $cant, $productos_map[$id]['precio']]);
                }
            }

            $pdo->commit();
            $_SESSION['carrito'] = [];
            header("Location: pago.php?pedido_id=" . $pedido_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = 'Error al procesar el pedido. Intenta de nuevo.';
        }
    }
}

$exito = $_GET['exito'] ?? 0;
$pedido_id = $_SESSION['pedido_exitoso'] ?? 0;
if ($exito) {
    unset($_SESSION['pedido_exitoso']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Psusking</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f9; color: #333; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { font-size: 28px; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .pedido-exitoso { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 30px; border-radius: 12px; text-align: center; }
        .pedido-exitoso h2 { font-size: 24px; margin-bottom: 10px; }
        .pedido-exitoso p { font-size: 16px; }
        .checkout-form { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .checkout-form h2 { margin-bottom: 20px; color: #1a1a2e; }
        .grupo { margin-bottom: 20px; }
        .grupo label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 5px; color: #555; }
        .grupo input, .grupo textarea { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; }
        .grupo input:focus, .grupo textarea:focus { outline: none; border-color: #0f3460; }
        .resumen { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .resumen h3 { margin-bottom: 15px; color: #1a1a2e; }
        .resumen-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .resumen-total { display: flex; justify-content: space-between; padding: 12px 0; font-size: 20px; font-weight: bold; color: #1a1a2e; }
        .btn-pagar { background: #27ae60; color: white; border: none; padding: 14px; border-radius: 8px; font-size: 18px; cursor: pointer; width: 100%; }
        .btn-pagar:hover { background: #219653; }
        .btn-volver { display: inline-block; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PSUSKING</h1>
        <p>Finalizar Compra</p>
    </div>
    <div class="container">
        <?php if ($exito): ?>
            <div class="pedido-exitoso">
                <h2>✅ Pedido Registrado</h2>
                <p>Tu pedido #<?php echo $pedido_id; ?> ha sido recibido. Te contactaremos a la brevedad.</p>
                <p style="margin-top:15px;"><a href="index.php" style="color:#155724;font-weight:bold;">Volver a la tienda</a></p>
            </div>
        <?php else: ?>
            <div class="checkout-form">
                <h2>📋 Datos de Envío</h2>
                <?php if ($mensaje): ?>
                    <div class="error"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <?php echo csrf_campo(); ?>
                    <div class="grupo">
                        <label for="nombre">Nombre completo *</label>
                        <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                    </div>
                    <div class="grupo">
                        <label for="correo">Correo electrónico *</label>
                        <input type="email" id="correo" name="correo" required value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">
                    </div>
                    <div class="grupo">
                        <label for="direccion">Dirección</label>
                        <textarea id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                    </div>

                    <div class="resumen">
                        <h3>🛒 Resumen del pedido</h3>
                        <?php foreach ($_SESSION['carrito'] as $id => $cant): ?>
                            <?php if (isset($productos_map[$id])): $p = $productos_map[$id]; ?>
                                <div class="resumen-item">
                                    <span><?php echo htmlspecialchars($p['nombre']); ?> × <?php echo $cant; ?></span>
                                    <span>$<?php echo number_format($p['precio'] * $cant, 0, ',', '.'); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <div class="resumen-total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-pagar">Confirmar Pedido</button>
                </form>
                <a href="index.php" class="btn-volver">← Seguir comprando</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

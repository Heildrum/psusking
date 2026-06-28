<?php
session_start();
require_once 'conexion.php';

// Recogemos el token que envía el banco
$token = $_POST['token_ws'] ?? $_GET['token_ws'] ?? null;

if (!$token) {
    die("Acceso denegado o transacción anulada por el usuario.");
}

// Credenciales de prueba
$codigo_comercio = "597055555532";
$api_key = "579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C";

// Hacemos un PUT a Transbank para confirmar y "capturar" el dinero
$url_confirmar = "https://webpay3gint.transbank.cl/rsenv_webpayplus/v1.0/transactions/" . $token;

$ch = curl_init($url_confirmar);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Tbk-Api-Key-Id: $codigo_comercio",
    "Tbk-Api-Key-Secret: $api_key",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$resultado = json_decode($response, true);

// Verificamos si la respuesta del banco dice "Aprobado" (response_code = 0)
if (isset($resultado['response_code']) && $resultado['response_code'] === 0) {
    
    try {
        $pdo->beginTransaction(); // Iniciamos transacción segura en MySQL
        
        // 1. Insertar la venta general en la tabla 'pedidos'
        // (Nota: En un flujo real, los datos del cliente vendrían de un formulario previo al pago)
        $stmt = $pdo->prepare("INSERT INTO pedidos (nombre_cliente, correo_cliente, total, estado_pago, token_pago) VALUES (?, ?, ?, 'Aprobado', ?)");
        $stmt->execute(['Cliente Webpay', 'cliente@correo.cl', $resultado['amount'], $token]);
        $pedido_id = $pdo->lastInsertId();
        
        // 2. Insertar el desglose en 'detalle_pedidos' y descontar stock
        foreach ($_SESSION['carrito'] as $producto_id => $cantidad) {
            
            $stmt_p = $pdo->prepare("SELECT precio, stock FROM productos WHERE id = ?");
            $stmt_p->execute([$producto_id]);
            $producto = $stmt_p->fetch();
            
            if ($producto) {
                // Registramos el ítem comprado
                $stmt_d = $pdo->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt_d->execute([$pedido_id, $producto_id, $cantidad, $producto['precio']]);
                
                // Restamos del inventario en MySQL
                $stmt_s = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt_s->execute([$cantidad, $producto_id]);
            }
        }
        
        $pdo->commit(); // Confirmamos todos los cambios en MySQL a la vez
        
        // Vaciamos el carro de la memoria
        unset($_SESSION['carrito']);
        
        $status_compra = "SUCCESS";
        
    } catch (Exception $e) {
        $pdo->rollBack(); // Si algo falló guardando, cancelamos todo para no descuadrar cajas
        die("Error crítico al registrar la compra en el sistema: " . $e->getMessage());
    }
} else {
    $status_compra = "REJECTED";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado del Pago</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; text-align: center; padding-top: 50px; }
        .box { background: white; padding: 40px; display: inline-block; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 450px; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .btn { display: inline-block; padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <?php if ($status_compra === "SUCCESS"): ?>
            <h1 class="success">¡Pago Aprobado!</h1>
            <p>Tu orden de compra <strong><?php echo $resultado['buy_order']; ?></strong> ha sido procesada con éxito.</p>
            <p>Monto Pagado: $<?php echo number_format($resultado['amount'], 0, ',', '.'); ?> CLP</p>
            <p>El cargo aparecerá en tu cartola bancaria. Pronto prepararemos tu despacho.</p>
        <?php else: ?>
            <h1 class="error">Pago Rechazado</h1>
            <p>La transacción fue rechazada por el banco emisor o cancelada antes de terminar.</p>
            <p>No se ha realizado ningún cobro a tu tarjeta.</p>
        <?php endif; ?>
        
        <a href="index.php" class="btn">Volver a la Tienda</a>
    </div>
</body>
</html>
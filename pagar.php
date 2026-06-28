<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['carrito'])) {
    die("El carrito está vacío.");
}

// 1. CALCULAR EL TOTAL REAL CONSULTANDO EN MYSQL
$monto_total = 0;
foreach ($_SESSION['carrito'] as $producto_id => $cantidad) {
    $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $precio = $stmt->fetchColumn();
    if ($precio) {
        $monto_total += ($precio * $cantidad);
    }
}

// 2. PARÁMETROS REQUERIDOS POR TRANSBANK (Datos de Prueba Oficiales)
$codigo_comercio = "597055555532"; // Código de comercio oficial para pruebas
$api_key = "579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C"; // Llave pública de pruebas

$orden_compra = "ORD-" . rand(10000, 99999);
$session_id = session_id();

// URL a la que Transbank devolverá al cliente tras digitar sus claves bancarias
$url_retorno = "http://" . $_SERVER['HTTP_HOST'] . "/tu-proyecto/confirmar_pago.php";

// 3. COMUNICACIÓN MEDIANTE cURL (Petición HTTP POST a Transbank)
$url_transbank = "https://webpay3gint.transbank.cl/rsenv_webpayplus/v1.0/transactions";

$payload = json_encode([
    "buy_order" => $orden_compra,
    "session_id" => $session_id,
    "amount" => (int)$monto_total,
    "return_url" => $url_retorno
]);

$ch = curl_init($url_transbank);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Tbk-Api-Key-Id: $codigo_comercio",
    "Tbk-Api-Key-Secret: $api_key",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// 4. REDIRIGIR AUTOMÁTICAMENTE AL CLIENTE AL PORTAL DE WEBPAY
if (isset($data['token']) && isset($data['url'])) {
    $token = $data['token'];
    $url_banco = $data['url'];
    
    // Guardamos el token temporalmente en la sesión para validarlo a la vuelta
    $_SESSION['token_webpay'] = $token;
    $_SESSION['pedido_total'] = $monto_total;
} else {
    die("Error al conectar con Webpay: " . print_r($response, true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head><title>Conectando con el Banco...</title></head>
<body onload="document.forms['webpay_form'].submit();">
    <p style="text-align:center; margin-top:50px; font-family:sans-serif;">Conectando de forma segura con Webpay Plus, por favor espere...</p>
    
    <!-- Este formulario se envía solo inmediatamente mediante el JS del body -->
    <form name="webpay_form" action="<?php echo $url_banco; ?>" method="POST">
        <input type="hidden" name="token_ws" value="<?php echo $token; ?>" />
    </form>
</body>
</html>
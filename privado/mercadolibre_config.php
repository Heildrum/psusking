<?php
session_start();
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}
require_once __DIR__ . '/../conexion.php';

$mensaje = '';

$credenciales = $pdo->query("SELECT * FROM Pasarela ORDER BY id DESC LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $client_id = trim($_POST['client_id']);
    $client_secret = trim($_POST['client_secret']);
    $access_token = trim($_POST['access_token']);
    $refresh_token = trim($_POST['refresh_token']);
    $expires_at = $_POST['expires_at'] ?? date('Y-m-d H:i:s', time() + 86400);

    if ($credenciales) {
        $stmt = $pdo->prepare("UPDATE Pasarela SET client_id=?, client_secret=?, access_token=?, refresh_token=?, expires_at=? WHERE id=?");
        $stmt->execute([$client_id, $client_secret, $access_token, $refresh_token, $expires_at, $credenciales['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO Pasarela (client_id, client_secret, access_token, refresh_token, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$client_id, $client_secret, $access_token, $refresh_token, $expires_at]);
    }
    $mensaje = 'Credenciales guardadas correctamente.';
    $credenciales = $pdo->query("SELECT * FROM Pasarela ORDER BY id DESC LIMIT 1")->fetch();
}

$pagos = $pdo->query("SELECT p.*, ped.nombre_cliente, ped.total as pedido_total FROM payments p JOIN pedidos ped ON p.order_id = ped.id ORDER BY p.created_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mercado Libre - Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f9; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; min-height: 100vh; }
        .sidebar h2 { font-size: 18px; margin-bottom: 20px; }
        .sidebar a { display: block; color: #bdc3c7; padding: 10px; border-radius: 4px; text-decoration: none; margin-bottom: 5px; }
        .sidebar a:hover { background: #34495e; color: white; }
        .main { flex: 1; padding: 30px; max-width: 900px; }
        .mensaje { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .card h3 { margin-bottom: 15px; color: #2c3e50; }
        .grupo { margin-bottom: 15px; }
        .grupo label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #555; }
        .grupo input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .grupo input:focus { border-color: #0f3460; outline: none; }
        button { background: #0f3460; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 15px; }
        button:hover { background: #1a4a7a; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #2c3e50; color: white; }
        .status-approved { color: #27ae60; font-weight: bold; }
        .status-rejected { color: #e74c3c; font-weight: bold; }
        .status-pending { color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>⚙️ Psusking Panel</h2>
        <a href="panel_control.php">← Volver al Panel</a>
        <a href="mercadolibre_config.php" style="color:white;background:#34495e;">🔑 Mercado Libre</a>
        <a href="cerrar_sesion.php" style="margin-top:30px;color:#e74c3c;">Cerrar Sesión</a>
    </div>
    <div class="main">
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>🔑 Credenciales de Mercado Libre / Mercado Pago</h3>
            <form method="POST">
                <div class="grupo">
                    <label>Client ID</label>
                    <input type="text" name="client_id" value="<?php echo htmlspecialchars($credenciales['client_id'] ?? ''); ?>">
                </div>
                <div class="grupo">
                    <label>Client Secret</label>
                    <input type="text" name="client_secret" value="<?php echo htmlspecialchars($credenciales['client_secret'] ?? ''); ?>">
                </div>
                <div class="grupo">
                    <label>Access Token</label>
                    <input type="text" name="access_token" value="<?php echo htmlspecialchars($credenciales['access_token'] ?? ''); ?>">
                </div>
                <div class="grupo">
                    <label>Refresh Token</label>
                    <input type="text" name="refresh_token" value="<?php echo htmlspecialchars($credenciales['refresh_token'] ?? ''); ?>">
                </div>
                <div class="grupo">
                    <label>Expira en</label>
                    <input type="text" name="expires_at" value="<?php echo htmlspecialchars($credenciales['expires_at'] ?? date('Y-m-d H:i:s', time() + 86400)); ?>">
                </div>
                <button type="submit" name="guardar">Guardar Credenciales</button>
            </form>
        </div>

        <div class="card">
            <h3>💳 Últimos Pagos Registrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td>#<?php echo $p['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($p['nombre_cliente']); ?></td>
                        <td>$<?php echo number_format($p['amount'], 0, ',', '.'); ?></td>
                        <td><?php echo $p['payment_method']; ?></td>
                        <td class="status-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></td>
                        <td><?php echo $p['created_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pagos)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#999;">Sin pagos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

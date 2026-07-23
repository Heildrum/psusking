<?php
session_start();
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../csrf_helper.php';

$seccion = $_GET['seccion'] ?? 'dashboard';

if ($seccion === 'cuenta_bancaria' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $stmt = $pdo->prepare("UPDATE cuenta_bancaria SET banco=?, tipo_cuenta=?, numero_cuenta=?, titular=?, rut=? WHERE id=1");
    $stmt->execute([
        $_POST['banco'],
        $_POST['tipo_cuenta'],
        $_POST['numero_cuenta'],
        $_POST['titular'],
        $_POST['rut'],
    ]);
    header("Location: ?seccion=cuenta_bancaria&guardado=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Elixir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f9; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; }
        .sidebar h2 { margin-bottom: 20px; font-size: 18px; }
        .sidebar a { display: block; color: #bdc3c7; text-decoration: none; padding: 10px; border-radius: 4px; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; }
        .main { flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; }
        .btn-cerrar { background: #e74c3c; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .btn-cerrar:hover { background: #c0392b; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { display: inline-block; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; margin: 0 2px; }
        .btn-editar { background: #f39c12; color: white; }
        .btn-eliminar { background: #e74c3c; color: white; }
        .btn-nuevo { background: #27ae60; color: white; padding: 10px 20px; margin-bottom: 20px; display: inline-block; }
        .mensaje { padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        img.thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .grupo { margin-bottom: 18px; }
        .grupo label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 5px; color: #555; }
        .grupo input { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; }
        .grupo input:focus { outline: none; border-color: #0f3460; }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; padding: 14px; display: flex; flex-wrap: wrap; gap: 6px; }
            .sidebar h2 { width: 100%; margin-bottom: 10px; font-size: 16px; }
            .sidebar a { display: inline-block; padding: 8px 12px; margin-bottom: 0; font-size: 13px; }
            .main { padding: 16px; }
            .header h1 { font-size: 20px; }
            .header { flex-direction: column; gap: 10px; align-items: stretch; text-align: center; }
            table { font-size: 13px; }
            th, td { padding: 8px 6px; }
            img.thumb { width: 40px; height: 40px; }
            .btn { font-size: 12px; padding: 5px 8px; }
            [class*="btn-nuevo"] { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🐱 Elixir Panel</h2>
        <a href="?seccion=dashboard" class="<?php echo $seccion === 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a>
        <a href="?seccion=productos" class="<?php echo $seccion === 'productos' ? 'active' : ''; ?>">🧴 Productos</a>
        <a href="?seccion=pedidos" class="<?php echo $seccion === 'pedidos' ? 'active' : ''; ?>">📦 Pedidos</a>
        <a href="?seccion=crear" class="<?php echo $seccion === 'crear' ? 'active' : ''; ?>">➕ Nuevo Producto</a>
        <a href="importar_productos.php">📥 Importar Excel</a>
        <a href="?seccion=cuenta_bancaria" class="<?php echo $seccion === 'cuenta_bancaria' ? 'active' : ''; ?>">🏦 Cuenta Bancaria</a>
        <a href="mercadolibre_config.php">🔑 Mercado Pago</a>
        <a href="cerrar_sesion.php" style="margin-top:30px; color:#e74c3c;">🚪 Cerrar Sesión</a>
    </div>
    <div class="main">
        <div class="header">
            <h1><?php
                $titulos = [
                    'dashboard' => 'Dashboard',
                    'productos' => 'Gestión de Productos',
                    'pedidos' => 'Pedidos Recibidos',
                    'crear' => 'Nuevo Producto',
                    'editar' => 'Editar Producto',
                    'cuenta_bancaria' => 'Cuenta Bancaria',
                ];
                echo $titulos[$seccion] ?? 'Panel de Control';
            ?></h1>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="mensaje <?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo $_GET['status'] === 'success' ? 'Operación realizada con éxito.' : 'Error al realizar la operación.'; ?>
            </div>
        <?php endif; ?>

        <?php if ($seccion === 'dashboard'): ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                <?php
                $totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
                $totalPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
                $totalStock = $pdo->query("SELECT SUM(stock) FROM productos")->fetchColumn() ?: 0;
                ?>
                <div style="background:white; padding:25px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="color:#2c3e50;">🧴 Productos</h3>
                    <p style="font-size:32px; font-weight:bold; color:#27ae60;"><?php echo $totalProductos; ?></p>
                </div>
                <div style="background:white; padding:25px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="color:#2c3e50;">📦 Pedidos</h3>
                    <p style="font-size:32px; font-weight:bold; color:#2980b9;"><?php echo $totalPedidos; ?></p>
                </div>
                <div style="background:white; padding:25px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="color:#2c3e50;">📦 Stock Total</h3>
                    <p style="font-size:32px; font-weight:bold; color:#8e44ad;"><?php echo $totalStock; ?> uds.</p>
                </div>
            </div>

        <?php elseif ($seccion === 'productos'): ?>
            <a href="?seccion=crear" class="btn btn-nuevo">➕ Nuevo Producto</a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $productos = $pdo->query("SELECT * FROM productos ORDER BY id DESC")->fetchAll();
                    foreach ($productos as $p):
                    ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td>
                            <?php if ($p['imagen']): ?>
                                <img src="imagenes/<?php echo $p['imagen']; ?>" class="thumb" alt="">
                            <?php else: ?>
                                <span style="color:#999;">Sin foto</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td>$<?php echo number_format($p['precio'], 2); ?></td>
                        <td><?php echo $p['stock']; ?></td>
                        <td>
                            <a href="?seccion=editar&id=<?php echo $p['id']; ?>" class="btn btn-editar">Editar</a>
                            <a href="eliminar_producto.php?id=<?php echo $p['id']; ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar este producto?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($seccion === 'pedidos'): ?>
            <?php
            if (isset($_GET['aprobar'])) {
                $token_get = $_GET['csrf'] ?? '';
                if (empty($token_get) || !hash_equals($_SESSION['csrf_token'] ?? '', $token_get)) {
                    echo '<div class="mensaje error">Error de seguridad.</div>';
                } else {
                $id_aprobar = filter_input(INPUT_GET, 'aprobar', FILTER_VALIDATE_INT);
                if ($id_aprobar) {
                    $stmt = $pdo->prepare("UPDATE pedidos SET estado_pago = 'Aprobado', token_pago = ? WHERE id = ? AND estado_pago = 'Pendiente'");
                    $token = 'CONF_' . bin2hex(random_bytes(8));
                    $stmt->execute([$token, $id_aprobar]);
                    echo '<div class="mensaje success">Pedido #'.$id_aprobar.' aprobado.</div>';
                }
                }
            }
            ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Correo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll();
                    foreach ($pedidos as $ped):
                    ?>
                    <tr>
                        <td><?php echo $ped['id']; ?></td>
                        <td><?php echo htmlspecialchars($ped['nombre_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($ped['correo_cliente']); ?></td>
                        <td>$<?php echo number_format($ped['total'], 2); ?></td>
                        <td><?php echo $ped['estado_pago']; ?></td>
                        <td><?php echo $ped['fecha']; ?></td>
                        <td>
                            <?php if ($ped['estado_pago'] === 'Pendiente'): ?>
                                <a href="?seccion=pedidos&aprobar=<?php echo $ped['id']; ?>&csrf=<?php echo csrf_generar_token(); ?>" class="btn btn-editar" onclick="return confirm('¿Confirmar pago de pedido #<?php echo $ped['id']; ?>?')">Aprobar</a>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($seccion === 'crear'): ?>
            <?php $producto_a_editar = null; ?>
            <?php include 'formulario_creacion.php'; ?>

        <?php elseif ($seccion === 'editar'): ?>
            <?php
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            $producto_a_editar = $stmt->fetch();
            if (!$producto_a_editar):
            ?>
                <div class="mensaje error">Producto no encontrado.</div>
            <?php else: ?>
                <?php include 'formulario_creacion.php'; ?>
            <?php endif; ?>

        <?php elseif ($seccion === 'cuenta_bancaria'): ?>
            <?php
            $cuenta = $pdo->query("SELECT * FROM cuenta_bancaria WHERE id = 1")->fetch();
            if (isset($_GET['guardado'])): ?>
                <div class="mensaje success">✅ Cuenta bancaria guardada correctamente.</div>
            <?php endif; ?>
            <div style="background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.08);padding:30px;max-width:550px;">
                <h3 style="color:#2c3e50;margin-bottom:5px;font-size:20px;">🏦 Cuenta Bancaria</h3>
                <p style="color:#888;font-size:14px;margin-bottom:25px;">Estos datos se mostrarán en la pantalla de pago para que los clientes realicen la transferencia.</p>
                <form method="POST">
                    <?php echo csrf_campo(); ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div style="grid-column:span 2;">
                            <label style="display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:5px;">Banco</label>
                            <input type="text" name="banco" value="<?php echo htmlspecialchars($cuenta['banco']); ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:5px;">Tipo de Cuenta</label>
                            <input type="text" name="tipo_cuenta" value="<?php echo htmlspecialchars($cuenta['tipo_cuenta']); ?>" placeholder="Corriente / RUT" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:5px;">Número de Cuenta</label>
                            <input type="text" name="numero_cuenta" value="<?php echo htmlspecialchars($cuenta['numero_cuenta']); ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:5px;">Titular</label>
                            <input type="text" name="titular" value="<?php echo htmlspecialchars($cuenta['titular']); ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:5px;">RUT</label>
                            <input type="text" name="rut" value="<?php echo htmlspecialchars($cuenta['rut']); ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
                        </div>
                    </div>
                    <button type="submit" name="guardar_cuenta" style="margin-top:20px;background:#0f3460;color:#fff;border:none;padding:12px 30px;border-radius:6px;font-size:15px;cursor:pointer;">Guardar</button>
                </form>
                <p style="margin-top:20px;text-align:center;"><a href="?seccion=dashboard" style="color:#888;text-decoration:none;font-size:14px;">← Volver al Dashboard</a></p>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>

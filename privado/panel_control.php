<?php
session_start();
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../csrf_helper.php';

$seccion = $_GET['seccion'] ?? 'dashboard';
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
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Correo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
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
        <?php endif; ?>
    </div>
</body>
</html>

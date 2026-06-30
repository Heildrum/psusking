<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$productos = $pdo->query("SELECT * FROM productos ORDER BY creado_en DESC")->fetchAll();
$total_carrito = 0;
$carrito_ids = [];
if (!empty($_SESSION['carrito'])) {
    $carrito_ids = array_keys($_SESSION['carrito']);
    $total_carrito = array_sum($_SESSION['carrito']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elixir - Perfumería</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d0d0d; color: #e0e0e0; font-size: 17px; }
        .header { background: #0a0a0a; color: white; padding: 50px 20px 30px; text-align: center; border-bottom: 1px solid #222; }
        .header-logo { max-width: 220px; max-height: 220px; width: auto; height: auto; object-fit: contain; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto; }
        .header h1 { font-size: 48px; letter-spacing: 4px; color: #fff; }
        .header p { color: #888; margin-top: 8px; font-size: 16px; }
        .nav { background: #111; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; border-bottom: 1px solid #222; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; color: #ccc; text-decoration: none; font-size: 20px; font-style: italic; letter-spacing: 1px; }
        .nav-links a:hover { color: white; }
        .cart-btn { background: none; border: none; color: #ccc; cursor: pointer; font-size: 17px; position: relative; padding: 6px 14px; border-radius: 4px; }
        .cart-btn:hover { background: rgba(255,255,255,0.1); color: white; }
        .cart-badge { background: #e74c3c; color: white; border-radius: 50%; padding: 2px 7px; font-size: 12px; position: absolute; top: -6px; right: -6px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .card { background: #1a1a1a; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.4); transition: transform 0.2s, box-shadow 0.2s; border: 1px solid #2a2a2a; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.6); }
        .card-img { width: 100%; height: 260px; object-fit: cover; display: block; background: #111; }
        .card-img-placeholder { width: 100%; height: 260px; background: #111; display: flex; align-items: center; justify-content: center; color: #555; font-size: 15px; }
        .card-body { padding: 20px; }
        .card-body h3 { font-size: 20px; margin-bottom: 8px; color: #fff; }
        .card-body .descripcion { font-size: 15px; color: #aaa; line-height: 1.6; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #141414; border-top: 1px solid #2a2a2a; }
        .precio { font-size: 24px; font-weight: bold; color: #fff; }
        .stock { font-size: 14px; color: #27ae60; }
        .stock.agotado { color: #e74c3c; }
        .btn-carrito { background: #222; color: #fff; border: 1px solid #444; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        .btn-carrito:hover { background: #333; }
        .btn-carrito.agotado { background: #1a1a1a; color: #555; border-color: #2a2a2a; cursor: not-allowed; }
        .sin-productos { text-align: center; padding: 80px 20px; color: #666; }
        .sin-productos h2 { font-size: 26px; margin-bottom: 10px; }
        .footer { text-align: center; padding: 30px; color: #555; font-size: 14px; border-top: 1px solid #222; margin-top: 40px; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1a1a1a; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.5); border: 1px solid #333; }
        .modal h2 { margin-bottom: 20px; color: #fff; font-size: 22px; }
        .modal-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #2a2a2a; }
        .modal-item-info { flex: 1; }
        .modal-item-info h4 { font-size: 17px; color: #fff; }
        .modal-item-info p { font-size: 14px; color: #888; }
        .modal-item-acciones { display: flex; align-items: center; gap: 10px; }
        .modal-item-acciones span { color: #fff; font-size: 16px; }
        .modal-item-acciones a { color: #e74c3c; text-decoration: none; font-size: 14px; }
        .modal-total { text-align: right; margin-top: 15px; font-size: 22px; font-weight: bold; color: #fff; }
        .modal-close { background: #2a2a2a; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; margin-top: 15px; width: 100%; font-size: 16px; }
        .modal-close:hover { background: #3a3a3a; }
        .btn-vaciar { color: #e74c3c; text-decoration: none; font-size: 14px; float: right; margin-top: 5px; }
        .cart-msg { display: none; position: fixed; bottom: 20px; right: 20px; background: #27ae60; color: white; padding: 14px 26px; border-radius: 8px; font-size: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); z-index: 999; animation: fadeInOut 2s; }
        @keyframes fadeInOut { 0%{opacity:0;transform:translateY(10px)} 15%{opacity:1;transform:translateY(0)} 85%{opacity:1} 100%{opacity:0;transform:translateY(-10px)} }
    </style>
</head>
<body>
    <div class="header">
        <img class="header-logo" src="privado/imagenes/logo.jpg" alt="Elixir">
        <h1>ELIXIR</h1>
        <p>Perfumería de autor — Esencias que marcan</p>
    </div>
    <div class="nav">
        <div class="nav-links">
            <a href="index.php">Inicio</a>
            <a href="#">Catálogo</a>
        </div>
        <button class="cart-btn" onclick="toggleCarrito()">
            🛒 Carrito
            <?php if ($total_carrito > 0): ?>
                <span class="cart-badge"><?php echo $total_carrito; ?></span>
            <?php endif; ?>
        </button>
    </div>

    <div class="cart-msg" id="cartMsg">✅ Producto agregado al carrito</div>

    <div class="modal-overlay" id="cartModal">
        <div class="modal">
            <h2>🛒 Tu Carrito</h2>
            <?php
            $carrito_items = [];
            $total_precio = 0;
            if (!empty($_SESSION['carrito'])) {
                $ids = implode(',', array_map('intval', $carrito_ids));
                $stmt = $pdo->query("SELECT * FROM productos WHERE id IN ($ids)");
                $prod_carrito = $stmt->fetchAll();
                foreach ($prod_carrito as $pc) {
                    $cnt = $_SESSION['carrito'][$pc['id']];
                    $subtotal = $pc['precio'] * $cnt;
                    $total_precio += $subtotal;
                    $carrito_items[] = ['p' => $pc, 'cnt' => $cnt, 'subtotal' => $subtotal];
                }
            }
            ?>
            <?php if (empty($carrito_items)): ?>
                <p style="text-align:center;padding:30px 0;color:#999;">El carrito está vacío</p>
            <?php else: ?>
                <?php foreach ($carrito_items as $item): ?>
                    <div class="modal-item">
                        <div class="modal-item-info">
                            <h4><?php echo htmlspecialchars($item['p']['nombre']); ?></h4>
                            <p><?php echo $item['cnt']; ?> x $<?php echo number_format($item['p']['precio'], 0, ',', '.'); ?></p>
                        </div>
                        <div class="modal-item-acciones">
                            <span>$<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                            <a href="carrito.php?accion=quitar&id=<?php echo $item['p']['id']; ?>">✕</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="modal-total">Total: $<?php echo number_format($total_precio, 0, ',', '.'); ?></div>
                <a href="checkout.php" style="display:block;background:#27ae60;color:white;text-align:center;padding:12px;border-radius:6px;text-decoration:none;font-size:16px;margin-top:10px;">Proceder al pago</a>
                <a href="carrito.php?accion=vaciar" class="btn-vaciar">Vaciar carrito</a>
            <?php endif; ?>
            <button class="modal-close" onclick="toggleCarrito()">Cerrar</button>
        </div>
    </div>

    <div class="container">
        <?php if (count($productos) === 0): ?>
            <div class="sin-productos">
                <h2>🕯️ Próximamente</h2>
                <p>Estamos preparando nuestra colección de perfumes. Vuelve pronto.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($productos as $p): ?>
                    <div class="card">
                        <?php if ($p['imagen'] && file_exists(__DIR__ . '/privado/imagenes/' . $p['imagen'])): ?>
                            <img class="card-img" src="privado/imagenes/<?php echo $p['imagen']; ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                        <?php else: ?>
                            <div class="card-img-placeholder">🧴 Sin imagen</div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
                            <div class="descripcion"><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></div>
                        </div>
                        <div class="card-footer">
                            <span class="precio">$<?php echo number_format($p['precio'], 0, ',', '.'); ?></span>
                            <?php if ($p['stock'] > 0): ?>
                                <a href="carrito.php?accion=agregar&id=<?php echo $p['id']; ?>" class="btn-carrito" onclick="mostrarMsg(event)">Agregar al carrito</a>
                            <?php else: ?>
                                <button class="btn-carrito agotado" disabled>Agotado</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Elixir — Todos los derechos reservados
    </div>

    <script>
        function toggleCarrito() {
            document.getElementById('cartModal').classList.toggle('active');
        }
        function mostrarMsg(e) {
            var msg = document.getElementById('cartMsg');
            msg.style.display = 'block';
            setTimeout(function() { msg.style.display = 'none'; }, 2000);
        }
        document.getElementById('cartModal').addEventListener('click', function(e) {
            if (e.target === this) toggleCarrito();
        });
    </script>
</body>
</html>

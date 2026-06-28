<?php
session_start();
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$errores = [];
$contador = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Error al subir el archivo.';
    } else {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        try {
            if ($ext === 'csv') {
                $reader = IOFactory::createReader('Csv');
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
                $reader->setSheetIndex(0);
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                $reader = IOFactory::createReader(ucfirst($ext === 'xlsx' ? 'Xlsx' : 'Xls'));
            } else {
                throw new Exception('Formato no soportado. Usa CSV, XLSX o XLS.');
            }

            $spreadsheet = $reader->load($archivo['tmp_name']);
            $hoja = $spreadsheet->getActiveSheet();
            $filas = $hoja->toArray();

            if (count($filas) < 2) {
                throw new Exception('El archivo debe tener al menos 2 filas (encabezados + datos).');
            }

            $encabezados = array_map('strtolower', $filas[0]);

            $mapa_columnas = [
                'nombre' => ['nombre', 'name', 'producto', 'perfume'],
                'descripcion' => ['descripcion', 'descripcion', 'description', 'notas', 'notas olfativas'],
                'precio' => ['precio', 'price', 'precio venta', 'valor', 'pvp'],
                'stock' => ['stock', 'cantidad', 'quantity', 'unidades', 'qty', 'existencia', 'existencias'],
                'imagen' => ['imagen', 'image', 'foto', 'photo', 'url imagen', 'url'],
            ];

            $columnas_detectadas = [];
            foreach ($mapa_columnas as $campo => $variantes) {
                $idx = false;
                foreach ($variantes as $v) {
                    $idx = array_search($v, $encabezados);
                    if ($idx !== false) break;
                }
                $columnas_detectadas[$campo] = $idx;
            }

            if ($columnas_detectadas['nombre'] === false || $columnas_detectadas['precio'] === false) {
                throw new Exception('El archivo debe tener columnas "nombre" y "precio" al menos. Columnas detectadas: ' . implode(', ', $encabezados));
            }

            $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen, stock) VALUES (?, ?, ?, ?, ?)");
            $carpeta_destino = __DIR__ . '/imagenes/';

            for ($i = 1; $i < count($filas); $i++) {
                $fila = $filas[$i];
                $nombre = trim($fila[$columnas_detectadas['nombre']] ?? '');
                if (empty($nombre)) continue;

                $descripcion = $columnas_detectadas['descripcion'] !== false ? trim($fila[$columnas_detectadas['descripcion']] ?? '') : '';
                $precio = $columnas_detectadas['precio'] !== false ? floatval(str_replace(['$', '.', ','], ['', '', '.'], $fila[$columnas_detectadas['precio']] ?? 0)) : 0;
                $stock = $columnas_detectadas['stock'] !== false ? intval($fila[$columnas_detectadas['stock']] ?? 0) : 0;
                $imagen = '';

                if ($columnas_detectadas['imagen'] !== false) {
                    $url_imagen = trim($fila[$columnas_detectadas['imagen']] ?? '');
                    if (!empty($url_imagen) && filter_var($url_imagen, FILTER_VALIDATE_URL)) {
                        $nombre_limpio = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $nombre)));
                        $ext = 'jpg';
                        $nombre_img = $nombre_limpio . '_' . uniqid() . '.' . $ext;
                        $contenido = @file_get_contents($url_imagen);
                        if ($contenido !== false) {
                            file_put_contents($carpeta_destino . $nombre_img, $contenido);
                            $imagen = $nombre_img;
                        }
                    }
                }

                $stmt->execute([$nombre, $descripcion, $precio, $imagen, $stock]);
                $contador++;
            }

        } catch (Exception $e) {
            $errores[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Productos</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 600px; width: 100%; }
        h2 { color: #2c3e50; margin-bottom: 10px; }
        .info { font-size: 14px; color: #666; margin-bottom: 20px; }
        input[type="file"] { display: block; margin: 15px 0; }
        button { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 15px; }
        button:hover { background: #219653; }
        .error { color: #e74c3c; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .exito { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #2c3e50; color: white; }
        .btn-volver { display: inline-block; margin-top: 15px; color: #2c3e50; }
    </style>
</head>
<body>
    <div class="box">
        <h2>📥 Importar Productos desde Excel</h2>
        <p class="info">Sube un archivo <strong>CSV</strong> o <strong>XLSX</strong> con las siguientes columnas:</p>

        <table>
            <tr><th>Columna</th><th>Requerido</th><th>Descripción</th></tr>
            <tr><td>nombre</td><td>Sí</td><td>Nombre del perfume</td></tr>
            <tr><td>descripcion</td><td>No</td><td>Notas olfativas</td></tr>
            <tr><td>precio</td><td>Sí</td><td>Precio de venta</td></tr>
            <tr><td>stock</td><td>No</td><td>Unidades disponibles</td></tr>
            <tr><td>imagen</td><td>No</td><td>URL pública de la foto (la descarga automática)</td></tr>
        </table>

        <?php if ($errores): ?>
            <div class="error"><?php echo implode('<br>', $errores); ?></div>
        <?php endif; ?>

        <?php if ($contador > 0): ?>
            <div class="exito">✅ Se importaron <strong><?php echo $contador; ?></strong> productos correctamente.</div>
        <?php endif; ?>

        <?php if (isset($columnas_detectadas)): ?>
            <div class="info" style="background:#eef;padding:10px;border-radius:4px;font-size:13px;">
                <strong>Columnas detectadas:</strong>
                nombre=<?php echo $columnas_detectadas['nombre'] !== false ? '✔' : '✖'; ?>,
                descripcion=<?php echo $columnas_detectadas['descripcion'] !== false ? '✔' : '✖'; ?>,
                precio=<?php echo $columnas_detectadas['precio'] !== false ? '✔' : '✖'; ?>,
                stock=<?php echo $columnas_detectadas['stock'] !== false ? '✔' : '✖'; ?>,
                imagen=<?php echo $columnas_detectadas['imagen'] !== false ? '✔' : '✖'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo" accept=".csv,.xlsx,.xls" required>
            <button type="submit">Subir e Importar</button>
        </form>

        <a href="panel_control.php" class="btn-volver">← Volver al panel</a>
    </div>
</body>
</html>

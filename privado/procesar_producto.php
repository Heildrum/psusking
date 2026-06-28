<?php
session_start();
// Seguridad: Si el dueño no ha pasado el 2FA, no puede usar este procesador
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}

require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion      = $_POST['accion'] ?? '';
    $id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre      = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio      = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    // Validación básica de campos obligatorios
    if (empty($nombre) || empty($descripcion) || $precio === false || $stock === false) {
        die("Error: Por favor rellena todos los campos con valores válidos.");
    }

    // --- GESTIÓN DE LA FOTO PARTICULAR ---
    $nombre_imagen_final = null;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        
        // Extraemos la extensión del archivo (ej: jpg, png)
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $extensiones_permitidas)) {
            // Limpiamos el nombre del producto para la URL (Quitamos espacios y caracteres raros)
            $nombre_limpio = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $nombre)));
            
            // Generamos un nombre único usando el nombre del perfume y un código aleatorio
            $nombre_imagen_final = $nombre_limpio . "_" . uniqid() . "." . $ext;
            
            // Ruta física del servidor donde se guardará el archivo
            $carpeta_destino = __DIR__ . '/imagenes/';
            
            // Movemos la foto temporal a la carpeta definitiva de tu Hosting
            if (!move_uploaded_file($file_tmp, $carpeta_destino . $nombre_imagen_final)) {
                die("Error: No se pudo guardar la imagen en el servidor. Revisa los permisos de la carpeta 'imagenes/'.");
            }
        } else {
            die("Error: Formato de imagen no permitido. Usa JPG, PNG o WEBP.");
        }
    }

    // --- OPERACIONES EN LA BASE DE DATOS (MySQL) ---
    if ($accion === 'crear') {
        // Al crear, la foto es obligatoria
        if (!$nombre_imagen_final) {
            die("Error: Debes subir una foto para el nuevo perfume.");
        }

        $sql = "INSERT INTO productos (nombre, descripcion, precio, imagen, stock) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $precio, $nombre_imagen_final, $stock]);

    } elseif ($accion === 'editar' && $id) {
        if ($nombre_imagen_final) {
            // Si el dueño subió una NUEVA foto, primero buscamos la antigua para borrarla del disco duro
            $stmt_antigua = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
            $stmt_antigua->execute([$id]);
            $foto_antigua = $stmt_antigua->fetchColumn();
            
            if ($foto_antigua && file_exists(__DIR__ . '/imagenes/' . $foto_antigua)) {
                unlink(__DIR__ . '/imagenes/' . $foto_antigua); // Borra el archivo físico viejo
            }

            // Actualizamos todos los datos incluyendo la nueva ruta de imagen
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, stock = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $precio, $nombre_imagen_final, $stock, $id]);
        } else {
            // Si no se subió una nueva foto, actualizamos los textos y mantenemos la foto actual en MySQL
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $precio, $stock, $id]);
        }
    }

    // Al finalizar con éxito, regresa a la sección de gestión de productos
    header("Location: panel_control.php?seccion=productos&status=success");
    exit;
}
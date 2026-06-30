<?php
session_start();
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../csrf_helper.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = 'error';

if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $imagen = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);

        if ($imagen && file_exists(__DIR__ . '/imagenes/' . $imagen)) {
            unlink(__DIR__ . '/imagenes/' . $imagen);
        }

        $status = 'success';
    } catch (Exception $e) {
        $status = 'error';
    }
}

header("Location: panel_control.php?seccion=productos&status=$status");
exit;

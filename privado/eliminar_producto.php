<?php
session_start();
if (!isset($_SESSION['dueno_logeado'])) {
    header("Location: ingreso_secreto_dueno.php");
    exit;
}
require_once __DIR__ . '/../conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $imagen = $stmt->fetchColumn();
    if ($imagen && file_exists(__DIR__ . '/imagenes/' . $imagen)) {
        unlink(__DIR__ . '/imagenes/' . $imagen);
    }
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: panel_control.php?seccion=productos&status=success");
exit;

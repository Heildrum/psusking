<?php
session_start();

$accion = $_GET['accion'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if ($accion === 'agregar' && $id) {
    $cantidad = max(1, intval($_GET['cantidad'] ?? 1));
    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id] += $cantidad;
    } else {
        $_SESSION['carrito'][$id] = $cantidad;
    }
}

if ($accion === 'quitar' && $id) {
    if (isset($_SESSION['carrito'][$id])) {
        unset($_SESSION['carrito'][$id]);
    }
}

if ($accion === 'vaciar') {
    $_SESSION['carrito'] = [];
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($referer && $host) {
    $ref_host = parse_url($referer, PHP_URL_HOST);
    if ($ref_host === $host) {
        header("Location: $referer");
        exit;
    }
}
header("Location: index.php");
exit;

<?php
function csrf_generar_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_generar_token() . '">';
}

function csrf_validar() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die("Error de seguridad: Token CSRF inválido. Intenta recargar la página.");
        }
    }
}

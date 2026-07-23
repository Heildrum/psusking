<?php
$env_file = __DIR__ . '/../../Elixir.env';
if (file_exists($env_file)) {
    $lineas = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue;
        list($key, $val) = explode('=', $linea, 2) + [null, null];
        if ($key && $val !== null) {
            $GLOBALS[trim($key)] = trim($val);
        }
    }
}

$host    = $GLOBALS['DB_HOST'] ?? 'localhost';
$db      = $GLOBALS['DB_NAME'] ?? 'tienda_perfumes';
$user    = $GLOBALS['DB_USER'] ?? 'root';
$password = $GLOBALS['DB_PASS'] ?? '';
$charset = $GLOBALS['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    die("Error crítico de conexión a la base de datos.");
}

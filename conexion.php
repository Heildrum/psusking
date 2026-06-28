<?php
// Configuración de la base de datos
// Cuando estés en tu computadora local con XAMPP, usa estos valores:
$host    = 'localhost';
$db      = 'tienda_perfumes';
$user    = 'root';
$password = ''; // En XAMPP suele estar vacío. En Hostinger pondrás la contraseña que crees.
$charset  = 'utf8mb4';

// Cuando subas el proyecto a Hostinger, solo debes cambiar los valores de arriba 
// por los que te entregue el panel (ej: $host = 'mysql.hostinger.cl', etc.)

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones de configuración de PDO para mayor seguridad y control de errores
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Activa el reporte de errores graves
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve los datos en arreglos limpios
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa consultas preparadas reales (Seguridad)
];

try {
    // Intentamos establecer la conexión con MySQL
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // Si algo sale mal (ej: contraseña incorrecta), detiene la página y muestra el error
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
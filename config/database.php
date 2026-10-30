<?php
declare(strict_types=1);
$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    $configPath = __DIR__ . '/config.example.php';
}
$config = require $configPath;
date_default_timezone_set($config['timezone'] ?? 'America/Bogota');

function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'] ?? '3306', $db['name'], $db['charset'] ?? 'utf8mb4');
   try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (!empty($db['ssl_ca'])) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $db['ssl_ca'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);

} catch (PDOException $e) {
        error_log('SaniApp DB connection error: ' . $e->getMessage());
        http_response_code(500);
        exit('No fue posible conectar con la base de datos. Verifica los datos en config/config.php y que hayas importado database.sql.');
    }
    return $pdo;
}

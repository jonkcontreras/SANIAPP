<?php
/**
 * Configuración por variables de entorno.
 * En local, Docker Compose las proporciona automáticamente.
 * En Render, se configuran en Environment del servicio web.
 */
$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'app_name' => 'SaniApp',
    'app_env' => $env('APP_ENV', 'development'),
    'app_url' => rtrim($env('APP_URL', 'http://localhost:8080'), '/'),
    'timezone' => $env('APP_TIMEZONE', 'America/Bogota'),
    'db' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '3306'),
        'name' => $env('DB_NAME', 'saniapp'),
        'user' => $env('DB_USER', 'root'),
        'pass' => $env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'mail_from' => $env('MAIL_FROM', 'no-responder@tudominio.com'),
];

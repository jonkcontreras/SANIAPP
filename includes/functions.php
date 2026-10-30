<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Render termina HTTPS en su proxy y reenvía el protocolo en este encabezado.
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $https = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        flash('error', 'Inicia sesión para continuar.');
        redirect('login.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = (string)($_POST['csrf_token'] ?? '');
    $saved = (string)($_SESSION['csrf_token'] ?? '');
    if ($sent === '' || $saved === '' || !hash_equals($saved, $sent)) {
        http_response_code(419);
        exit('La sesión del formulario venció o no es válida. Regresa, recarga la página e inténtalo de nuevo.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function current_user(): ?array
{
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, nombre, apellido, ciudad, email, created_at FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function acta_status(string $fechaVencimiento): array
{
    $today = new DateTimeImmutable('today');
    $due = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaVencimiento);
    if (!$due) return ['estado' => 'Sin fecha válida', 'clase' => 'muted', 'dias' => null];
    $days = (int)$today->diff($due)->format('%r%a');
    if ($days < 0) return ['estado' => 'Vencida', 'clase' => 'danger', 'dias' => $days];
    if ($days <= 30) return ['estado' => ($days === 0 ? 'Vence hoy' : 'Próxima a vencer'), 'clase' => 'warning', 'dias' => $days];
    return ['estado' => 'Vigente', 'clase' => 'success', 'dias' => $days];
}

function valid_date_ymd(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    [$year, $month, $day] = array_map('intval', explode('-', $date));
    return checkdate($month, $day, $year);
}

function format_date(?string $date): string
{
    if (!$date) return 'No registrada';
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));
    return $dt ? $dt->format('d/m/Y') : e($date);
}

function app_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $configPath = __DIR__ . '/../config/config.php';
        if (!is_file($configPath)) $configPath = __DIR__ . '/../config/config.example.php';
        $cfg = require $configPath;
    }
    return $cfg;
}

function make_absolute_url(string $path): string
{
    $base = rtrim((string)(app_config()['app_url'] ?? ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function send_reset_email(string $to, string $url): bool
{
    $cfg = app_config();
    $from = filter_var($cfg['mail_from'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$from) return false;
    $subject = 'Restablecimiento de contraseña - SaniApp';
    $body = "Recibimos una solicitud para cambiar la contraseña de tu cuenta SaniApp.\n\nAbre el siguiente enlace dentro de los próximos 60 minutos:\n$url\n\nSi no solicitaste este cambio, ignora este correo.";
    $headers = "From: SaniApp <{$from}>\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($to, $subject, $body, $headers);
}

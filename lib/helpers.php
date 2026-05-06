<?php
function env_value(string $key, ?string $default = null): ?string
{
    static $loaded = false;

    if (!$loaded) {
        $envFile = dirname(__DIR__) . '/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                if ($k !== '' && getenv($k) === false) putenv($k . '=' . $v);
            }
        }
        $loaded = true;
    }

    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function drive_file_id(?string $url): string
{
    $text = trim((string)$url);
    if ($text === '') return '';
    if (preg_match('~/file/d/([a-zA-Z0-9_-]+)~', $text, $m)) return $m[1];
    if (preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $text, $m)) return $m[1];
    return '';
}

function asset_url(?string $url): string
{
    $raw = trim((string)$url);
    if ($raw === '') return '';

    $id = drive_file_id($raw);
    if ($id !== '') {
        return 'image.php?id=' . rawurlencode($id);
    }

    return $raw;
}

function normalized_phone(string $phone): string
{
    $p = preg_replace('/[^0-9+]/', '', $phone) ?? '';

    // Chuẩn hóa cùng một số về một định dạng duy nhất để tránh đăng ký trùng:
    // +84901234567 => 0901234567
    // 84901234567  => 0901234567
    if (str_starts_with($p, '+84')) {
        $p = '0' . substr($p, 3);
    } elseif (str_starts_with($p, '84') && strlen($p) === 11) {
        $p = '0' . substr($p, 2);
    }

    return $p;
}

function is_valid_vietnam_phone(string $phone): bool
{
    $p = normalized_phone($phone);
    return (bool)preg_match('/^(0[0-9]{9}|\+84[0-9]{9})$/', $p);
}

function is_valid_checkin_url(string $url): bool
{
    return (bool)preg_match('~^https?://([^/]+\.)?(facebook\.com|fb\.watch|tiktok\.com|vt\.tiktok\.com)(/|$)~i', trim($url));
}

function safe_file_name(string $name): string
{
    $name = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name) ?? 'image.jpg';
    return trim($name, '._-') ?: 'image.jpg';
}

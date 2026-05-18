<?php
require __DIR__ . '/lib/helpers.php';

$encoded = (string)($_GET['src'] ?? '');
$url = base64_decode(rawurldecode($encoded), true);
if (!is_string($url) || $url === '') {
    http_response_code(404);
    exit;
}

$url = public_file_url($url);
$host = parse_url($url, PHP_URL_HOST) ?: '';
$path = parse_url($url, PHP_URL_PATH) ?: '';

if (!str_ends_with($host, 'supabase.co') || !str_contains($path, '/storage/v1/object/public/landing-assets/')) {
    http_response_code(403);
    exit;
}

$headers = [
    'User-Agent: Mozilla/5.0 NamThangVideoProxy/1.0',
];

if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=\d*-\d*$/', $_SERVER['HTTP_RANGE'])) {
    $headers[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
}

@set_time_limit(0);
ignore_user_abort(true);

$sentHeaders = false;
$responseHeaders = [];
$statusCode = 200;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_HEADER => false,
    CURLOPT_BUFFERSIZE => 65536,
    CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$responseHeaders, &$statusCode, &$sentHeaders): int {
        $line = trim($header);

        if (preg_match('~^HTTP/\S+\s+(\d+)~', $line, $m)) {
            $statusCode = (int)$m[1];
            $responseHeaders = [];
            $sentHeaders = false;
            return strlen($header);
        }

        if ($line !== '') {
            $responseHeaders[] = $line;
            return strlen($header);
        }

        if (!$sentHeaders) {
            http_response_code($statusCode === 206 ? 206 : ($statusCode >= 400 ? 502 : 200));
            header('Content-Type: video/mp4');
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=86400');
            header('X-Accel-Buffering: no');

            foreach ($responseHeaders as $responseHeader) {
                if (preg_match('/^(Content-Type|Content-Length|Content-Range):\s*(.+)$/i', $responseHeader, $m)) {
                    header($m[1] . ': ' . trim($m[2]));
                }
            }

            $sentHeaders = true;
        }

        return strlen($header);
    },
    CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk): int {
        echo $chunk;
        if (ob_get_level() > 0) @ob_flush();
        flush();
        return strlen($chunk);
    },
]);

$ok = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($ok === false && !$sentHeaders) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo $error ?: 'Video proxy error';
}

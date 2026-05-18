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

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_HEADER => true,
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'video/mp4';
$error = curl_error($ch);
curl_close($ch);

if ($response === false || $status < 200 || $status >= 300) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo $error ?: 'Video proxy error';
    exit;
}

$rawHeaders = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

http_response_code($status === 206 ? 206 : 200);
header('Content-Type: ' . $contentType);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=86400');

if (preg_match('/^Content-Range:\s*(.+)$/mi', $rawHeaders, $m)) {
    header('Content-Range: ' . trim($m[1]));
}

header('Content-Length: ' . strlen($body));
echo $body;

<?php
require __DIR__ . '/lib/helpers.php';

$id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_GET['id'] ?? ''));
if ($id === '') {
    http_response_code(404);
    exit;
}

$cacheDir = __DIR__ . '/storage/cache';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0775, true);
$cacheFile = $cacheDir . '/' . $id . '.img';
$metaFile = $cacheDir . '/' . $id . '.json';

if (is_file($cacheFile) && is_file($metaFile) && filemtime($cacheFile) > time() - 86400) {
    $meta = json_decode(file_get_contents($metaFile), true) ?: [];
    header('Content-Type: ' . ($meta['contentType'] ?? 'image/jpeg'));
    header('Cache-Control: public, max-age=86400');
    readfile($cacheFile);
    exit;
}

$urls = [
    'https://drive.google.com/thumbnail?id=' . rawurlencode($id) . '&sz=w2000',
    'https://lh3.googleusercontent.com/d/' . rawurlencode($id) . '=w2000',
    'https://drive.google.com/uc?export=download&id=' . rawurlencode($id),
];

foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 NamThangLanding/1.0',
        CURLOPT_HEADER => true,
    ]);
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/jpeg';
    curl_close($ch);

    if ($resp === false || $status < 200 || $status >= 300) continue;
    $body = substr($resp, $headerSize);
    if (strlen($body) < 100 || !str_starts_with($contentType, 'image/')) continue;

    file_put_contents($cacheFile, $body);
    file_put_contents($metaFile, json_encode(['contentType' => $contentType]));
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=86400');
    echo $body;
    exit;
}

http_response_code(404);
header('Content-Type: image/svg+xml; charset=utf-8');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="400"><rect width="100%" height="100%" fill="#eef8e5"/><text x="50%" y="50%" text-anchor="middle" fill="#2d4f13" font-size="28" font-family="Arial">Không tải được ảnh</text></svg>';

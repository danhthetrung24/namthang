<?php
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/DatabaseClient.php';

function normalize_uploaded_files(array $files): array
{
    if (!isset($files['name'])) return [];

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }
    return $normalized;
}

function detect_uploaded_mime(string $tmpName, string $fallback): string
{
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        }
    }

    return $mime !== '' ? $mime : $fallback;
}

function is_allowed_checkin_media(string $mime): bool
{
    return in_array($mime, [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'video/x-msvideo',
        'video/x-m4v',
    ], true);
}

function validate_checkin_media(string $mime, int $bytes, int $index): void
{
    if (!is_allowed_checkin_media($mime)) {
        throw new RuntimeException('File số ' . $index . ' không đúng định dạng cho phép.');
    }

    $isVideo = str_starts_with($mime, 'video/');
    $limit = $isVideo ? 100 * 1024 * 1024 : 3 * 1024 * 1024;
    if ($bytes > $limit) {
        throw new RuntimeException($isVideo
            ? 'Video số ' . $index . ' vượt quá 100MB.'
            : 'Ảnh số ' . $index . ' vượt quá 3MB sau khi nén.');
    }
}

function media_extension(string $mime): string
{
    return match ($mime) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
        'video/x-msvideo' => 'avi',
        'video/x-m4v' => 'm4v',
        default => 'jpg',
    };
}

function call_get_webhook(string $url, array $payload): array
{
    $query = http_build_query($payload);
    $separator = str_contains($url, '?') ? '&' : '?';
    $ch = curl_init($url . $separator . $query);
    if (!$ch) throw new RuntimeException('Không thể khởi tạo webhook.');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) throw new RuntimeException('Lỗi kết nối webhook: ' . $error);
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('Webhook trả lỗi HTTP ' . $status . ': ' . $response);
    }

    $decoded = json_decode((string)$response, true);
    return is_array($decoded) ? $decoded : ['raw' => $response];
}

function app_base_url(): string
{
    $configured = trim((string)env_value('APP_BASE_URL', ''));
    if ($configured !== '') return rtrim($configured, '/');

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function public_upload_url(string $relativePath): string
{
    $parts = array_map(
        static fn($part) => rawurlencode($part),
        explode('/', str_replace('\\', '/', trim($relativePath, '/')))
    );
    return app_base_url() . '/' . implode('/', $parts);
}

function save_local_upload(string $folder, string $name, string $bytes): string
{
    $baseDir = __DIR__ . '/uploads/checkin';
    $dir = $baseDir . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Không thể tạo thư mục upload.');
    }

    $relativePath = 'uploads/checkin/' . $folder . '/' . $name;
    $fullPath = __DIR__ . '/' . $relativePath;
    if (file_put_contents($fullPath, $bytes) === false) {
        throw new RuntimeException('Không thể lưu file upload.');
    }

    return public_upload_url($relativePath);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $isMultipart = str_starts_with((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data');
    $data = $_POST;
    $uploadedFiles = [];

    if ($isMultipart) {
        $uploadedFiles = normalize_uploaded_files($_FILES['media'] ?? $_FILES['images'] ?? []);
    } else {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) throw new RuntimeException('Dữ liệu gửi lên không hợp lệ.');
    }

    $hoTen = trim((string)($data['hoTen'] ?? ''));
    $soDienThoai = trim((string)($data['soDienThoai'] ?? ''));
    $linkCheckIn = trim((string)($data['linkCheckIn'] ?? ''));
    $platform = trim((string)($data['platform'] ?? ''));
    $userAgent = substr(trim((string)($data['userAgent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''))), 0, 500);
    $images = $data['images'] ?? [];

    if ($hoTen === '') throw new RuntimeException('Vui lòng nhập họ tên.');
    if ($soDienThoai === '') throw new RuntimeException('Vui lòng nhập số điện thoại.');
    if (!is_valid_vietnam_phone($soDienThoai)) throw new RuntimeException('Số điện thoại không hợp lệ.');
    if ($linkCheckIn !== '' && !is_valid_checkin_url($linkCheckIn)) throw new RuntimeException('Link checkin phải là Facebook hoặc TikTok.');
    if (!is_array($images)) $images = [];
    if (count($images) + count($uploadedFiles) > 10) throw new RuntimeException('Chỉ cho phép tối đa 10 file.');
    if ($linkCheckIn === '' && count($images) + count($uploadedFiles) === 0) {
        throw new RuntimeException('Vui lòng dán link hoặc tải ảnh/video checkin.');
    }

    $storageUsesSupabase = !DatabaseClient::isSqlServer();
    $database = DatabaseClient::fromEnv();
    $phoneNormalized = normalized_phone($soDienThoai);

    // Chặn đăng ký trùng trước khi upload file để tránh tốn Storage.
    $existing = $database->findRegistrationByPhone($phoneNormalized);
    if (!empty($existing)) {
        throw new RuntimeException('Số điện thoại này đã đăng ký nhận Magic Ticket. Mỗi số điện thoại chỉ được đăng ký 1 lần.');
    }

    $bucket = env_value('SUPABASE_STORAGE_BUCKET', 'magic-ticket-images');
    $rowId = bin2hex(random_bytes(16));
    $folder = date('Ymd_His') . '_' . $rowId;

    $fileNames = [];
    $fileLinks = [];

    foreach ($uploadedFiles as $index => $file) {
        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File số ' . ($index + 1) . ' tải lên không thành công.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('File số ' . ($index + 1) . ' không đọc được.');
        }

        $bytes = file_get_contents($tmpName);
        if ($bytes === false) throw new RuntimeException('File số ' . ($index + 1) . ' không đọc được.');

        $mime = detect_uploaded_mime($tmpName, (string)($file['type'] ?? ''));
        validate_checkin_media($mime, strlen($bytes), $index + 1);

        $name = safe_file_name((string)($file['name'] ?? ('checkin_' . ($index + 1) . media_extension($mime))));
        $name = preg_replace('/\.[^.]+$/', '', $name) . '.' . media_extension($mime);
        $path = $folder . '/' . ($index + 1) . '_' . $name;

        if ($storageUsesSupabase) {
            $database->uploadObject($bucket, $path, $bytes, $mime);
            $fileLinks[] = $database->publicUrl($bucket, $path);
        } else {
            $fileLinks[] = save_local_upload($folder, ($index + 1) . '_' . $name, $bytes);
        }
        $fileNames[] = $name;
    }

    $jsonStartIndex = count($uploadedFiles);
    foreach ($images as $index => $img) {
        if (!is_array($img) || empty($img['base64'])) continue;

        $mime = (string)($img['mimeType'] ?? 'image/jpeg');
        $displayIndex = $jsonStartIndex + $index + 1;
        if (!is_allowed_checkin_media($mime)) throw new RuntimeException('File số ' . $displayIndex . ' không đúng định dạng cho phép.');

        $base64 = preg_replace('/\s+/', '', (string)$img['base64']);
        $bytes = base64_decode($base64, true);
        if ($bytes === false) throw new RuntimeException('File số ' . $displayIndex . ' không đọc được.');
        validate_checkin_media($mime, strlen($bytes), $displayIndex);

        $name = safe_file_name((string)($img['name'] ?? ('checkin_' . ($index + 1) . '.jpg')));
        $name = preg_replace('/\.[^.]+$/', '', $name) . '.' . media_extension($mime);
        $path = $folder . '/' . $displayIndex . '_' . $name;

        if ($storageUsesSupabase) {
            $database->uploadObject($bucket, $path, $bytes, $mime);
            $fileLinks[] = $database->publicUrl($bucket, $path);
        } else {
            $fileLinks[] = save_local_upload($folder, $displayIndex . '_' . $name, $bytes);
        }
        $fileNames[] = $name;
    }

    $inserted = $database->insertRegistration([
        'id' => $rowId,
        'ho_ten' => $hoTen,
        'so_dien_thoai' => $phoneNormalized,
        'link_checkin' => $linkCheckIn,
        'platform' => $platform,
        'user_agent' => $userAgent,
        'so_anh' => count($fileLinks),
        'ten_file_anh' => $fileNames,
        'link_anh' => $fileLinks,
        'status' => 'new',
    ]);

    $webhookOk = true;
    $webhookError = null;
    $webhookResponse = null;
    try {
        $webhookResponse = call_get_webhook('https://n8n.taxinamthang.vn/webhook/xe-bus', [
            'id' => $rowId,
            'hoTen' => $hoTen,
            'soDienThoai' => $phoneNormalized,
            'linkCheckIn' => $linkCheckIn,
            'platform' => $platform,
            'userAgent' => $userAgent,
            'fileCount' => count($fileLinks),
            'fileNames' => $fileNames,
            'fileLinks' => $fileLinks,
            'status' => 'new',
            'createdAt' => gmdate('c'),
        ]);
    } catch (Throwable $webhookException) {
        $webhookOk = false;
        $webhookError = $webhookException->getMessage();
    }

    json_response([
        'success' => true,
        'message' => 'Đăng ký nhận Magic Ticket thành công.',
        'id' => $rowId,
        'fileCount' => count($fileLinks),
        'imageCount' => count($fileLinks),
        'webhookOk' => $webhookOk,
        'webhookError' => $webhookError,
        'webhookResponse' => $webhookResponse,
        'data' => $inserted[0] ?? null,
    ]);
} catch (Throwable $e) {
    $message = $e->getMessage();

    // Nếu 2 request gửi cùng lúc, database unique index vẫn là lớp chặn cuối cùng.
    if (str_contains($message, 'duplicate key value') || str_contains($message, '23505')) {
        $message = 'Số điện thoại này đã đăng ký nhận Magic Ticket. Mỗi số điện thoại chỉ được đăng ký 1 lần.';
    }

    json_response(['success' => false, 'message' => $message], 400);
}

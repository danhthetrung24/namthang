<?php
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/SupabaseClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) throw new RuntimeException('Dữ liệu gửi lên không hợp lệ.');

    $hoTen = trim((string)($data['hoTen'] ?? ''));
    $soDienThoai = trim((string)($data['soDienThoai'] ?? ''));
    $linkCheckIn = trim((string)($data['linkCheckIn'] ?? ''));
    $platform = trim((string)($data['platform'] ?? ''));
    $userAgent = substr(trim((string)($data['userAgent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''))), 0, 500);
    $images = $data['images'] ?? [];

    if ($hoTen === '') throw new RuntimeException('Vui lòng nhập họ tên.');
    if ($soDienThoai === '') throw new RuntimeException('Vui lòng nhập số điện thoại.');
    if (!is_valid_vietnam_phone($soDienThoai)) throw new RuntimeException('Số điện thoại không hợp lệ.');
    if ($linkCheckIn === '') throw new RuntimeException('Vui lòng dán link check-in.');
    if (!is_valid_checkin_url($linkCheckIn)) throw new RuntimeException('Link check-in phải là Facebook hoặc TikTok.');
    if (!is_array($images)) $images = [];
    if (count($images) > 10) throw new RuntimeException('Chỉ cho phép tối đa 10 ảnh.');

    $supabase = SupabaseClient::fromEnv();
    $phoneNormalized = normalized_phone($soDienThoai);

    // Chặn đăng ký trùng trước khi upload ảnh để tránh tốn Storage.
    $existing = $supabase->findRegistrationByPhone($phoneNormalized);
    if (!empty($existing)) {
        throw new RuntimeException('Số điện thoại này đã đăng ký nhận Magic Ticket. Mỗi số điện thoại chỉ được đăng ký 1 lần.');
    }

    $bucket = env_value('SUPABASE_STORAGE_BUCKET', 'magic-ticket-images');
    $rowId = bin2hex(random_bytes(16));
    $folder = date('Ymd_His') . '_' . $rowId;

    $fileNames = [];
    $fileLinks = [];

    foreach ($images as $index => $img) {
        if (!is_array($img) || empty($img['base64'])) continue;

        $mime = (string)($img['mimeType'] ?? 'image/jpeg');
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('Ảnh số ' . ($index + 1) . ' không đúng định dạng cho phép.');
        }

        $base64 = preg_replace('/\s+/', '', (string)$img['base64']);
        $bytes = base64_decode($base64, true);
        if ($bytes === false) throw new RuntimeException('Ảnh số ' . ($index + 1) . ' không đọc được.');
        if (strlen($bytes) > 3 * 1024 * 1024) throw new RuntimeException('Ảnh số ' . ($index + 1) . ' vượt quá 3MB sau khi nén.');

        $name = safe_file_name((string)($img['name'] ?? ('checkin_' . ($index + 1) . '.jpg')));
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
        $name = preg_replace('/\.[^.]+$/', '', $name) . '.' . $ext;
        $path = $folder . '/' . ($index + 1) . '_' . $name;

        $supabase->uploadObject($bucket, $path, $bytes, $mime);
        $fileNames[] = $name;
        $fileLinks[] = $supabase->publicUrl($bucket, $path);
    }

    $inserted = $supabase->insertRegistration([
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

    json_response([
        'success' => true,
        'message' => 'Đăng ký nhận Magic Ticket thành công.',
        'id' => $rowId,
        'imageCount' => count($fileLinks),
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

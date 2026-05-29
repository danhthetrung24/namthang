<?php
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/SupabaseClient.php';

function admin_env_username(): string
{
    return env_value('ADMIN_USERNAME', 'admin') ?: 'admin';
}

function admin_env_password(): string
{
    return env_value('ADMIN_PASSWORD', 'changeme') ?: 'changeme';
}

function admin_require_auth(): void
{
    $expectedUser = admin_env_username();
    $expectedPass = admin_env_password();
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (!hash_equals($expectedUser, $user) || !hash_equals($expectedPass, $pass)) {
        header('WWW-Authenticate: Basic realm="Nam Thang Admin"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Bạn cần đăng nhập admin.';
        exit;
    }
}

function admin_filters_from_request(): array
{
    return [
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
        'from' => trim((string)($_GET['from'] ?? '')),
        'to' => trim((string)($_GET['to'] ?? '')),
    ];
}

function admin_status_options(): array
{
    return [
        'new' => 'Mới',
        'contacted' => 'Đã liên hệ',
        'qualified' => 'Hợp lệ',
        'rejected' => 'Không hợp lệ',
        'done' => 'Hoàn tất',
    ];
}

function admin_landing_asset_fields(): array
{
    return [
        'logoUrl' => 'Logo',
        'heroBannerImageUrl' => 'Banner chính',
        'popupPromoImageUrl' => 'Ảnh popup khuyến mãi',
        'checkinGuideImageUrl' => 'Ảnh hướng dẫn checkin',
        'whySectionImageUrl' => 'Ảnh lý do chọn',
        'vehicleDefaultImageUrl' => 'Ảnh xe mặc định',
        'vehicle16ImageUrl' => 'Ảnh xe 16 chỗ',
        'vehicle16VideoUrl' => 'Video xe 16 chỗ',
        'vehicle29ImageUrl' => 'Ảnh xe 29 chỗ',
        'vehicle29VideoUrl' => 'Video xe 29 chỗ',
        'vehicleLimoImageUrl' => 'Ảnh xe Limousine',
        'vehicleLimoVideoUrl' => 'Video xe Limousine',
        'vehicle45ImageUrl' => 'Ảnh xe 45 chỗ',
        'vehicle45VideoUrl' => 'Video xe 45 chỗ',
        'testimonialImage1' => 'Khách hàng 1',
        'testimonialImage2' => 'Khách hàng 2',
        'testimonialImage3' => 'Khách hàng 3',
        'testimonialImage4' => 'Khách hàng 4',
    ];
}

function admin_asset_rows_to_map(array $rows): array
{
    $map = [];
    foreach ($rows as $row) {
        $key = (string)($row['asset_key'] ?? '');
        if ($key === '') continue;
        $map[$key] = (string)($row['asset_url'] ?? '');
    }
    return $map;
}

function admin_current_url_query(array $extra = []): string
{
    $query = array_merge($_GET, $extra);
    return http_build_query(array_filter($query, static fn($v) => $v !== '' && $v !== null));
}

function admin_format_date(?string $iso): string
{
    if (!$iso) return '';
    try {
        $dt = new DateTimeImmutable($iso);
        return $dt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i');
    } catch (Throwable) {
        return $iso;
    }
}

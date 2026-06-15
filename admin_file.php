<?php
require __DIR__ . '/admin_bootstrap.php';
admin_require_auth();

$url = trim((string)($_GET['url'] ?? ''));
$host = $url !== '' ? (parse_url($url, PHP_URL_HOST) ?: '') : '';
$path = $url !== '' ? (parse_url($url, PHP_URL_PATH) ?: '') : '';
$isHttp = (bool)preg_match('/^https?:\/\//i', $url);
$isSupabaseStorage = $isHttp
    && str_ends_with(strtolower($host), '.supabase.co')
    && str_contains($path, '/storage/v1/');

if ($url !== '' && $isHttp && !$isSupabaseStorage) {
    header('Location: ' . $url, true, 302);
    exit;
}

if ($url !== '' && !$isHttp && preg_match('/^uploads\/checkin\//', $url)) {
    header('Location: ' . $url, true, 302);
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>File checkin cũ</title>
  <style>
    :root{--brand:#8BC34A;--text:#26391a;--muted:#607059;--line:#f0d7b8}
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial,'Helvetica Neue',sans-serif;color:var(--text);background:linear-gradient(180deg,#fff8ef,#f7fff2);font-size:16px}
    .wrap{min-height:100vh;display:grid;place-items:center;padding:24px}
    .card{width:min(100%,720px);background:#fff;border:1px solid var(--line);border-radius:22px;box-shadow:0 18px 44px rgba(91,58,20,.12);padding:26px}
    h1{margin:0 0 12px;font-size:26px;color:#7a430f}
    p{margin:0 0 12px;line-height:1.6;color:#4d5a47;font-weight:700}
    .box{margin:16px 0;padding:14px;border-radius:16px;background:#fff7e6;border:1px solid #ffd591;color:#7a430f;line-height:1.55;font-weight:800}
    .url{word-break:break-all;color:#667;font-size:13px;font-family:Consolas,monospace;background:#f8faf6;border-radius:12px;padding:12px;margin-top:12px}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
    .btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:999px;background:var(--brand);color:#1e3515;padding:11px 16px;font-weight:900;text-decoration:none}
    .btn.light{background:#fff;border:1px solid #d6e3cc}
  </style>
</head>
<body>
  <main class="wrap">
    <section class="card">
      <h1>File checkin cũ đang nằm trên Supabase</h1>
      <?php if ($url === ''): ?>
        <p>Thiếu đường dẫn file.</p>
      <?php elseif (!$isSupabaseStorage): ?>
        <p>Đường dẫn file không hợp lệ hoặc không được hỗ trợ.</p>
      <?php else: ?>
        <p>File này là dữ liệu cũ từ Supabase Storage. Hiện Supabase đang trả lỗi quota nên không thể tải ảnh/video từ đường dẫn này.</p>
        <div class="box">
          Để xem lại file cũ, cần mở lại Supabase tạm thời rồi migrate ảnh/video sang nơi lưu trữ mới, hoặc upload lại file gốc nếu còn bản lưu.
        </div>
        <div class="url"><?= e($url) ?></div>
        <div class="actions">
          <a class="btn" href="<?= e($url) ?>" target="_blank" rel="noopener">Thử mở trực tiếp</a>
          <a class="btn light" href="admin.php">Quay lại admin</a>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>

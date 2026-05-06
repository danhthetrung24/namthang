<?php
require __DIR__ . '/admin_bootstrap.php';
admin_require_auth();

$filters = admin_filters_from_request();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$error = '';
$rows = [];
$hasNext = false;
$statusOptions = admin_status_options();

try {
    $supabase = SupabaseClient::fromEnv();
    $rows = $supabase->listRegistrations($filters, $perPage + 1, $offset);
    $hasNext = count($rows) > $perPage;
    if ($hasNext) $rows = array_slice($rows, 0, $perPage);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$exportQuery = admin_current_url_query(['page' => null]);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Magic Ticket</title>
  <style>
    :root{--brand:#8BC34A;--accent:#ed111b;--text:#26391a;--muted:#607059;--bg:#f4fbf2;--card:#fff;--line:#dfead8;--shadow:0 14px 34px rgba(31,45,26,.10)}
    *{box-sizing:border-box} body{margin:0;font-family:Arial,'Helvetica Neue',sans-serif;color:var(--text);background:linear-gradient(180deg,#eef9f7,#f7fff2);font-size:14px} a{color:#16781d;text-decoration:none;font-weight:700} .wrap{width:min(100% - 28px,1280px);margin:0 auto;padding:24px 0 44px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.title h1{margin:0 0 6px;font-size:28px}.title p{margin:0;color:var(--muted);font-weight:700}.actions{display:flex;gap:10px;flex-wrap:wrap}.btn{border:0;border-radius:999px;background:var(--brand);color:#1e3515;padding:11px 16px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}.btn.red{background:var(--accent);color:#fff}.btn.light{background:#fff;border:1px solid var(--line)}.card{background:rgba(255,255,255,.94);border:1px solid rgba(139,195,74,.22);border-radius:22px;box-shadow:var(--shadow);padding:16px;margin-bottom:16px}.filters{display:grid;grid-template-columns:1.6fr .9fr .75fr .75fr auto;gap:10px;align-items:end}.field label{display:block;margin:0 0 6px;color:#315516;font-weight:900}.field input,.field select{width:100%;border:1.5px solid #d6e3cc;border-radius:14px;padding:11px 12px;background:#fbfdf8;outline:none}.alert{background:#fff1f0;color:#a8071a;border:1px solid #ffa39e;border-radius:16px;padding:12px 14px;margin-bottom:16px;font-weight:800}.table-wrap{overflow:auto;border-radius:18px;border:1px solid var(--line);background:#fff}table{width:100%;border-collapse:separate;border-spacing:0;min-width:1080px}th,td{padding:12px;border-bottom:1px solid #edf2e8;text-align:left;vertical-align:top}th{position:sticky;top:0;background:#eef9df;color:#315516;font-size:12px;text-transform:uppercase;z-index:1}tr:hover td{background:#fbfff8}.id{font-family:monospace;font-size:12px;color:#667}.phone{font-weight:900;white-space:nowrap}.status{border:1px solid #d6e3cc;border-radius:999px;padding:7px 10px;background:#fbfdf8;font-weight:800}.status-form{display:flex;gap:6px;align-items:center}.images{display:flex;gap:8px;flex-wrap:wrap;max-width:260px}.thumb{width:62px;height:62px;border-radius:12px;object-fit:cover;border:1px solid #dfead8;background:#f3f7ef}.empty{padding:32px;text-align:center;color:var(--muted);font-weight:800}.pagination{display:flex;justify-content:space-between;align-items:center;margin-top:14px;gap:12px}.small{font-size:12px;color:var(--muted)}.warn{margin-top:10px;color:#9a6700;font-weight:800}.link-cell{max-width:220px;word-break:break-all}.nowrap{white-space:nowrap}@media(max-width:900px){.top{align-items:flex-start;flex-direction:column}.filters{grid-template-columns:1fr 1fr}.filters .submit-filter{grid-column:1/-1}.btn{width:100%}.actions{width:100%}.actions .btn{flex:1}.wrap{width:min(100% - 20px,1280px);padding-top:16px}}
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="title">
      <h1>Quản lý đăng ký Magic Ticket</h1>
      <p>Xem khách hàng đã đăng ký, ảnh đính kèm và xuất Excel.</p>
      <?php if (admin_env_password() === 'changeme'): ?>
        <div class="warn">Cảnh báo: bạn đang dùng mật khẩu mặc định. Hãy thêm ADMIN_USERNAME và ADMIN_PASSWORD vào file .env.</div>
      <?php endif; ?>
    </div>
    <div class="actions">
      <a class="btn light" href="admin.php">Làm mới</a>
      <a class="btn red" href="admin_export.php<?= $exportQuery ? '?' . e($exportQuery) : '' ?>">Xuất Excel</a>
    </div>
  </div>

  <form class="card filters" method="get" action="admin.php">
    <div class="field">
      <label>Tìm kiếm</label>
      <input name="q" value="<?= e($filters['q']) ?>" placeholder="Tên, số điện thoại hoặc link check-in">
    </div>
    <div class="field">
      <label>Trạng thái</label>
      <select name="status">
        <option value="">Tất cả</option>
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Từ ngày</label>
      <input type="date" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
      <label>Đến ngày</label>
      <input type="date" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <div class="submit-filter">
      <button class="btn" type="submit">Lọc</button>
    </div>
  </form>

  <?php if ($error): ?>
    <div class="alert">Lỗi: <?= e($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Khách hàng</th>
            <th>Số điện thoại</th>
            <th>Check-in</th>
            <th>Ảnh</th>
            <th>Nền tảng</th>
            <th>Trạng thái</th>
            <th>ID</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8"><div class="empty">Chưa có dữ liệu phù hợp.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <?php $links = is_array($row['link_anh'] ?? null) ? $row['link_anh'] : []; ?>
          <tr>
            <td class="nowrap"><?= e(admin_format_date($row['created_at'] ?? '')) ?></td>
            <td><strong><?= e($row['ho_ten'] ?? '') ?></strong></td>
            <td class="phone"><?= e($row['so_dien_thoai'] ?? '') ?></td>
            <td class="link-cell"><a href="<?= e($row['link_checkin'] ?? '#') ?>" target="_blank" rel="noopener">Mở link</a></td>
            <td>
              <?php if ($links): ?>
                <div class="images">
                  <?php foreach ($links as $i => $url): ?>
                    <a href="<?= e($url) ?>" target="_blank" rel="noopener" title="Mở ảnh <?= $i + 1 ?>">
                      <img class="thumb" src="<?= e($url) ?>" alt="Ảnh <?= $i + 1 ?>" loading="lazy">
                    </a>
                  <?php endforeach; ?>
                </div>
                <div class="small"><?= count($links) ?> ảnh</div>
              <?php else: ?>
                <span class="small">Không có ảnh</span>
              <?php endif; ?>
            </td>
            <td><?= e($row['platform'] ?? '') ?></td>
            <td>
              <form class="status-form" method="post" action="admin_status.php">
                <input type="hidden" name="id" value="<?= e($row['id'] ?? '') ?>">
                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? 'admin.php') ?>">
                <select class="status" name="status" onchange="this.form.submit()">
                  <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($row['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td class="id"><?= e($row['id'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <div class="small">Trang <?= $page ?> · Hiển thị tối đa <?= $perPage ?> dòng/trang</div>
      <div class="actions">
        <?php if ($page > 1): ?>
          <a class="btn light" href="admin.php?<?= e(admin_current_url_query(['page' => $page - 1])) ?>">← Trang trước</a>
        <?php endif; ?>
        <?php if ($hasNext): ?>
          <a class="btn light" href="admin.php?<?= e(admin_current_url_query(['page' => $page + 1])) ?>">Trang sau →</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>

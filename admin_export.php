<?php
require __DIR__ . '/admin_bootstrap.php';
admin_require_auth();

$filters = admin_filters_from_request();
$statusOptions = admin_status_options();

try {
    $rows = SupabaseClient::fromEnv()->listRegistrations($filters, 5000, 0);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Không thể xuất Excel: ' . e($e->getMessage());
    exit;
}

$filename = 'magic-ticket-' . date('Ymd-His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
?>
<html>
<head>
<meta charset="utf-8">
<style>
  table{border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px}
  th{background:#8BC34A;color:#1f3210;font-weight:bold}
  th,td{border:1px solid #999;padding:6px;vertical-align:top}
  a{color:#0563c1}
</style>
</head>
<body>
<table>
  <thead>
    <tr>
      <th>Thời gian</th>
      <th>Họ tên</th>
      <th>Số điện thoại</th>
      <th>Link check-in</th>
      <th>Nền tảng</th>
      <th>Số ảnh</th>
      <th>Link ảnh</th>
      <th>Tên file ảnh</th>
      <th>Trạng thái</th>
      <th>ID</th>
      <th>User Agent</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <?php
      $links = is_array($row['link_anh'] ?? null) ? $row['link_anh'] : [];
      $names = is_array($row['ten_file_anh'] ?? null) ? $row['ten_file_anh'] : [];
      $status = (string)($row['status'] ?? '');
    ?>
    <tr>
      <td><?= e(admin_format_date($row['created_at'] ?? '')) ?></td>
      <td><?= e($row['ho_ten'] ?? '') ?></td>
      <td style="mso-number-format:'\@';"><?= e($row['so_dien_thoai'] ?? '') ?></td>
      <td><a href="<?= e($row['link_checkin'] ?? '') ?>"><?= e($row['link_checkin'] ?? '') ?></a></td>
      <td><?= e($row['platform'] ?? '') ?></td>
      <td><?= e((string)($row['so_anh'] ?? count($links))) ?></td>
      <td>
        <?php foreach ($links as $i => $url): ?>
          <a href="<?= e($url) ?>">Ảnh <?= $i + 1 ?></a><?= $i < count($links) - 1 ? '<br>' : '' ?>
        <?php endforeach; ?>
      </td>
      <td><?= e(implode("\n", $names)) ?></td>
      <td><?= e($statusOptions[$status] ?? $status) ?></td>
      <td><?= e($row['id'] ?? '') ?></td>
      <td><?= e($row['user_agent'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>

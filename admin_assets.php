<?php
require __DIR__ . '/admin_bootstrap.php';
admin_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: admin.php?view=media');
    exit;
}

$fields = admin_landing_asset_fields();
$assets = [];

foreach ($fields as $key => $_label) {
    $url = trim((string)($_POST['assets'][$key] ?? ''));
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        header('Location: admin.php?view=media&asset_error=' . rawurlencode('URL không hợp lệ: ' . $key));
        exit;
    }
    $assets[$key] = public_file_url($url);
}

try {
    DatabaseClient::fromEnv()->upsertLandingAssets($assets);
    header('Location: admin.php?view=media&assets=saved');
} catch (Throwable $e) {
    header('Location: admin.php?view=media&asset_error=' . rawurlencode($e->getMessage()));
}
exit;

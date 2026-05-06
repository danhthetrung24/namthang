<?php
require __DIR__ . '/admin_bootstrap.php';
admin_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$id = trim((string)($_POST['id'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));
$redirect = (string)($_POST['redirect'] ?? 'admin.php');
$allowed = array_keys(admin_status_options());

if (!in_array($status, $allowed, true)) {
    $status = 'new';
}

try {
    SupabaseClient::fromEnv()->updateRegistrationStatus($id, $status);
} catch (Throwable $e) {
    $separator = str_contains($redirect, '?') ? '&' : '?';
    $redirect .= $separator . 'error=' . rawurlencode($e->getMessage());
}

if (!str_starts_with($redirect, '/')) {
    $redirect = 'admin.php';
}

header('Location: ' . $redirect);
exit;

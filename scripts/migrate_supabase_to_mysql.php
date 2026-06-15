<?php
require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/SupabaseClient.php';

$dbName = $argv[1] ?? env_value('MYSQL_DATABASE', 'namthang_magic_ticket');
$dbHost = env_value('MYSQL_HOST', '127.0.0.1');
$dbPort = env_value('MYSQL_PORT', '3306');
$dbUser = env_value('MYSQL_USER', 'root');
$dbPass = env_value('MYSQL_PASSWORD', '');

function log_line(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function mysql_date(?string $value): string
{
    if (!$value) return date('Y-m-d H:i:s');
    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return date('Y-m-d H:i:s');
    }
}

function fetch_all_registrations(SupabaseClient $supabase): array
{
    $all = [];
    $offset = 0;
    $limit = 1000;

    do {
        $batch = $supabase->listRegistrations([], $limit, $offset);
        $all = array_merge($all, $batch);
        $offset += $limit;
    } while (count($batch) === $limit);

    return $all;
}

$supabase = SupabaseClient::fromEnv();
log_line('Reading Supabase data...');
$registrations = fetch_all_registrations($supabase);
$assets = $supabase->listLandingAssets();

$backupDir = __DIR__ . '/../storage/backup';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Cannot create backup directory: ' . $backupDir);
}

$backupFile = $backupDir . '/supabase_backup_' . date('Ymd_His') . '.json';
file_put_contents($backupFile, json_encode([
    'created_at' => date('c'),
    'source' => 'supabase',
    'registrations' => $registrations,
    'landing_page_assets' => $assets,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

log_line('Backup written: ' . $backupFile);

$serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);
$pdo = new PDO($serverDsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec(
    'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '` ' .
    'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
);
$pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS magic_ticket_registrations (
  id varchar(64) NOT NULL,
  created_at datetime NOT NULL,
  ho_ten varchar(255) NOT NULL,
  so_dien_thoai varchar(32) NOT NULL,
  link_checkin text NULL,
  platform varchar(100) NULL,
  user_agent text NULL,
  so_anh int NOT NULL DEFAULT 0,
  ten_file_anh json NOT NULL,
  link_anh json NOT NULL,
  status varchar(32) NOT NULL DEFAULT 'new',
  PRIMARY KEY (id),
  KEY idx_magic_ticket_created_at (created_at),
  KEY idx_magic_ticket_phone (so_dien_thoai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS landing_page_assets (
  asset_key varchar(191) NOT NULL,
  asset_url text NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (asset_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

$registrationStmt = $pdo->prepare(<<<SQL
INSERT INTO magic_ticket_registrations
  (id, created_at, ho_ten, so_dien_thoai, link_checkin, platform, user_agent, so_anh, ten_file_anh, link_anh, status)
VALUES
  (:id, :created_at, :ho_ten, :so_dien_thoai, :link_checkin, :platform, :user_agent, :so_anh, :ten_file_anh, :link_anh, :status)
ON DUPLICATE KEY UPDATE
  created_at = VALUES(created_at),
  ho_ten = VALUES(ho_ten),
  so_dien_thoai = VALUES(so_dien_thoai),
  link_checkin = VALUES(link_checkin),
  platform = VALUES(platform),
  user_agent = VALUES(user_agent),
  so_anh = VALUES(so_anh),
  ten_file_anh = VALUES(ten_file_anh),
  link_anh = VALUES(link_anh),
  status = VALUES(status)
SQL);

foreach ($registrations as $row) {
    $registrationStmt->execute([
        ':id' => (string)($row['id'] ?? ''),
        ':created_at' => mysql_date($row['created_at'] ?? null),
        ':ho_ten' => (string)($row['ho_ten'] ?? ''),
        ':so_dien_thoai' => (string)($row['so_dien_thoai'] ?? ''),
        ':link_checkin' => (string)($row['link_checkin'] ?? ''),
        ':platform' => (string)($row['platform'] ?? ''),
        ':user_agent' => (string)($row['user_agent'] ?? ''),
        ':so_anh' => (int)($row['so_anh'] ?? 0),
        ':ten_file_anh' => json_encode($row['ten_file_anh'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':link_anh' => json_encode($row['link_anh'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':status' => (string)($row['status'] ?? 'new'),
    ]);
}

$assetStmt = $pdo->prepare(<<<SQL
INSERT INTO landing_page_assets (asset_key, asset_url, updated_at)
VALUES (:asset_key, :asset_url, :updated_at)
ON DUPLICATE KEY UPDATE
  asset_url = VALUES(asset_url),
  updated_at = VALUES(updated_at)
SQL);

foreach ($assets as $row) {
    $assetStmt->execute([
        ':asset_key' => (string)($row['asset_key'] ?? ''),
        ':asset_url' => (string)($row['asset_url'] ?? ''),
        ':updated_at' => mysql_date($row['updated_at'] ?? null),
    ]);
}

$counts = [
    'magic_ticket_registrations' => (int)$pdo->query('SELECT COUNT(*) FROM magic_ticket_registrations')->fetchColumn(),
    'landing_page_assets' => (int)$pdo->query('SELECT COUNT(*) FROM landing_page_assets')->fetchColumn(),
];

log_line('Database ready: ' . $dbName);
log_line('Imported registrations: ' . count($registrations));
log_line('Imported landing assets: ' . count($assets));
log_line('Current table counts: ' . json_encode($counts, JSON_UNESCAPED_SLASHES));

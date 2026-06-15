<?php

$registrationsCsv = $argv[1] ?? 'C:/Users/Admin/Desktop/magic_ticket_registrations_rows.csv';
$assetsCsv = $argv[2] ?? 'C:/Users/Admin/Desktop/landing_page_assets_rows.csv';

$host = getenv('SQLSRV_HOST') ?: '160.191.175.132';
$port = getenv('SQLSRV_PORT') ?: '1433';
$database = getenv('SQLSRV_DATABASE') ?: 'namthang_magic_ticket';
$username = getenv('SQLSRV_USERNAME') ?: '';
$password = getenv('SQLSRV_PASSWORD') ?: '';

if ($username === '' || $password === '') {
    fwrite(STDERR, "Set SQLSRV_USERNAME and SQLSRV_PASSWORD before running this script.\n");
    fwrite(STDERR, "Example PowerShell:\n");
    fwrite(STDERR, "\$env:SQLSRV_USERNAME='sa'; \$env:SQLSRV_PASSWORD='your-password'; C:\\xampp\\php\\php.exe scripts\\import_csv_to_sqlsrv.php\n");
    exit(2);
}

function log_line(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function sqlsrv_connect_db(string $host, string $port, string $database, string $username, string $password): PDO
{
    $server = str_contains($host, ',') ? $host : $host . ',' . $port;
    $dsn = 'sqlsrv:Server=' . $server . ';Database=' . $database . ';TrustServerCertificate=1';
    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function csv_rows(string $file): Generator
{
    if (!is_readable($file)) {
        throw new RuntimeException('CSV not readable: ' . $file);
    }

    $handle = fopen($file, 'r');
    if (!$handle) throw new RuntimeException('Cannot open CSV: ' . $file);

    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        return;
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) === 1 && trim((string)$row[0]) === '') continue;
        yield array_combine($headers, array_pad($row, count($headers), ''));
    }

    fclose($handle);
}

function sql_datetime(?string $value): string
{
    if (!$value) return gmdate('Y-m-d H:i:s.u');
    return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
}

$master = sqlsrv_connect_db($host, $port, 'master', $username, $password);
$quotedDb = str_replace(']', ']]', $database);
$master->exec("IF DB_ID(N'{$quotedDb}') IS NULL CREATE DATABASE [{$quotedDb}]");

$pdo = sqlsrv_connect_db($host, $port, $database, $username, $password);
$pdo->exec(<<<SQL
IF OBJECT_ID(N'dbo.magic_ticket_registrations', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.magic_ticket_registrations (
    id NVARCHAR(64) NOT NULL PRIMARY KEY,
    created_at DATETIME2 NOT NULL DEFAULT SYSUTCDATETIME(),
    ho_ten NVARCHAR(255) NOT NULL,
    so_dien_thoai NVARCHAR(32) NOT NULL,
    link_checkin NVARCHAR(MAX) NULL,
    platform NVARCHAR(100) NULL,
    user_agent NVARCHAR(MAX) NULL,
    so_anh INT NOT NULL DEFAULT 0,
    ten_file_anh NVARCHAR(MAX) NOT NULL DEFAULT N'[]',
    link_anh NVARCHAR(MAX) NOT NULL DEFAULT N'[]',
    status NVARCHAR(32) NOT NULL DEFAULT N'new',
    CONSTRAINT chk_magic_ticket_ten_file_anh_json CHECK (ISJSON(ten_file_anh) = 1),
    CONSTRAINT chk_magic_ticket_link_anh_json CHECK (ISJSON(link_anh) = 1)
  );
  CREATE INDEX idx_magic_ticket_created_at ON dbo.magic_ticket_registrations(created_at DESC);
  CREATE INDEX idx_magic_ticket_phone ON dbo.magic_ticket_registrations(so_dien_thoai);
END;

IF OBJECT_ID(N'dbo.landing_page_assets', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.landing_page_assets (
    asset_key NVARCHAR(191) NOT NULL PRIMARY KEY,
    asset_url NVARCHAR(MAX) NOT NULL DEFAULT N'',
    updated_at DATETIME2 NOT NULL DEFAULT SYSUTCDATETIME()
  );
END;
SQL);

$registrationSql = <<<SQL
MERGE dbo.magic_ticket_registrations AS target
USING (SELECT ? AS id) AS source
ON target.id = source.id
WHEN MATCHED THEN UPDATE SET
  created_at = ?,
  ho_ten = ?,
  so_dien_thoai = ?,
  link_checkin = ?,
  platform = ?,
  user_agent = ?,
  so_anh = ?,
  ten_file_anh = ?,
  link_anh = ?,
  status = ?
WHEN NOT MATCHED THEN INSERT
  (id, created_at, ho_ten, so_dien_thoai, link_checkin, platform, user_agent, so_anh, ten_file_anh, link_anh, status)
VALUES
  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
SQL;
$registrationStmt = $pdo->prepare($registrationSql);

$registrationCount = 0;
foreach (csv_rows($registrationsCsv) as $row) {
    $params = [
        $row['id'],
        sql_datetime($row['created_at'] ?? null),
        $row['ho_ten'] ?? '',
        $row['so_dien_thoai'] ?? '',
        ($row['link_checkin'] ?? '') !== '' ? $row['link_checkin'] : null,
        ($row['platform'] ?? '') !== '' ? $row['platform'] : null,
        ($row['user_agent'] ?? '') !== '' ? $row['user_agent'] : null,
        (int)($row['so_anh'] ?? 0),
        ($row['ten_file_anh'] ?? '') !== '' ? $row['ten_file_anh'] : '[]',
        ($row['link_anh'] ?? '') !== '' ? $row['link_anh'] : '[]',
        ($row['status'] ?? '') !== '' ? $row['status'] : 'new',
        $row['id'],
        sql_datetime($row['created_at'] ?? null),
        $row['ho_ten'] ?? '',
        $row['so_dien_thoai'] ?? '',
        ($row['link_checkin'] ?? '') !== '' ? $row['link_checkin'] : null,
        ($row['platform'] ?? '') !== '' ? $row['platform'] : null,
        ($row['user_agent'] ?? '') !== '' ? $row['user_agent'] : null,
        (int)($row['so_anh'] ?? 0),
        ($row['ten_file_anh'] ?? '') !== '' ? $row['ten_file_anh'] : '[]',
        ($row['link_anh'] ?? '') !== '' ? $row['link_anh'] : '[]',
        ($row['status'] ?? '') !== '' ? $row['status'] : 'new',
    ];
    $registrationStmt->execute($params);
    $registrationCount++;
}

$assetSql = <<<SQL
MERGE dbo.landing_page_assets AS target
USING (SELECT ? AS asset_key) AS source
ON target.asset_key = source.asset_key
WHEN MATCHED THEN UPDATE SET
  asset_url = ?,
  updated_at = ?
WHEN NOT MATCHED THEN INSERT
  (asset_key, asset_url, updated_at)
VALUES
  (?, ?, ?);
SQL;
$assetStmt = $pdo->prepare($assetSql);

$assetCount = 0;
foreach (csv_rows($assetsCsv) as $row) {
    $assetStmt->execute([
        $row['asset_key'],
        $row['asset_url'] ?? '',
        sql_datetime($row['updated_at'] ?? null),
        $row['asset_key'],
        $row['asset_url'] ?? '',
        sql_datetime($row['updated_at'] ?? null),
    ]);
    $assetCount++;
}

$dbCounts = [
    'magic_ticket_registrations' => (int)$pdo->query('SELECT COUNT(*) FROM dbo.magic_ticket_registrations')->fetchColumn(),
    'landing_page_assets' => (int)$pdo->query('SELECT COUNT(*) FROM dbo.landing_page_assets')->fetchColumn(),
];

log_line('SQL Server database ready: ' . $database);
log_line('Imported registrations from CSV: ' . $registrationCount);
log_line('Imported landing assets from CSV: ' . $assetCount);
log_line('Current table counts: ' . json_encode($dbCounts, JSON_UNESCAPED_SLASHES));

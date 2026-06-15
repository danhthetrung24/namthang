<?php
final class SqlServerClient
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function fromEnv(): self
    {
        $host = env_value('SQLSRV_HOST');
        $port = env_value('SQLSRV_PORT', '1433');
        $database = env_value('SQLSRV_DATABASE', 'namthang_magic_ticket');
        $username = env_value('SQLSRV_USERNAME');
        $password = env_value('SQLSRV_PASSWORD');

        if (!$host || !$username || $password === null) {
            throw new RuntimeException('Thiếu SQLSRV_HOST, SQLSRV_USERNAME hoặc SQLSRV_PASSWORD trong .env.');
        }

        $server = str_contains($host, ',') ? $host : $host . ',' . $port;
        $dsn = 'sqlsrv:Server=' . $server . ';Database=' . $database . ';TrustServerCertificate=1';
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return new self($pdo);
    }

    public function findRegistrationByPhone(string $phone): array
    {
        $stmt = $this->pdo->prepare('SELECT TOP 1 id, created_at, so_dien_thoai FROM dbo.magic_ticket_registrations WHERE so_dien_thoai = ?');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();
        return $row ? [$this->hydrateRegistration($row)] : [];
    }

    public function insertRegistration(array $data): array
    {
        $stmt = $this->pdo->prepare(<<<SQL
INSERT INTO dbo.magic_ticket_registrations
  (id, created_at, ho_ten, so_dien_thoai, link_checkin, platform, user_agent, so_anh, ten_file_anh, link_anh, status)
OUTPUT inserted.*
VALUES
  (?, SYSUTCDATETIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL);
        $stmt->execute([
            $data['id'],
            $data['ho_ten'],
            $data['so_dien_thoai'],
            $data['link_checkin'] !== '' ? $data['link_checkin'] : null,
            $data['platform'] ?? null,
            $data['user_agent'] ?? null,
            (int)($data['so_anh'] ?? 0),
            json_encode($data['ten_file_anh'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($data['link_anh'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $data['status'] ?? 'new',
        ]);
        $row = $stmt->fetch();
        return $row ? [$this->hydrateRegistration($row)] : [];
    }

    public function listRegistrations(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);
        $where = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(ho_ten LIKE ? OR so_dien_thoai LIKE ? OR link_checkin LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
            array_push($params, $like, $like, $like);
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            $value = trim((string)($filters[$key] ?? ''));
            if ($value === '') continue;
            $time = $key === 'from' ? '00:00:00' : '23:59:59';
            $dt = new DateTimeImmutable($value . ' ' . $time, new DateTimeZone('Asia/Ho_Chi_Minh'));
            $where[] = 'created_at ' . $operator . ' ?';
            $params[] = $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }

        $sql = 'SELECT * FROM dbo.magic_ticket_registrations';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($row) => $this->hydrateRegistration($row), $stmt->fetchAll());
    }

    public function updateRegistrationStatus(string $id, string $status): array
    {
        if (!preg_match('/^[a-f0-9-]{16,64}$/i', $id)) {
            throw new RuntimeException('ID không hợp lệ.');
        }

        $stmt = $this->pdo->prepare('UPDATE dbo.magic_ticket_registrations SET status = ? OUTPUT inserted.* WHERE id = ?');
        $stmt->execute([$status, $id]);
        $row = $stmt->fetch();
        return $row ? [$this->hydrateRegistration($row)] : [];
    }

    public function listLandingAssets(): array
    {
        return $this->pdo->query('SELECT asset_key, asset_url, updated_at FROM dbo.landing_page_assets ORDER BY asset_key ASC')->fetchAll();
    }

    public function upsertLandingAssets(array $assets): array
    {
        $stmt = $this->pdo->prepare(<<<SQL
MERGE dbo.landing_page_assets AS target
USING (SELECT ? AS asset_key) AS source
ON target.asset_key = source.asset_key
WHEN MATCHED THEN UPDATE SET asset_url = ?, updated_at = SYSUTCDATETIME()
WHEN NOT MATCHED THEN INSERT (asset_key, asset_url, updated_at) VALUES (?, ?, SYSUTCDATETIME());
SQL);

        foreach ($assets as $key => $url) {
            $stmt->execute([(string)$key, trim((string)$url), (string)$key, trim((string)$url)]);
        }

        return $this->listLandingAssets();
    }

    private function hydrateRegistration(array $row): array
    {
        foreach (['ten_file_anh', 'link_anh'] as $key) {
            $value = $row[$key] ?? [];
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $row[$key] = is_array($decoded) ? $decoded : [];
            }
        }

        if (isset($row['created_at']) && $row['created_at'] instanceof DateTimeInterface) {
            $row['created_at'] = $row['created_at']->format(DateTimeInterface::ATOM);
        }

        return $row;
    }
}

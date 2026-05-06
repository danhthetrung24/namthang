<?php
final class SupabaseClient
{
    private string $url;
    private string $key;

    public function __construct(string $url, string $key)
    {
        $this->url = rtrim($url, '/');
        $this->url = preg_replace('#/(rest|storage)/v1$#', '', $this->url);
        $this->key = $key;
    }

    public static function fromEnv(): self
    {
        $url = env_value('SUPABASE_URL') ?: env_value('VITE_SUPABASE_URL');
        $key = env_value('SUPABASE_SERVICE_ROLE_KEY') ?: env_value('SUPABASE_ANON_KEY') ?: env_value('VITE_SUPABASE_ANON_KEY');

        if (!$url || !$key) {
            throw new RuntimeException('Thiếu SUPABASE_URL hoặc SUPABASE_SERVICE_ROLE_KEY trong file .env.');
        }

        return new self($url, $key);
    }

    /** Kiểm tra số điện thoại đã đăng ký chưa. Dùng cho submit.php để chặn đăng ký trùng. */
    public function findRegistrationByPhone(string $phone): array
    {
        $query = '/rest/v1/magic_ticket_registrations'
            . '?select=id,created_at,so_dien_thoai'
            . '&so_dien_thoai=eq.' . rawurlencode($phone)
            . '&limit=1';

        return $this->request('GET', $query);
    }

    public function insertRegistration(array $data): array
    {
        return $this->request('POST', '/rest/v1/magic_ticket_registrations', $data, [
            'Prefer: return=representation',
        ]);
    }

    /** Lấy danh sách đăng ký cho trang admin. $filters hỗ trợ: q, status, from, to. */
    public function listRegistrations(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);

        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => (string)$limit,
            'offset' => (string)$offset,
        ];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $safeQ = $this->escapeLikeValue($q);
            $params['or'] = '(ho_ten.ilike.*' . $safeQ . '*,so_dien_thoai.ilike.*' . $safeQ . '*,link_checkin.ilike.*' . $safeQ . '*)';
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $params['status'] = 'eq.' . $status;
        }

        $query = $this->buildQuery($params);

        $from = trim((string)($filters['from'] ?? ''));
        if ($from !== '') {
            $query .= '&created_at=gte.' . rawurlencode($from . 'T00:00:00+07:00');
        }

        $to = trim((string)($filters['to'] ?? ''));
        if ($to !== '') {
            $query .= '&created_at=lte.' . rawurlencode($to . 'T23:59:59+07:00');
        }

        return $this->request('GET', '/rest/v1/magic_ticket_registrations?' . $query);
    }

    /** Cập nhật trạng thái trên trang admin. */
    public function updateRegistrationStatus(string $id, string $status): array
    {
        if (!preg_match('/^[a-f0-9-]{16,64}$/i', $id)) {
            throw new RuntimeException('ID không hợp lệ.');
        }

        return $this->request(
            'PATCH',
            '/rest/v1/magic_ticket_registrations?id=eq.' . rawurlencode($id),
            ['status' => $status],
            ['Prefer: return=representation']
        );
    }

    public function uploadObject(string $bucket, string $path, string $bytes, string $contentType): array
    {
        $endpoint = '/storage/v1/object/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode($path));
        return $this->request('POST', $endpoint, $bytes, [
            'Content-Type: ' . $contentType,
            'x-upsert: false',
        ], false);
    }

    public function publicUrl(string $bucket, string $path): string
    {
        return $this->url . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode($path));
    }

    private function buildQuery(array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) continue;
            $parts[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
        }
        return implode('&', $parts);
    }

    private function escapeLikeValue(string $value): string
    {
        return trim(str_replace(['*', ',', '(', ')'], ' ', $value));
    }

    private function request(string $method, string $path, array|string $payload = '', array $extraHeaders = [], bool $json = true): array
    {
        $ch = curl_init($this->url . $path);
        if (!$ch) throw new RuntimeException('Không thể khởi tạo cURL.');

        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
        ];

        $hasBody = !in_array(strtoupper($method), ['GET', 'HEAD'], true);
        $body = '';

        if ($hasBody) {
            if ($json) {
                $headers[] = 'Content-Type: application/json; charset=utf-8';
                $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $body = (string)$payload;
            }
        }

        $headers = array_merge($headers, $extraHeaders);

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 45,
        ];

        if ($hasBody) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Lỗi kết nối Supabase: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            $message = $response;
            $decoded = json_decode($response, true);
            if (is_array($decoded) && isset($decoded['message'])) $message = $decoded['message'];
            throw new RuntimeException('Supabase trả lỗi HTTP ' . $status . ': ' . $message);
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : ['raw' => $response];
    }
}

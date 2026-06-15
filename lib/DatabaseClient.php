<?php
require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/SqlServerClient.php';

final class DatabaseClient
{
    public static function fromEnv(): SupabaseClient|SqlServerClient
    {
        $driver = strtolower((string)env_value('DB_DRIVER', ''));
        if ($driver === 'sqlsrv' || ($driver === '' && env_value('SQLSRV_HOST'))) {
            return SqlServerClient::fromEnv();
        }

        return SupabaseClient::fromEnv();
    }

    public static function isSqlServer(): bool
    {
        $driver = strtolower((string)env_value('DB_DRIVER', ''));
        return $driver === 'sqlsrv' || ($driver === '' && env_value('SQLSRV_HOST'));
    }
}

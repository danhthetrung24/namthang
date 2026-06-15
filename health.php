<?php
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/DatabaseClient.php';

$driver = strtolower((string)env_value('DB_DRIVER', ''));
$sqlsrvHost = env_value('SQLSRV_HOST');
$databaseClass = null;
$databaseOk = false;
$databaseError = null;
$counts = null;

try {
    $database = DatabaseClient::fromEnv();
    $databaseClass = get_class($database);
    $databaseOk = true;
    if ($database instanceof SqlServerClient) {
        $counts = [
            'registrations' => count($database->listRegistrations([], 100, 0)),
            'assets' => count($database->listLandingAssets()),
        ];
    }
} catch (Throwable $e) {
    $databaseError = $e->getMessage();
}

json_response([
    'ok' => $databaseOk,
    'dbDriver' => $driver,
    'hasSqlsrvHost' => $sqlsrvHost !== null && $sqlsrvHost !== '',
    'databaseClass' => $databaseClass,
    'databaseError' => $databaseError,
    'counts' => $counts,
    'appBaseUrl' => env_value('APP_BASE_URL'),
]);

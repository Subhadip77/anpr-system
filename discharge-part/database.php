<?php
declare(strict_types=1);

$dbHost = 'localhost';
$dbName = 'nursing_discharge';
$dbUser = 'root';
$dbPassword = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Discharge database connection failed. Import discharge-part/schema.sql and check the database settings.');
}

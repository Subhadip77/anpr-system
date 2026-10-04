<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('A valid discharge record is required.');
}
$statement = $pdo->prepare('DELETE FROM discharge_records WHERE id = ?');
$statement->execute([$id]);
header('Location: history.php');
exit;

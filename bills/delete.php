<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: history.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('A valid bill is required.');
}

$pdo->beginTransaction();
try {
    foreach (['bill_diagnoses', 'bill_items', 'bill_summary_items'] as $table) {
        $statement = $pdo->prepare("DELETE FROM {$table} WHERE bill_id = ?");
        $statement->execute([$id]);
    }
    $statement = $pdo->prepare('DELETE FROM bills WHERE id = ?');
    $statement->execute([$id]);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
header('Location: history.php');
exit;
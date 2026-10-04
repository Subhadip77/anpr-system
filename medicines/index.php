<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $name = trim((string) ($_POST['medicine_name'] ?? ''));
    try {
        if ($action === 'delete' && $id) {
            $pdo->prepare('DELETE FROM discharge_medicines WHERE id = ?')->execute([$id]);
            $message = 'Medicine removed.';
        } elseif (in_array($action, ['add', 'update'], true) && $name !== '') {
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO discharge_medicines (medicine_name) VALUES (?)')->execute([$name]);
                $message = 'Medicine added.';
            } elseif ($id) {
                $pdo->prepare('UPDATE discharge_medicines SET medicine_name = ? WHERE id = ?')->execute([$name, $id]);
                $message = 'Medicine updated.';
            }
        } else {
            $error = 'Enter a medicine name.';
        }
    } catch (PDOException $exception) {
        $error = str_contains($exception->getMessage(), 'Duplicate') ? 'That medicine is already in the library.' : 'Medicine could not be saved.';
    }
}
$medicines = $pdo->query('SELECT id, medicine_name FROM discharge_medicines ORDER BY medicine_name')->fetchAll();
$pageTitle = 'Medicine Library';
require_once '../includes/header.php';
?>
<section class="card">
  <div class="title-row"><div><h1>Medicine library</h1><p>Keep reusable medicine names here for discharge sheets.</p></div><a class="button" href="../discharge/create.php">Create discharge sheet</a></div>
  <?php if ($message): ?><p class="alert success-alert"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
  <form class="library-add" method="post"><input type="hidden" name="action" value="add"><label>New medicine name<input name="medicine_name" required placeholder="e.g. Rabeprazole 20 mg"></label><button type="submit">Add medicine</button></form>
  <div class="table-scroll"><table><thead><tr><th>Medicine name</th><th>Actions</th></tr></thead><tbody><?php foreach ($medicines as $medicine): ?><tr><td><form class="inline-form" method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $medicine['id'] ?>"><input name="medicine_name" value="<?= e($medicine['medicine_name']) ?>" required><button type="submit" class="outline">Save</button></form></td><td><form method="post" onsubmit="return confirm('Remove this medicine from the library?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $medicine['id'] ?>"><button type="submit" class="delete-button">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$medicines): ?><tr><td colspan="2">No medicines added yet.</td></tr><?php endif; ?></tbody></table></div>
</section>
<?php require_once '../includes/footer.php'; ?>

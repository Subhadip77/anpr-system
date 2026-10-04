<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'database.php';
require_once 'helpers.php';

$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 10;
$where = $search === '' ? '' : ' WHERE patient_name LIKE :search OR record_number LIKE :search';
$countStatement = $pdo->prepare('SELECT COUNT(*) FROM discharge_records' . $where);
if ($search !== '') $countStatement->bindValue(':search', '%' . $search . '%');
$countStatement->execute();
$totalRecords = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$statement = $pdo->prepare('SELECT id, record_number, patient_name, discharge_date, discharge_time FROM discharge_records' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
if ($search !== '') $statement->bindValue(':search', '%' . $search . '%');
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$records = $statement->fetchAll();
$pageTitle = 'Discharge Records';
require_once 'layout.php';
function dischargeHistoryUrl(int $page, string $search): string
{
    return 'history.php?' . http_build_query(array_filter(['page' => $page, 'q' => $search], static fn($value) => $value !== ''));
}
?>
<section class="discharge-card">
  <div class="title-row"><div><h1>Discharge records</h1><p class="muted">Patient discharge records created by the clinical team.</p></div><a class="button" href="create.php">New discharge</a></div>
  <form class="search-row" method="get"><label>Patient or record number<input name="q" value="<?= e($search) ?>" placeholder="Search records"></label><button type="submit">Search</button><?php if ($search !== ''): ?><a class="button outline" href="history.php">Clear</a><?php endif; ?></form>
  <div class="table-scroll"><table><thead><tr><th>Record no.</th><th>Patient name</th><th>Discharge date</th><th>Time</th><th>Actions</th></tr></thead><tbody><?php foreach ($records as $item): ?><tr><td><?= e($item['record_number']) ?></td><td><?= e($item['patient_name']) ?></td><td><?= e(displayDate($item['discharge_date'])) ?></td><td><?= e($item['discharge_time']) ?></td><td class="record-actions"><a href="view.php?id=<?= $item['id'] ?>">View</a><a href="print.php?id=<?= $item['id'] ?>" target="_blank">Print</a><a href="create.php?id=<?= $item['id'] ?>">Edit</a><a href="delete.php?id=<?= $item['id'] ?>" onclick="return confirm('Delete this discharge record? This cannot be undone.');">Delete</a></td></tr><?php endforeach; ?><?php if (!$records): ?><tr><td colspan="5">No discharge records found.</td></tr><?php endif; ?></tbody></table></div>
  <?php if ($totalRecords > 0): ?><div class="pagination"><span>Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?></span><div><?php if ($page > 1): ?><a href="<?= e(dischargeHistoryUrl($page - 1, $search)) ?>">Previous</a><?php endif; ?><?php if ($page < $totalPages): ?><a href="<?= e(dischargeHistoryUrl($page + 1, $search)) ?>">Next</a><?php endif; ?></div></div><?php endif; ?>
</section>
<?php require_once 'footer.php'; ?>

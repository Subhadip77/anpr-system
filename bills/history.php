<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';

$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 10;
$where = $search === '' ? '' : ' WHERE patient_name LIKE :search';

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM bills' . $where);
if ($search !== '') $countStatement->bindValue(':search', '%' . $search . '%');
$countStatement->execute();
$totalBills = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalBills / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$billStatement = $pdo->prepare('SELECT id, bill_number, invoice_no, bill_date, patient_name, net_amount, balance_amount FROM bills' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
if ($search !== '') $billStatement->bindValue(':search', '%' . $search . '%');
$billStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$billStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$billStatement->execute();
$bills = $billStatement->fetchAll();
$pageTitle = 'Bill History';
require_once '../includes/header.php';

function historyUrl(int $page, string $search): string
{
		return 'history.php?' . http_build_query(array_filter(['page' => $page, 'q' => $search], fn($value) => $value !== ''));
}
?>
<section class="card">
	<div class="title-row"><h1>Bill history</h1><a class="button" href="create.php">New Bill</a></div>
	<form class="history-search" method="get"><label for="patientSearch">Patient Name</label><input id="patientSearch" name="q" value="<?= e($search) ?>" placeholder="Search patient name"><button type="submit">Search</button><?php if ($search !== ''): ?><a class="outline-link" href="history.php">Clear</a><?php endif; ?></form>
	<div class="table-scroll"><table><thead><tr><th>Bill no.</th><th>Invoice</th><th>Date</th><th>Patient Name</th><th>Net amount</th><th>Due</th><th>Actions</th></tr></thead><tbody><?php foreach ($bills as $bill): ?><tr><td><?= e($bill['bill_number']) ?></td><td><?= e($bill['invoice_no']) ?></td><td><?= e($bill['bill_date']) ?></td><td><?= e($bill['patient_name']) ?></td><td><?= number_format((float) $bill['net_amount'], 2) ?></td><td><?= number_format((float) $bill['balance_amount'], 2) ?></td><td class="bill-actions"><a href="view.php?id=<?= $bill['id'] ?>">View</a><a href="print.php?id=<?= $bill['id'] ?>" target="_blank">Print</a><a href="create.php?id=<?= $bill['id'] ?>">Edit</a><form action="delete.php" method="post" onsubmit="return confirm('Delete bill <?= e($bill['bill_number']) ?>? This cannot be undone.');"><input type="hidden" name="id" value="<?= $bill['id'] ?>"><button type="submit" class="delete-button">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$bills): ?><tr><td colspan="7">No bills found.</td></tr><?php endif; ?></tbody></table></div>
	<?php if ($totalBills > 0): ?><nav class="pagination" aria-label="Bill history pages"><span>Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalBills) ?> of <?= $totalBills ?></span><div><?php if ($page > 1): ?><a class="outline-link" href="<?= e(historyUrl($page - 1, $search)) ?>">Previous</a><?php endif; ?><?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?><a class="page-link<?= $pageNumber === $page ? ' current' : '' ?>" href="<?= e(historyUrl($pageNumber, $search)) ?>"><?= $pageNumber ?></a><?php endfor; ?><?php if ($page < $totalPages): ?><a class="outline-link" href="<?= e(historyUrl($page + 1, $search)) ?>">Next</a><?php endif; ?></div></nav><?php endif; ?>
</section>
<?php require_once '../includes/footer.php'; ?>
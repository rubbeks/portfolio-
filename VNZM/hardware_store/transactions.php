<?php
require 'auth.php';
require 'db.php';

// Void transaction
if (isset($_GET['void'])) {
    $id = (int) $_GET['void'];
    $pdo->prepare("UPDATE transactions SET status='voided' WHERE id=?")->execute([$id]);
    header('Location: transactions.php?voided=1');
    exit;
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query("
        SELECT t.receipt_number, t.customer_name, t.notes, t.total_amount, t.status, t.created_at,
               GROUP_CONCAT(p.name, ' x', ti.quantity ORDER BY p.name SEPARATOR ' | ') AS items
        FROM transactions t
        LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
        LEFT JOIN products p ON p.id = ti.product_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ")->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Receipt #', 'Customer', 'Notes', 'Items', 'Total', 'Status', 'Date']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['receipt_number'],
            $r['customer_name'],
            $r['notes'],
            $r['items'],
            $r['total_amount'],
            $r['status'],
            $r['created_at']
        ]);
    }
    fclose($out);
    exit;
}

// Search/filter
$search   = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

$where  = ["t.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(t.customer_name LIKE ? OR t.receipt_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dateFrom) { $where[] = "DATE(t.created_at) >= ?"; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = "DATE(t.created_at) <= ?"; $params[] = $dateTo; }

$sql  = "SELECT t.* FROM transactions t WHERE " . implode(" AND ", $where) . " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$totalSql = "SELECT SUM(total_amount) FROM transactions t WHERE " . implode(" AND ", $where);
$grandTotal = $pdo->prepare($totalSql);
$grandTotal->execute($params);
$grandTotal = $grandTotal->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:#00c896; font-weight:700;">Transaction Log</h4>
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                ⬇ Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="background:#111a14; border:1px solid #1e3a2a;">
                <li><a class="dropdown-item" href="transactions.php?export=csv" style="color:#e8f5e9;">📄 Export CSV</a></li>
                <li><a class="dropdown-item" href="export_print.php" target="_blank" style="color:#e8f5e9;">🖨 Print List</a></li>
                <li><a class="dropdown-item" href="#" onclick="downloadPDF()" style="color:#e8f5e9;">📑 Save as PDF</a></li>
            </ul>
        </div>
    </div>

    <?php if (isset($_GET['voided'])): ?>
        <div class="alert alert-success mb-3">Transaction voided.</div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search customer</label>
                <input type="text" name="search" class="form-control" placeholder="Customer name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="transactions.php" class="btn btn-outline-light w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span style="color:#4a7a5a; font-size:0.9rem;"><?= count($transactions) ?> transaction(s)</span>
        <span style="color:#00c896; font-weight:700; font-size:1.1rem;">
            Total: ₱<?= number_format($grandTotal ?? 0, 2) ?>
        </span>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Customer</th>
                        <th>Notes</th>
                        <th>Total</th>
                        <th>Date & Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" class="text-center" style="color:#4a7a5a; padding:2rem;">No transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td style="color:#00c896; font-weight:600;"><?= htmlspecialchars($t['receipt_number'] ?? '#'.$t['id']) ?></td>
                        <td><?= htmlspecialchars($t['customer_name']) ?></td>
                        <td style="color:#4a7a5a; font-size:0.82rem;"><?= htmlspecialchars($t['notes'] ?? '—') ?></td>
                        <td style="color:#00c896; font-weight:600;">₱<?= number_format($t['total_amount'], 2) ?></td>
                        <td style="color:#4a7a5a; font-size:0.85rem;"><?= date('M d, Y h:i A', strtotime($t['created_at'])) ?></td>
                        <td class="d-flex gap-1">
                            <a href="transaction_view.php?id=<?= $t['id'] ?>" class="btn btn-outline-light btn-sm">View</a>
                            <a href="transactions.php?void=<?= $t['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Void receipt <?= htmlspecialchars($t['receipt_number'] ?? '#'.$t['id']) ?>? This cannot be undone.')">Void</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Logo text header
    doc.setFontSize(16);
    doc.setTextColor(0, 168, 120);
    doc.text('VNZM Hardware & Construction Supplies', 14, 18);
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text('Transaction Report — Generated: ' + new Date().toLocaleString(), 14, 25);

    // Table data from the DOM
    const rows = [];
    document.querySelectorAll('tbody tr').forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length >= 5) {
            rows.push([
                cells[0].textContent.trim(),
                cells[1].textContent.trim(),
                cells[2].textContent.trim(),
                cells[3].textContent.trim(),
                cells[4].textContent.trim(),
            ]);
        }
    });

    doc.autoTable({
        startY: 30,
        head: [['Receipt #', 'Customer', 'Notes', 'Total', 'Date & Time']],
        body: rows,
        headStyles: { fillColor: [10, 46, 30], textColor: [0, 200, 150], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245, 250, 247] },
        styles: { fontSize: 9, cellPadding: 4 },
        columnStyles: { 3: { textColor: [0, 168, 120], fontStyle: 'bold' } }
    });

    // Grand total
    const totalY = doc.lastAutoTable.finalY + 8;
    doc.setFontSize(11);
    doc.setTextColor(0, 168, 120);
    doc.text('Grand Total: <?= '₱' . number_format($grandTotal ?? 0, 2) ?>', 14, totalY);

    doc.save('transactions_<?= date('Y-m-d') ?>.pdf');
}
</script>
<script src="assets/progress.js"></script>
</body>
</html>

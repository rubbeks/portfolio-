<?php
require 'auth.php';
require 'db.php';

$isPdf = isset($_GET['pdf']);

$transactions = $pdo->query("
    SELECT * FROM transactions
    WHERE status = 'active'
    ORDER BY created_at DESC
")->fetchAll();

$grandTotal = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE status='active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction Report — <?= date('M d, Y') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            color: #111;
            padding: 2rem;
            font-size: 13px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #00a878;
            padding-bottom: 1rem;
        }
        .header img { height: 60px; }
        .header-text h2 { font-size: 1.2rem; color: #00a878; font-weight: 700; }
        .header-text p  { font-size: 0.8rem; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        thead th {
            background: #0a2e1e;
            color: #00c896;
            padding: 8px 10px;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr:nth-child(even) { background: #f5faf7; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #ddd; }
        .total-row td {
            font-weight: 700;
            font-size: 1rem;
            border-top: 2px solid #00a878;
            padding-top: 10px;
        }
        .total-amount { color: #00a878; }
        .footer { margin-top: 2rem; font-size: 0.78rem; color: #888; text-align: center; }
        .no-print { margin-bottom: 1rem; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="no-print" style="display:flex; gap:0.5rem;">
    <button onclick="window.print()"
        style="background:#00c896;border:none;color:#0a0f0a;padding:0.5rem 1.2rem;border-radius:8px;font-weight:600;cursor:pointer;">
        🖨 Print / Save as PDF
    </button>
    <button onclick="window.close()"
        style="background:#1e3a2a;border:1px solid #1e3a2a;color:#a5d6a7;padding:0.5rem 1.2rem;border-radius:8px;cursor:pointer;">
        ✕ Close
    </button>
</div>

<div class="header">
    <img src="logo/VNZM 2x4.png" alt="Logo">
    <div class="header-text">
        <h2>Transaction Report</h2>
        <p>Generated: <?= date('F d, Y h:i A') ?> &nbsp;|&nbsp; <?= count($transactions) ?> transaction(s)</p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Receipt #</th>
            <th>Customer</th>
            <th>Notes</th>
            <th>Total</th>
            <th>Date & Time</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['receipt_number'] ?? '#'.$t['id']) ?></td>
            <td><?= htmlspecialchars($t['customer_name']) ?></td>
            <td><?= htmlspecialchars($t['notes'] ?? '—') ?></td>
            <td>₱<?= number_format($t['total_amount'], 2) ?></td>
            <td><?= date('M d, Y h:i A', strtotime($t['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="3">Grand Total</td>
            <td class="total-amount">₱<?= number_format($grandTotal ?? 0, 2) ?></td>
            <td></td>
        </tr>
    </tbody>
</table>

<div class="footer">VNZM Hardware & Construction Supplies — <?= date('Y') ?></div>

<?php if ($isPdf): ?>
<script>window.onload = () => window.print();</script>
<?php endif; ?>
</body>
</html>

<?php
require 'auth.php';
require 'db.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: transactions.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$transaction = $stmt->fetch();
if (!$transaction) { header('Location: transactions.php'); exit; }

$items = $pdo->prepare("
    SELECT ti.*, p.name AS product_name, p.unit, p.image
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    WHERE ti.transaction_id = ?
");
$items->execute([$id]);
$items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $id ?> — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            nav { display: none !important; }
            #progress-toast { display: none !important; }
            body {
                background: #fff !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .receipt-box {
                border: 1px solid #ccc !important;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 1rem !important;
            }
            .receipt-box * {
                color: #000 !important;
                border-color: #ccc !important;
                background: transparent !important;
            }
            .receipt-box img {
                display: block !important;
                max-width: 200px !important;
            }
            table { width: 100% !important; }
            td, th { padding: 6px 8px !important; font-size: 12px !important; }
            .container { max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container py-4" style="max-width:600px;">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="transactions.php" class="btn btn-outline-light btn-sm">← Back</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Print</button>
    </div>

    <div class="card p-4 receipt-box">
        <!-- Header -->
        <div class="text-center mb-4">
            <img src="logo/VNZM 2x6.png" alt="Logo" style="max-width:100%; height:auto; display:block; margin:0 auto 0.5rem;">
            <div style="color:#4a7a5a; font-size:0.85rem;">Official Receipt</div>
        </div>

        <div style="border-top:1px solid #1e3a2a; border-bottom:1px solid #1e3a2a; padding:0.75rem 0; margin-bottom:1rem;">
            <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                <span style="color:#4a7a5a;">Receipt #</span>
                <span><?= htmlspecialchars($transaction['receipt_number'] ?? '#'.$transaction['id']) ?></span>
            </div>
            <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                <span style="color:#4a7a5a;">Customer</span>
                <span><?= htmlspecialchars($transaction['customer_name']) ?></span>
            </div>
            <?php if (!empty($transaction['notes'])): ?>
            <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                <span style="color:#4a7a5a;">Notes</span>
                <span><?= htmlspecialchars($transaction['notes']) ?></span>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                <span style="color:#4a7a5a;">Date</span>
                <span><?= date('M d, Y h:i A', strtotime($transaction['created_at'])) ?></span>
            </div>
        </div>

        <!-- Items -->
        <table class="table mb-0" style="font-size:0.88rem;">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div style="color:#4a7a5a; font-size:0.78rem;">per <?= htmlspecialchars($item['unit']) ?></div>
                    </td>
                    <td class="text-center"><?= rtrim(rtrim(number_format($item['quantity'], 3), '0'), '.') ?></td>
                    <td class="text-end">₱<?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-end" style="color:#00c896;">₱<?= number_format($item['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total -->
        <div style="border-top:1px solid #1e3a2a; margin-top:1rem; padding-top:1rem;">
            <div class="d-flex justify-content-between">
                <span style="font-weight:700; font-size:1.1rem;">TOTAL</span>
                <span style="font-weight:700; font-size:1.1rem; color:#00c896;">
                    ₱<?= number_format($transaction['total_amount'], 2) ?>
                </span>
            </div>
        </div>

        <div class="text-center mt-4" style="color:#4a7a5a; font-size:0.8rem;">
            Thank you for your purchase!
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/progress.js"></script>
</body>
</html>

<?php
require 'auth.php';
require 'db.php';

// ── Summary Stats ──────────────────────────────────────────
$today = $pdo->query("
    SELECT COUNT(*) AS count, COALESCE(SUM(total_amount),0) AS total
    FROM transactions
    WHERE DATE(created_at) = CURDATE()
")->fetch();

$week = $pdo->query("
    SELECT COUNT(*) AS count, COALESCE(SUM(total_amount),0) AS total
    FROM transactions
    WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
")->fetch();

$month = $pdo->query("
    SELECT COUNT(*) AS count, COALESCE(SUM(total_amount),0) AS total
    FROM transactions
    WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
")->fetch();

// ── Last 7 Days Chart Data ─────────────────────────────────
$last7 = $pdo->query("
    SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS total
    FROM transactions
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

// Fill in missing days with 0
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartData[$date] = 0;
}
foreach ($last7 as $row) {
    $chartData[$row['day']] = (float) $row['total'];
}

$chartLabels = array_map(fn($d) => date('D M d', strtotime($d)), array_keys($chartData));
$chartValues = array_values($chartData);

// ── Top 5 Products This Week ───────────────────────────────
$topProducts = $pdo->query("
    SELECT p.name, p.unit,
           SUM(ti.quantity) AS total_qty,
           SUM(ti.subtotal) AS total_sales
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1)
    GROUP BY p.id
    ORDER BY total_sales DESC
    LIMIT 5
")->fetchAll();

// ── Recent Transactions ────────────────────────────────────
$recent = $pdo->query("
    SELECT * FROM transactions
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <style>
        .stat-card {
            background: #111a14;
            border: 1px solid #1e3a2a;
            border-radius: 14px;
            padding: 1.4rem 1.6rem;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 100px; height: 100px;
            background: radial-gradient(circle, rgba(0,200,150,0.12), transparent 70%);
            border-radius: 50%;
        }
        .stat-label { color: #4a7a5a; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { color: #00c896; font-size: 1.7rem; font-weight: 700; line-height: 1.2; margin: 0.25rem 0; }
        .stat-sub   { color: #a5d6a7; font-size: 0.82rem; }
        .stat-icon  { font-size: 1.6rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <h4 style="color:#00c896; font-weight:700;" class="mb-4">Dashboard</h4>

    <!-- ── Stat Cards ── -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Today</div>
                <div class="stat-value">₱<?= number_format($today['total'], 2) ?></div>
                <div class="stat-sub"><?= $today['count'] ?> transaction<?= $today['count'] != 1 ? 's' : '' ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon">📆</div>
                <div class="stat-label">This Week</div>
                <div class="stat-value">₱<?= number_format($week['total'], 2) ?></div>
                <div class="stat-sub"><?= $week['count'] ?> transaction<?= $week['count'] != 1 ? 's' : '' ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon">🗓</div>
                <div class="stat-label">This Month</div>
                <div class="stat-value">₱<?= number_format($month['total'], 2) ?></div>
                <div class="stat-sub"><?= $month['count'] ?> transaction<?= $month['count'] != 1 ? 's' : '' ?></div>
            </div>
        </div>
    </div>

    <!-- ── Chart + Top Products ── -->
    <div class="row g-3 mb-4">
        <!-- Bar Chart -->
        <div class="col-lg-7">
            <div class="card p-4" style="height:100%;">
                <div class="card-header mb-3" style="background:none; border:none; padding:0;">
                    Sales — Last 7 Days
                </div>
                <canvas id="salesChart" height="200"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-5">
            <div class="card p-4" style="height:100%;">
                <div class="card-header mb-3" style="background:none; border:none; padding:0;">
                    Top Products This Week
                </div>
                <?php if (empty($topProducts)): ?>
                    <p style="color:#4a7a5a; font-size:0.85rem;">No sales this week yet.</p>
                <?php else: ?>
                    <?php
                    $maxSales = max(array_column($topProducts, 'total_sales'));
                    foreach ($topProducts as $i => $p):
                        $pct = $maxSales > 0 ? ($p['total_sales'] / $maxSales) * 100 : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                            <span><?= htmlspecialchars($p['name']) ?></span>
                            <span style="color:#00c896;">₱<?= number_format($p['total_sales'], 2) ?></span>
                        </div>
                        <div style="background:#1e3a2a; border-radius:99px; height:6px;">
                            <div style="width:<?= $pct ?>%; background:linear-gradient(90deg,#00c896,#00a878); height:100%; border-radius:99px; transition: width 1s ease;"></div>
                        </div>
                        <div style="color:#4a7a5a; font-size:0.75rem; margin-top:2px;">
                            <?= rtrim(rtrim(number_format($p['total_qty'], 3), '0'), '.') ?> <?= htmlspecialchars($p['unit']) ?> sold
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Recent Transactions ── -->
    <div class="card">
        <div class="card-header px-4 py-3">Recent Transactions</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="5" class="text-center" style="color:#4a7a5a; padding:2rem;">No transactions yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $t): ?>
                    <tr>
                        <td style="color:#00c896; font-weight:600;"><?= htmlspecialchars($t['receipt_number'] ?? '#'.$t['id']) ?></td>
                        <td><?= htmlspecialchars($t['customer_name']) ?></td>
                        <td style="color:#00c896; font-weight:600;">₱<?= number_format($t['total_amount'], 2) ?></td>
                        <td style="color:#4a7a5a; font-size:0.85rem;"><?= date('M d, Y h:i A', strtotime($t['created_at'])) ?></td>
                        <td><a href="transaction_view.php?id=<?= $t['id'] ?>" class="btn btn-outline-light btn-sm">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Sales (₱)',
            data: <?= json_encode($chartValues) ?>,
            backgroundColor: 'rgba(0, 200, 150, 0.2)',
            borderColor: '#00c896',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2})
                },
                backgroundColor: '#111a14',
                borderColor: '#1e3a2a',
                borderWidth: 1,
                titleColor: '#a5d6a7',
                bodyColor: '#00c896',
            }
        },
        scales: {
            x: {
                ticks: { color: '#4a7a5a', font: { size: 11 } },
                grid: { color: '#1e3a2a' }
            },
            y: {
                ticks: {
                    color: '#4a7a5a',
                    font: { size: 11 },
                    callback: v => '₱' + v.toLocaleString()
                },
                grid: { color: '#1e3a2a' }
            }
        }
    }
});
</script>
<script src="assets/progress.js"></script>
</body>
</html>

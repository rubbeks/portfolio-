<?php
require_once '../config.php';
redirectIfNotAdmin();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT AVG(price) as avg_price FROM products");
$avg_price = $stmt->fetch()['avg_price'];

$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $stmt->fetch()['total_orders'];

$stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'");
$total_revenue = $stmt->fetch()['total_revenue'] ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) as low_stock_products FROM products WHERE quantity <= 5 AND quantity > 0");
$low_stock_products = $stmt->fetch()['low_stock_products'];

$stmt = $pdo->query("SELECT COUNT(*) as out_of_stock_products FROM products WHERE quantity = 0");
$out_of_stock_products = $stmt->fetch()['out_of_stock_products'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Christian's Coffee Shop</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">Christian's Coffee Shop - Admin</h1>
            <ul class="nav-menu">
                <li><a href="../index.php" class="nav-link">View Site</a></li>
                <li><a href="../logout.php" class="nav-link">Logout</a></li>
                <li><span class="nav-user">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-sidebar">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage-users.php">Manage Users</a></li>
                <li><a href="manage-products.php">Manage Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <p>Welcome to the admin panel, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                <div class="admin-card">
                    <h3 style="color: #2c1810; margin-bottom: 1rem;">📊 Statistics</h3>
                    <div style="display: grid; gap: 1rem;">
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                            <strong>Total Users:</strong> <?php echo $total_users; ?>
                        </div>
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                            <strong>Total Products:</strong> <?php echo $total_products; ?>
                        </div>
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                            <strong>Total Orders:</strong> <?php echo $total_orders; ?>
                        </div>
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                            <strong>Total Revenue:</strong> <?php echo formatPrice($total_revenue); ?>
                        </div>
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                            <strong>Average Price:</strong> <?php echo formatPrice($avg_price); ?>
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <h3 style="color: #2c1810; margin-bottom: 1rem;">📦 Inventory Status</h3>
                    <div style="display: grid; gap: 1rem;">
                        <div style="padding: 1rem; background: <?php echo $out_of_stock_products > 0 ? '#f8d7da' : '#f8f9fa'; ?>; border-radius: 5px;">
                            <strong>Out of Stock:</strong> <?php echo $out_of_stock_products; ?> products
                            <?php if ($out_of_stock_products > 0): ?>
                                <br><small style="color: #721c24;">⚠️ Requires immediate attention</small>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 1rem; background: <?php echo $low_stock_products > 0 ? '#fff3cd' : '#f8f9fa'; ?>; border-radius: 5px;">
                            <strong>Low Stock (≤5):</strong> <?php echo $low_stock_products; ?> products
                            <?php if ($low_stock_products > 0): ?>
                                <br><small style="color: #856404;">⚠️ Consider restocking soon</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <h3 style="color: #2c1810; margin-bottom: 1rem;">🛠️ Quick Actions</h3>
                    <div style="display: grid; gap: 1rem;">
                        <a href="manage-users.php" class="btn-primary" style="text-align: center; text-decoration: none;">Manage Users</a>
                        <a href="manage-products.php" class="btn-primary" style="text-align: center; text-decoration: none;">Manage Products</a>
                        <a href="orders.php" class="btn-primary" style="text-align: center; text-decoration: none;">View Orders</a>
                        <a href="../shop.php" class="add-to-cart-btn" style="text-align: center; text-decoration: none;">View Shop</a>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3 style="color: #2c1810; margin-bottom: 1rem;">📋 System Overview</h3>
                <p><strong>Inventory Management:</strong> Real-time stock tracking with automatic deductions on orders</p>
                <p><strong>Security:</strong> Anti-brute force protection (10 attempts, 15-minute lockout)</p>
                <p><strong>Order Processing:</strong> Complete order management with inventory updates</p>
                
                <div style="margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-radius: 5px;">
                    <h4>Recent System Features:</h4>
                    <ul style="margin: 0.5rem 0; padding-left: 2rem;">
                        <li>✅ Inventory system with stock quantities</li>
                        <li>✅ Anti-brute force login protection</li>
                        <li>✅ Automatic stock deduction on orders</li>
                        <li>✅ Low stock and out-of-stock warnings</li>
                        <li>✅ Order management and tracking</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>
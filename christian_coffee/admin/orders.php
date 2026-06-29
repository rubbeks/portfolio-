<?php
require_once '../config.php';
redirectIfNotAdmin();

// Get orders with user information
$stmt = $pdo->query("
    SELECT o.*, u.username, u.email,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

// Get order details if viewing specific order
$order_details = null;
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Christian's Coffee Shop Admin</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage-users.php">Manage Users</a></li>
                <li><a href="manage-products.php">Manage Products</a></li>
                <li><a href="orders.php" class="active">Orders</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Order Management</h1>
                <p>View and manage customer orders</p>
            </div>

            <?php if ($order_details): ?>
                <div class="admin-card">
                    <h3>Order Details #<?php echo $_GET['view']; ?></h3>
                    <a href="orders.php" class="add-to-cart-btn" style="margin-bottom: 1rem; display: inline-block;">← Back to Orders</a>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_details as $item): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <img src="../images/<?php echo $item['image']; ?>" 
                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;"
                                                 onerror="this.src='../images/placeholder.jpg'">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-card">
                    <h3>All Orders</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><?php echo $order['item_count']; ?> items</td>
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                    <td>
                                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; color: white;
                                              background: <?php 
                                                switch($order['status']) {
                                                    case 'completed': echo '#28a745'; break;
                                                    case 'processing': echo '#ffc107'; break;
                                                    case 'cancelled': echo '#dc3545'; break;
                                                    default: echo '#6c757d';
                                                }
                                              ?>;">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn-edit btn-small">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem;">
                                        No orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>
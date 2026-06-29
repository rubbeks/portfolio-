<?php
require_once 'config.php';
redirectIfNotLoggedIn();

// Get cart total
$total = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $total += $product['price'] * $quantity;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Christian's Coffee Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">Christian's Coffee Shop</h1>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="shop.php" class="nav-link">Shop</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
                <li><span class="nav-user">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; min-height: 60vh;">
        <h1 style="color: #2c1810; margin-bottom: 2rem; text-align: center;">Checkout</h1>
        
        <div class="admin-card" style="max-width: 600px; margin: 0 auto; text-align: center;">
            <h2>Order Summary</h2>
            <div class="total-amount" style="margin: 2rem 0;">
                Total: <?php echo formatPrice($total); ?>
            </div>
            
            <div style="background: #f8f9fa; padding: 2rem; border-radius: 10px; margin: 2rem 0;">
                <h3>🚧 Checkout Coming Soon!</h3>
                <p>This is a placeholder for the checkout system. In a full implementation, this would include:</p>
                <ul style="text-align: left; margin: 1rem 0;">
                    <li>Customer billing information form</li>
                    <li>Payment gateway integration (PayPal, Stripe, etc.)</li>
                    <li>Order confirmation and email notifications</li>
                    <li>Inventory management</li>
                    <li>Order tracking system</li>
                </ul>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="cart.php" class="add-to-cart-btn">Back to Cart</a>
                <a href="shop.php" class="cta-button" style="margin-left: 1rem;">Continue Shopping</a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Christian's Coffee Shop. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php
require_once 'config.php';

// Fetch featured products (first 4 products)
$stmt = $pdo->query("SELECT * FROM products LIMIT 4");
$featuredProducts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Christian's Coffee Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">Christian's Coffee Shop</h1>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link active">Home</a></li>
                <li><a href="shop.php" class="nav-link">Shop</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/dashboard.php" class="nav-link">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-link">Logout</a></li>
                    <li><span class="nav-user">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                    <li><a href="signup.php" class="nav-link">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>Welcome to Christian's Coffee Shop</h1>
            <p>Discover the perfect blend of tradition and innovation in every cup. We serve the finest coffee beans sourced from the best farms around the world.</p>
            <a href="shop.php" class="cta-button">Shop Now</a>
        </div>
    </header>

    <section class="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card">
                        <img src="images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='images/placeholder.jpg'">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price"><?php echo formatPrice($product['price']); ?></p>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="stock-info">
                                <?php if ($product['quantity'] > 0): ?>
                                    <p class="stock-available">✅ In Stock: <?php echo $product['quantity']; ?> available</p>
                                    <?php if (isLoggedIn()): ?>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="add-to-cart-btn">Add to Cart</button>
                                    <?php else: ?>
                                        <a href="login.php" class="add-to-cart-btn">Login to Buy</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="stock-unavailable">❌ Out of Stock</p>
                                    <button class="add-to-cart-btn" style="background: #ccc; cursor: not-allowed;" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Christian's Coffee Shop. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
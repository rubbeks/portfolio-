<?php
require_once 'config.php';

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Christian's Coffee Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">Christian's Coffee Shop</h1>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="shop.php" class="nav-link active">Shop</a></li>
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

    <div class="container" style="margin-top: 100px; padding-bottom: 2rem;">
        <h1 style="text-align: center; color: #2c1810; margin-bottom: 2rem;">Our Coffee Collection</h1>
        
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
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
                                    <div class="cart-counter-section">
                                        <div class="cart-counter" id="cart_counter_<?php echo $product['id']; ?>">
                                            <span class="counter-label">In Cart: </span>
                                            <span class="counter-number" id="counter_<?php echo $product['id']; ?>">0</span>
                                        </div>
                                        <button onclick="addToCartWithCounter(<?php echo $product['id']; ?>)" class="add-to-cart-btn">Add to Cart</button>
                                    </div>
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

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Christian's Coffee Shop. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Cart counter functionality
        let cartCounters = {};

        // Initialize cart counters for all products
        <?php foreach ($products as $product): ?>
            cartCounters[<?php echo $product['id']; ?>] = 0;
        <?php endforeach; ?>

        // Load cart counters from localStorage on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCartCounters();
        });

        function loadCartCounters() {
            const savedCounters = localStorage.getItem('cartCounters');
            if (savedCounters) {
                cartCounters = JSON.parse(savedCounters);
                // Update display for all counters
                for (let productId in cartCounters) {
                    updateCounterDisplay(productId);
                }
            }
        }

        function saveCartCounters() {
            localStorage.setItem('cartCounters', JSON.stringify(cartCounters));
        }

        function updateCounterDisplay(productId) {
            const counterElement = document.getElementById('counter_' + productId);
            if (counterElement) {
                counterElement.textContent = cartCounters[productId] || 0;
                
                // Add visual feedback when counter changes
                const counterSection = document.getElementById('cart_counter_' + productId);
                if (cartCounters[productId] > 0) {
                    counterSection.style.display = 'block';
                    counterSection.style.color = '#28a745';
                    counterSection.style.fontWeight = 'bold';
                } else {
                    counterSection.style.display = 'none';
                }
            }
        }

        function addToCartWithCounter(productId) {
            // Make AJAX call to add to cart (existing functionality)
            addToCart(productId);
            
            // Update local counter
            if (!cartCounters[productId]) {
                cartCounters[productId] = 0;
            }
            cartCounters[productId]++;
            
            // Update display
            updateCounterDisplay(productId);
            
            // Save to localStorage
            saveCartCounters();
            
            // Visual feedback for the button
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Added!';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
            }, 1000);
        }

        // Optional: Reset counters function (can be called when cart is cleared)
        function resetCartCounters() {
            cartCounters = {};
            <?php foreach ($products as $product): ?>
                cartCounters[<?php echo $product['id']; ?>] = 0;
                updateCounterDisplay(<?php echo $product['id']; ?>);
            <?php endforeach; ?>
            saveCartCounters();
        }
    </script>

    <style>
        .cart-counter-section {
            margin-top: 10px;
        }

        .cart-counter {
            display: none;
            margin-bottom: 8px;
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #28a745;
        }

        .counter-label {
            font-weight: normal;
            color: #6c757d;
        }

        .counter-number {
            font-weight: bold;
            color: #28a745;
            font-size: 1.1em;
        }

        .add-to-cart-btn {
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
        }

        /* Animation for counter updates */
        .counter-number {
            transition: all 0.3s ease;
        }

        .counter-update {
            animation: counterPulse 0.5s ease;
        }

        @keyframes counterPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</body>
</html>
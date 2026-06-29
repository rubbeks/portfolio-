<?php
require_once 'config.php';

$message = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $product_id = (int)$_POST['product_id'];
                $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

                // Check product availability
                if (!checkProductAvailability($product_id, $quantity)) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                    exit();
                }

                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                if (isset($_SESSION['cart'][$product_id])) {
                    $new_quantity = $_SESSION['cart'][$product_id] + $quantity;
                    if (checkProductAvailability($product_id, $new_quantity)) {
                        $_SESSION['cart'][$product_id] = $new_quantity;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                        exit();
                    }
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                echo json_encode(['success' => true]);
                exit();

            case 'update':
                $product_id = (int)$_POST['product_id'];
                $quantity = (int)$_POST['quantity'];

                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    if (checkProductAvailability($product_id, $quantity)) {
                        $_SESSION['cart'][$product_id] = $quantity;
                    } else {
                        $message = 'Not enough stock available for the requested quantity.';
                    }
                }
                break;

            case 'remove':
                $product_id = (int)$_POST['product_id'];
                unset($_SESSION['cart'][$product_id]);
                break;

            case 'checkout':
                if (isLoggedIn() && !empty($_SESSION['cart'])) {
                    try {
                        // Get cart items with current stock check
                        $cart_items = [];
                        $total = 0;
                        $out_of_stock = [];

                        $product_ids = array_keys($_SESSION['cart']);
                        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                        $stmt->execute($product_ids);
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($products as $product) {
                            $requested_quantity = $_SESSION['cart'][$product['id']];

                            if ($product['quantity'] < $requested_quantity) {
                                $out_of_stock[] = $product['name'];
                                continue;
                            }

                            $subtotal = $product['price'] * $requested_quantity;
                            $total += $subtotal;

                            $cart_items[] = [
                                'product' => $product,
                                'quantity' => $requested_quantity,
                                'subtotal' => $subtotal
                            ];
                        }

                        if (!empty($out_of_stock)) {
                            $message = 'The following items are out of stock: ' . implode(', ', $out_of_stock);
                        } else {
                            // Process the order
                            $order_id = processOrder($_SESSION['user_id'], $cart_items, $total);

                            // Clear cart
                            $_SESSION['cart'] = [];

                            $message = "Order #$order_id placed successfully! Inventory has been updated.";
                        }

                    } catch (Exception $e) {
                        $message = 'Error processing order: ' . $e->getMessage();
                    }
                }
                break;
        } // Close switch statement
    } // Close if isset action
} // Close if POST request

// Get cart items
$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;

        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'available_stock' => $product['quantity']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Christian's Coffee Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">Christian's Coffee Shop</h1>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="shop.php" class="nav-link">Shop</a></li>
                <li><a href="cart.php" class="nav-link active">Cart</a></li>
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

    <div class="container cart-container">
        <h1 style="color: #2c1810; margin-bottom: 2rem;">Shopping Cart</h1>

        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'Error') !== false || strpos($message, 'out of stock') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3>Your cart is empty</h3>
                <p>Add some delicious coffee to your cart!</p>
                <a href="shop.php" class="cta-button">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="images/<?php echo $item['product']['image']; ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>" onerror="this.src='images/placeholder.jpg'">
                        <div class="cart-item-info">
                            <h4><?php echo htmlspecialchars($item['product']['name']); ?></h4>
                            <p class="price"><?php echo formatPrice($item['product']['price']); ?> each</p>
                            <p><strong>Subtotal: <?php echo formatPrice($item['subtotal']); ?></strong></p>

                            <?php if ($item['available_stock'] < $item['quantity']): ?>
                                <div class="out-of-stock-warning">
                                    ⚠️ Only <?php echo $item['available_stock']; ?> items available in stock!
                                </div>
                            <?php elseif ($item['available_stock'] <= 5): ?>
                                <div class="low-stock-warning">
                                    ⚠️ Low stock: Only <?php echo $item['available_stock']; ?> items remaining
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-controls">
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product']['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                <span><?php echo $item['quantity']; ?></span>
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product']['id']; ?>, <?php echo $item['quantity'] + 1; ?>)" 
                                    <?php echo $item['quantity'] >= $item['available_stock'] ? 'disabled style="background: #ccc;"' : ''; ?>>+</button>
                            </div>
                            <button class="remove-btn" onclick="removeFromCart(<?php echo $item['product']['id']; ?>)">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-total">
                <h3>Order Summary</h3>
                <div class="total-amount"><?php echo formatPrice($total); ?></div>

                <?php if (isLoggedIn()): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="cta-button">Complete Order</button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="cta-button">Login to Checkout</a>
                <?php endif; ?>

                <a href="shop.php" class="add-to-cart-btn" style="margin-left: 1rem;">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Christian's Coffee Shop. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>

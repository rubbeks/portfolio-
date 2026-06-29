<?php
require_once '../config.php';
redirectIfNotAdmin();

$message = '';

// Handle delete product
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$product_id])) {
        $message = "Product deleted successfully!";
    } else {
        $message = "Error deleting product.";
    }
}

// Handle add/edit product
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = (int)$_POST['quantity'];
    $image = trim($_POST['image']);
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if (empty($name) || empty($description) || $price <= 0 || $quantity < 0) {
        $message = "Please fill in all fields with valid data.";
    } else {
        if ($product_id > 0) {
            // Update existing product
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, image = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $price, $quantity, $image, $product_id])) {
                $message = "Product updated successfully!";
            } else {
                $message = "Error updating product.";
            }
        } else {
            // Add new product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, quantity, image) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $price, $quantity, $image])) {
                $message = "Product added successfully!";
            } else {
                $message = "Error adding product.";
            }
        }
    }
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Christian's Coffee Shop Admin</title>
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
                <li><a href="manage-products.php" class="active">Manage Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Manage Products</h1>
                <p>Add, edit, and manage coffee products</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
                <form method="POST" action="">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="name">Product Name:</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₱):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="quantity">Stock Quantity:</label>
                            <input type="number" id="quantity" name="quantity" min="0" required 
                                   value="<?php echo $edit_product ? $edit_product['quantity'] : '0'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image Filename:</label>
                            <input type="text" id="image" name="image" placeholder="e.g., espresso.jpg" 
                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['image']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3" required 
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image Filename:</label>
                        <input type="text" id="image" name="image" placeholder="e.g., espresso.jpg" 
                               value="<?php echo $edit_product ? htmlspecialchars($edit_product['image']) : ''; ?>">
                        <small style="color: #666;">Place images in the 'images' folder</small>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn-primary">
                            <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="manage-products.php" class="add-to-cart-btn" style="margin-left: 1rem;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="admin-card">
                <h3>All Products</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="../images/<?php echo $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;"
                                         onerror="this.src='../images/placeholder.jpg'">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo formatPrice($product['price']); ?></td>
                                <td>
                                    <span style="padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; color: white;
                                          background: <?php 
                                            if ($product['quantity'] == 0) echo '#dc3545';
                                            elseif ($product['quantity'] <= 5) echo '#ffc107'; 
                                            else echo '#28a745';
                                          ?>;">
                                        <?php echo $product['quantity']; ?>
                                        <?php if ($product['quantity'] == 0) echo ' (Out)';
                                              elseif ($product['quantity'] <= 5) echo ' (Low)'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></td>
                                <td>
                                    <a href="manage-products.php?edit=<?php echo $product['id']; ?>" class="btn-edit btn-small">Edit</a>
                                    <button onclick="confirmDelete('product', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" class="btn-delete btn-small">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>
<?php
require 'auth.php';
require 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$product = ['name' => '', 'category_id' => '', 'price' => '', 'unit' => 'piece', 'image' => ''];
$error = '';

// Load existing product for edit
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) { header('Location: products.php'); exit; }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price       = (float) ($_POST['price'] ?? 0);
    $unit        = trim($_POST['unit'] ?? 'piece');
    $imageFile   = $product['image'] ?? '';

    if (!$name || !$category_id || $price <= 0 || !$unit) {
        $error = 'Please fill in all required fields.';
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, or WEBP images allowed.';
            } elseif ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                $error = 'Image must be under 10MB.';
            } else {
                $newName = uniqid('prod_', true) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$newName");
                // Delete old image
                if ($imageFile && file_exists("uploads/$imageFile")) {
                    unlink("uploads/$imageFile");
                }
                $imageFile = $newName;
            }
        }

        if (!$error) {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET name=?, category_id=?, price=?, unit=?, image=? WHERE id=?");
                $stmt->execute([$name, $category_id, $price, $unit, $imageFile, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, unit, image) VALUES (?,?,?,?,?)");
                $stmt->execute([$name, $category_id, $price, $unit, $imageFile]);
            }
            header('Location: products.php?saved=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit' : 'Add' ?> Product — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container py-4" style="max-width:600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:#00c896; font-weight:700;"><?= $id ? 'Edit' : 'Add' ?> Product</h4>
        <a href="products.php" class="btn btn-outline-light btn-sm">← Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Product Name *</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Category *</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Price (₱) *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0.01"
                           value="<?= htmlspecialchars($product['price']) ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Unit *</label>
                    <select name="unit" class="form-select">
                        <?php foreach (['piece','meter','kg','liter','box','roll','set','pair','bag','sheet'] as $u): ?>
                            <option value="<?= $u ?>" <?= $product['unit'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Product Image</label>
                <?php if ($product['image']): ?>
                    <div class="mb-2">
                        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" style="height:80px;border-radius:8px;">
                        <span style="color:#4a7a5a; font-size:0.8rem; margin-left:0.5rem;">Current image</span>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div style="color:#4a7a5a; font-size:0.78rem; margin-top:0.25rem;">JPG, PNG or WEBP — max 10MB</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <?= $id ? 'Save Changes' : 'Add Product' ?>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/progress.js"></script>
</body>
</html>

<?php
require 'auth.php';
require 'db.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();
    if ($prod && $prod['image'] && file_exists("uploads/" . $prod['image'])) {
        unlink("uploads/" . $prod['image']);
    }
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header('Location: products.php?deleted=1');
    exit;
}

// Filters
$search     = trim($_GET['search'] ?? '');
$cat_filter = (int) ($_GET['category'] ?? 0);
$price_sort = $_GET['price'] ?? '';

$where  = [];
$params = [];

if ($search) {
    $where[]  = "p.name LIKE ?";
    $params[] = "%$search%";
}
if ($cat_filter) {
    $where[]  = "p.category_id = ?";
    $params[] = $cat_filter;
}

$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);

if ($price_sort === 'asc')       $sql .= " ORDER BY p.price ASC";
elseif ($price_sort === 'desc')  $sql .= " ORDER BY p.price DESC";
else                             $sql .= " ORDER BY c.name, p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:#00c896; font-weight:700;">Products</h4>
        <a href="product_form.php" class="btn btn-primary">+ Add Product</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success mb-3">Product deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success mb-3">Product saved successfully.</div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Product name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sort by Price</label>
                <select name="price" class="form-select">
                    <option value="">Default</option>
                    <option value="asc"  <?= $price_sort === 'asc'  ? 'selected' : '' ?>>Low → High</option>
                    <option value="desc" <?= $price_sort === 'desc' ? 'selected' : '' ?>>High → Low</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="products.php" class="btn btn-outline-light w-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="mb-2" style="color:#4a7a5a; font-size:0.85rem;"><?= count($products) ?> product(s) found</div>

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Unit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6" class="text-center" style="color:#4a7a5a; padding:2rem;">No products yet. Add one!</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['image']): ?>
                                <img src="uploads/<?= htmlspecialchars($p['image']) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:8px;">
                            <?php else: ?>
                                <div style="width:70px;height:70px;background:#1e3a2a;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">🔧</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><span class="badge-category"><?= htmlspecialchars($p['category_name']) ?></span></td>
                        <td style="color:#00c896;">₱<?= number_format($p['price'], 2) ?></td>
                        <td style="color:#4a7a5a;"><?= htmlspecialchars($p['unit']) ?></td>
                        <td>
                            <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-outline-light btn-sm me-1">Edit</a>
                            <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete <?= htmlspecialchars(addslashes($p['name'])) ?>?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/progress.js"></script>
</body>
</html>

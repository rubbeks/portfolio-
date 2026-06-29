<?php
require 'auth.php';
require 'db.php';

$error = '';
$editItem = null;

// Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Check if category has products
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $error = 'Cannot delete — this category has products assigned to it.';
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        header('Location: categories.php?deleted=1');
        exit;
    }
}

// Load for edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editItem = $stmt->fetch();
}

// Save (add or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $id   = (int) ($_POST['id'] ?? 0);

    if (!$name) {
        $error = 'Category name is required.';
    } else {
        if ($id) {
            $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $id]);
        } else {
            $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
        }
        header('Location: categories.php?saved=1');
        exit;
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container py-4" style="max-width:700px;">
    <h4 style="color:#00c896; font-weight:700;" class="mb-4">Categories</h4>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success mb-3">Category saved.</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success mb-3">Category deleted.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add / Edit Form -->
    <div class="card p-4 mb-4">
        <h6 style="color:#00c896;" class="mb-3"><?= $editItem ? 'Edit Category' : 'Add Category' ?></h6>
        <form method="POST" class="d-flex gap-2">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>
            <input type="text" name="name" class="form-control"
                   placeholder="Category name"
                   value="<?= htmlspecialchars($editItem['name'] ?? '') ?>" required>
            <button type="submit" class="btn btn-primary px-4">
                <?= $editItem ? 'Update' : 'Add' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="categories.php" class="btn btn-outline-light">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- List -->
    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="3" class="text-center" style="color:#4a7a5a; padding:2rem;">No categories yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><span class="badge-category"><?= $cat['product_count'] ?></span></td>
                        <td>
                            <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-outline-light btn-sm me-1">Edit</a>
                            <a href="categories.php?delete=<?= $cat['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete <?= htmlspecialchars(addslashes($cat['name'])) ?>?')">Delete</a>
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

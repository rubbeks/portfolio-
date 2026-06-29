<?php
require_once '../config.php';
redirectIfNotAdmin();

$message = '';

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    if ($stmt->execute([$user_id])) {
        $message = "User deleted successfully!";
    } else {
        $message = "Error deleting user.";
    }
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE username LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get users
$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Christian's Coffee Shop Admin</title>
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
                <li><a href="manage-users.php" class="active">Manage Users</a></li>
                <li><a href="manage-products.php">Manage Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Manage Users</h1>
                <p>View, search, and manage user accounts</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <form class="search-form" method="GET">
                    <input type="text" name="search" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                    <?php if ($search): ?>
                        <a href="manage-users.php" class="add-to-cart-btn">Clear</a>
                    <?php endif; ?>
                </form>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span style="padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; 
                                          background: <?php echo $user['role'] === 'admin' ? '#d4a574' : '#28a745'; ?>; 
                                          color: white;">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn-edit btn-small">Edit</a>
                                    <?php if ($user['role'] !== 'admin' && $user['id'] !== $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete('user', <?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn-delete btn-small">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    <?php echo $search ? "No users found matching your search." : "No users found."; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>
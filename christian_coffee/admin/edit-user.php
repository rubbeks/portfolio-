<?php
require_once '../config.php';
redirectIfNotAdmin();

$message = '';
$user = null;

// Get user ID
if (!isset($_GET['id'])) {
    header('Location: manage-users.php');
    exit();
}

$user_id = (int)$_GET['id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: manage-users.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $new_password = trim($_POST['new_password']);
    
    if (empty($username) || empty($email)) {
        $message = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = 'Username or email already exists.';
        } else {
            // Update user
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $success = $stmt->execute([$username, $email, $hashed_password, $role, $user_id]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $success = $stmt->execute([$username, $email, $role, $user_id]);
            }
            
            if ($success) {
                $message = 'User updated successfully!';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $message = 'Error updating user.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Christian's Coffee Shop Admin</title>
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
                <h1>Edit User</h1>
                <p>Modify user account details</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="admin-card" style="max-width: 600px;">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current):</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group" style="background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                        <strong>Account Information:</strong><br>
                        <small>Created: <?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></small><br>
                        <small>User ID: <?php echo $user['id']; ?></small>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Update User</button>
                        <a href="manage-users.php" class="add-to-cart-btn" style="margin-left: 1rem;">Back to Users</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>
<?php
require_once 'config.php';

$error = '';
$success = '';
$client_ip = getClientIP();

// Check if IP is blocked
if (isIPBlocked($client_ip)) {
    $remaining_time = getRemainingBlockTime($client_ip);
    $minutes = floor($remaining_time / 60);
    $seconds = $remaining_time % 60;
    
    if ($remaining_time > 0) {
        $block_info = getBlockInfo($client_ip);
        if ($block_info) {
            $error = "Account temporarily locked due to security policy. Remaining time: {$minutes} minutes and {$seconds} seconds.";
        } else {
            $error = "Too many failed login attempts. Please try again in {$minutes} minutes and {$seconds} seconds.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isIPBlocked($client_ip)) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Check if input is email or username
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            logLoginAttempt($client_ip, $username, true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            // Failed login
            logLoginAttempt($client_ip, $username, false);
            $error = 'Invalid username/email or password.';
            
            // Check if this attempt triggers a block
            if (isIPBlocked($client_ip)) {
                $block_info = getBlockInfo($client_ip);
                if ($block_info) {
                    $error .= " Security policy activated - account temporarily locked for {$block_info['duration']}.";
                } else {
                    $error .= ' Too many failed attempts. Your IP has been temporarily blocked.';
                }
            }
        }
    }
}

// Get current block info for display
$block_info = getBlockInfo($client_ip);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Christian's Coffee Shop</title>
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
                <li><a href="login.php" class="nav-link active">Login</a></li>
                <li><a href="signup.php" class="nav-link">Sign Up</a></li>
            </ul>
        </div>
    </nav>

    <div class="form-container">
        <h2>Login</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!isIPBlocked($client_ip)): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">Login</button>
        </form>
        <?php endif; ?>
        
 
        
        <?php if (isIPBlocked($client_ip)): ?>
            <div style="margin-top: 2rem; padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
                <h4>🔒 Security Policy Activated</h4>
                <p>Your account has been temporarily locked due to multiple failed login attempts.</p>
                
                <?php if ($block_info): ?>
                    <div style="margin-top: 1rem; padding: 0.5rem; background: rgba(255,255,255,0.3); border-radius: 3px;">
                        <strong>Block Level <?php echo $block_info['level']; ?>:</strong> <?php echo $block_info['duration']; ?><br>
                        <small><?php echo $block_info['attempts_range']; ?> • <?php echo $block_info['next_tier']; ?></small>
                    </div>
                <?php endif; ?>
                
                <p style="margin-top: 1rem; font-size: 0.9em;">
                    <strong>Security Tiers:</strong><br>
                    • Level 1: 5 minutes (attempts 3-10)<br>
                    • Level 2: 30 minutes (attempts 11-20)<br>
                    • Level 3: 2 hours (attempts 21+)
                </p>
                
                <div id="countdown-timer" style="margin-top: 1rem; padding: 0.5rem; background: rgba(0,0,0,0.1); border-radius: 3px; font-family: monospace;">
                    Time remaining: <span id="countdown"></span>
                </div>
                
                <script>
                    let remainingSeconds = <?php echo getRemainingBlockTime($client_ip); ?>;
                    
                    function updateCountdown() {
                        if (remainingSeconds <= 0) {
                            location.reload();
                            return;
                        }
                        
                        const hours = Math.floor(remainingSeconds / 3600);
                        const minutes = Math.floor((remainingSeconds % 3600) / 60);
                        const seconds = remainingSeconds % 60;
                        
                        let timeString = '';
                        if (hours > 0) {
                            timeString += hours + 'h ';
                        }
                        if (minutes > 0 || hours > 0) {
                            timeString += minutes + 'm ';
                        }
                        timeString += seconds + 's';
                        
                        document.getElementById('countdown').textContent = timeString;
                        remainingSeconds--;
                    }
                    
                    // Update countdown immediately and then every second
                    updateCountdown();
                    setInterval(updateCountdown, 1000);
                </script>
            </div>
        <?php endif; ?>
        
        <!-- Security Information Panel -->
        <div style="margin-top: 2rem; padding: 1rem; background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 5px; color: #0c5460;">
            <h4>🛡️ Account Security</h4>
            <p>We use progressive security measures to protect your account:</p>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem; font-size: 0.9em;">
                <li>First 10 failed attempts: 5-minute lockout</li>
                <li>Next 10 attempts (11-20): 30-minute lockout</li>
                <li>Beyond 20 attempts: 2-hour lockout</li>
            </ul>
            <p style="margin-top: 0.5rem; font-size: 0.9em; font-style: italic;">
                Successful login clears all failed attempt counters.
            </p>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Christian's Coffee Shop. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
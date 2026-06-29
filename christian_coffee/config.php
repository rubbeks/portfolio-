<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "christian_coffee";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

// Enhanced Anti-brute force functions with progressive blocking
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getProgressiveBlockTime($failed_attempts) {
    // Start blocking after 3 failed attempts
    if ($failed_attempts >= 3 && $failed_attempts <= 10) {
        // First blocking tier (attempts 3-10): 5 minutes
        return 5 * 60; // 5 minutes in seconds
    } elseif ($failed_attempts >= 11 && $failed_attempts <= 20) {
        // Second blocking tier (attempts 11-20): 30 minutes  
        return 30 * 60; // 30 minutes in seconds
    } elseif ($failed_attempts >= 21) {
        // Third tier (21+ attempts): 2 hours
        return 2 * 60 * 60; // 2 hours in seconds
    }
    
    return 0; // No block for less than 3 attempts
}

function isIPBlocked($ip) {
    global $pdo;
    
    try {
        // Clean old attempts first (older than 24 hours)
        $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        
        // Get failed attempts in the last 24 hours
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_count, 
                   MAX(attempt_time) as last_attempt
            FROM login_attempts 
            WHERE ip_address = ? 
            AND success = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        
        $failed_count = intval($result['failed_count']);
        $last_attempt = $result['last_attempt'];
        
        // Debug logging (remove in production)
        error_log("IP: $ip, Failed attempts: $failed_count, Last attempt: $last_attempt");
        
        if ($failed_count < 3) {
            return false; // Not blocked yet (less than 3 attempts)
        }
        
        // Calculate block time based on failed attempts
        $block_duration = getProgressiveBlockTime($failed_count);
        
        if ($block_duration == 0) {
            return false; // No blocking required
        }
        
        if (!$last_attempt) {
            return false; // No last attempt found
        }
        
        $last_attempt_time = strtotime($last_attempt);
        $current_time = time();
        $time_since_last_attempt = $current_time - $last_attempt_time;
        
        // Debug logging
        error_log("Block duration: $block_duration, Time since last: $time_since_last_attempt");
        
        // If enough time has passed since last attempt, unblock
        if ($time_since_last_attempt >= $block_duration) {
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error checking IP block status: " . $e->getMessage());
        return false;
    }
}

function getRemainingBlockTime($ip) {
    global $pdo;
    
    try {
        // Get failed attempts in the last 24 hours
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_count, 
                   MAX(attempt_time) as last_attempt
            FROM login_attempts 
            WHERE ip_address = ? 
            AND success = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        
        $failed_count = intval($result['failed_count']);
        $last_attempt = $result['last_attempt'];
        
        if ($failed_count < 3) {
            return 0;
        }
        
        $block_duration = getProgressiveBlockTime($failed_count);
        
        if ($block_duration == 0 || !$last_attempt) {
            return 0;
        }
        
        $last_attempt_time = strtotime($last_attempt);
        $current_time = time();
        $time_since_last_attempt = $current_time - $last_attempt_time;
        
        $remaining_time = $block_duration - $time_since_last_attempt;
        
        return max(0, $remaining_time);
        
    } catch (Exception $e) {
        error_log("Error calculating remaining block time: " . $e->getMessage());
        return 0;
    }
}

function getBlockInfo($ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_count
            FROM login_attempts 
            WHERE ip_address = ? 
            AND success = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        
        $failed_count = intval($result['failed_count']);
        
        if ($failed_count >= 3 && $failed_count <= 10) {
            return [
                'level' => 1,
                'duration' => '5 minutes',
                'attempts_range' => '3-10 attempts',
                'attempts_count' => $failed_count,
                'next_tier' => 'Next tier: 30 minutes (after 10 attempts)'
            ];
        } elseif ($failed_count >= 11 && $failed_count <= 20) {
            return [
                'level' => 2,
                'duration' => '30 minutes',
                'attempts_range' => '11-20 attempts',
                'attempts_count' => $failed_count,
                'next_tier' => 'Next tier: 2 hours (after 20 attempts)'
            ];
        } elseif ($failed_count >= 21) {
            return [
                'level' => 3,
                'duration' => '2 hours',
                'attempts_range' => '21+ attempts',
                'attempts_count' => $failed_count,
                'next_tier' => 'Maximum security level reached'
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error getting block info: " . $e->getMessage());
        return null;
    }
}

function logLoginAttempt($ip, $username = null, $success = false) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (ip_address, username, success, attempt_time) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$ip, $username, $success ? 1 : 0]);
        
        // If successful login, clear ALL failed attempts for this IP
        if ($success) {
            $stmt = $pdo->prepare("
                DELETE FROM login_attempts 
                WHERE ip_address = ? AND success = 0
            ");
            $stmt->execute([$ip]);
        }
        
    } catch (Exception $e) {
        error_log("Error logging login attempt: " . $e->getMessage());
    }
}

// Function to manually reset IP attempts (for admin use)
function resetIPAttempts($ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        return $stmt->execute([$ip]);
    } catch (Exception $e) {
        error_log("Error resetting IP attempts: " . $e->getMessage());
        return false;
    }
}

// Inventory functions
function updateProductQuantity($product_id, $quantity_sold) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE products 
        SET quantity = quantity - ? 
        WHERE id = ? AND quantity >= ?
    ");
    return $stmt->execute([$quantity_sold, $product_id, $quantity_sold]);
}

function checkProductAvailability($product_id, $requested_quantity) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();
    
    return $result && $result['quantity'] >= $requested_quantity;
}

function processOrder($user_id, $cart_items, $total) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->execute([$user_id, $total]);
        $order_id = $pdo->lastInsertId();
        
        // Process each cart item
        foreach ($cart_items as $item) {
            $product_id = $item['product']['id'];
            $quantity = $item['quantity'];
            $price = $item['product']['price'];
            
            // Check availability
            if (!checkProductAvailability($product_id, $quantity)) {
                throw new Exception("Insufficient stock for " . $item['product']['name']);
            }
            
            // Add order item
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $product_id, $quantity, $price]);
            
            // Update inventory
            if (!updateProductQuantity($product_id, $quantity)) {
                throw new Exception("Failed to update inventory for " . $item['product']['name']);
            }
        }
        
        $pdo->commit();
        return $order_id;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}
?>
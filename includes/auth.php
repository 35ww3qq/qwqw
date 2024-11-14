<?php
function check_auth() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_username() {
    return $_SESSION['username'] ?? null;
}

function login($username, $password) {
    global $db;
    
    try {
        error_log("Login attempt for username: " . $username);
        
        $stmt = $db->prepare("SELECT id, password, is_admin FROM users WHERE username = ?");
        if (!$stmt) {
            error_log("Login prepare failed: " . $db->getConnection()->error);
            return false;
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            error_log("User found, checking password...");
            error_log("Stored hash: " . $user['password']);
            error_log("Is admin: " . ($user['is_admin'] ? 'Yes' : 'No'));
            
            if (password_verify($password, $user['password'])) {
                error_log("Password verified successfully");
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                return true;
            } else {
                error_log("Invalid password for user: $username");
            }
        } else {
            error_log("User not found: $username");
        }
        return false;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function logout() {
    session_destroy();
    header('Location: /login.php');
    exit;
} 
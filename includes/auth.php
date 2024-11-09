<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login($username, $password) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND is_active = 1");
        if (!$stmt) {
            error_log("Failed to prepare login statement: " . $db->error);
            return false;
        }
        
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            error_log("Failed to execute login statement: " . $stmt->error);
            return false;
        }
        
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Update last login
                $db->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_username() {
    return $_SESSION['username'] ?? '';
}

function logout() {
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}
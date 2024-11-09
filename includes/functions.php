<?php
// Get user credits
function get_user_credits($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_row()) {
            return (int)$row[0];
        }
        return 0;
    } catch (Exception $e) {
        error_log("Error getting user credits: " . $e->getMessage());
        return 0;
    }
}

// Get user's sites
function get_user_sites($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT s.*, COUNT(b.id) as backlink_count 
            FROM sites s 
            LEFT JOIN backlinks b ON s.id = b.site_id 
            WHERE s.user_id = ? 
            GROUP BY s.id
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user sites: " . $e->getMessage());
        return [];
    }
}

// Check authentication status
function check_auth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Format time ago
function time_ago($datetime) {
    try {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    } catch (Exception $e) {
        error_log("Error formatting time: " . $e->getMessage());
        return 'unknown';
    }
}

// Safe HTML escaping
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Validate and sanitize input
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return trim(strip_tags($input));
}

// Format number
function format_number($number) {
    try {
        return number_format((float)$number);
    } catch (Exception $e) {
        error_log("Error formatting number: " . $e->getMessage());
        return '0';
    }
}
<?php
// Array helpers
function array_get($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

function array_only($array, $keys) {
    return array_intersect_key($array, array_flip((array) $keys));
}

function array_except($array, $keys) {
    return array_diff_key($array, array_flip((array) $keys));
}

// String helpers
function str_random($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function str_slug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

function str_limit($string, $limit = 100, $end = '...') {
    if (mb_strlen($string) <= $limit) {
        return $string;
    }
    return rtrim(mb_substr($string, 0, $limit)) . $end;
}

// URL helpers
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function asset($path) {
    return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
}

function redirect($url, $status = 302) {
    header('Location: ' . $url, true, $status);
    exit;
}

// Security helpers
function csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = str_random(32);
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Validation helpers
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function validate_domain($domain) {
    return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain);
}

// Date helpers
function format_date($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

function time_ago($datetime) {
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
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return format_date($datetime);
    }
}

// Number helpers
function format_number($number, $decimals = 0) {
    return number_format($number, $decimals, '.', ',');
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function format_currency($amount, $currency = '₺') {
    return $currency . number_format($amount, 2, '.', ',');
}

// File helpers
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function is_image($filename) {
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array(get_file_extension($filename), $extensions);
}

function sanitize_filename($filename) {
    // Remove any character that is not alphanumeric, dot, dash or underscore
    $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $filename);
    // Remove multiple dots
    $filename = preg_replace('/\.+/', '.', $filename);
    return $filename;
}

// Debug helpers
function dd(...$vars) {
    foreach ($vars as $var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
    exit;
}

function log_debug($message, $context = []) {
    $log = date('Y-m-d H:i:s') . ' ' . $message;
    if ($context) {
        $log .= ' ' . json_encode($context);
    }
    error_log($log);
}
?>
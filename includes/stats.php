<?php
function get_system_stats() {
    global $db;
    
    // Basic stats
    $stats = [
        'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0],
        'active_backlinks' => $db->query("SELECT COUNT(*) FROM backlinks WHERE status = 'active'")->fetch_row()[0],
        'verified_sites' => $db->query("SELECT COUNT(*) FROM sites WHERE is_verified = 1")->fetch_row()[0],
        'total_credits' => $db->query("SELECT SUM(credits) FROM users")->fetch_row()[0],
    ];
    
    // Backlinks distribution for chart
    $backlinks_query = $db->query("
        SELECT status, COUNT(*) as count 
        FROM backlinks 
        GROUP BY status
    ");
    
    $stats['chart_data']['backlinks_distribution'] = [0, 0, 0]; // [active, pending, removed]
    while ($row = $backlinks_query->fetch_assoc()) {
        switch ($row['status']) {
            case 'active':
                $stats['chart_data']['backlinks_distribution'][0] = $row['count'];
                break;
            case 'pending':
                $stats['chart_data']['backlinks_distribution'][1] = $row['count'];
                break;
            case 'removed':
                $stats['chart_data']['backlinks_distribution'][2] = $row['count'];
                break;
        }
    }
    
    // User registration trend
    $registration_query = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    
    $stats['chart_data']['registration_dates'] = [];
    $stats['chart_data']['registration_counts'] = [];
    
    while ($row = $registration_query->fetch_assoc()) {
        $stats['chart_data']['registration_dates'][] = $row['date'];
        $stats['chart_data']['registration_counts'][] = $row['count'];
    }
    
    // Recent activity
    $activity_query = $db->query("
        SELECT 
            u.username,
            al.action,
            al.details,
            al.created_at as timestamp
        FROM activity_log al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    
    $stats['recent_activity'] = [];
    while ($row = $activity_query->fetch_assoc()) {
        $row['action_color'] = get_action_color($row['action']);
        $stats['recent_activity'][] = $row;
    }
    
    return $stats;
}

function get_action_color($action) {
    $colors = [
        'login' => 'blue',
        'add_site' => 'green',
        'add_backlink' => 'indigo',
        'remove_backlink' => 'red',
        'purchase_credits' => 'purple',
        'verify_site' => 'yellow'
    ];
    
    return $colors[$action] ?? 'gray';
}

function time_ago($timestamp) {
    $time_diff = time() - strtotime($timestamp);
    
    if ($time_diff < 60) {
        return 'Just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($time_diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
<?php
// Filtering parameters
$filters = [
    'status' => $_GET['status'] ?? 'all',
    'type' => $_GET['type'] ?? 'all',
    'search' => $_GET['search'] ?? '',
    'da_min' => $_GET['da_min'] ?? '',
    'da_max' => $_GET['da_max'] ?? '',
    'pa_min' => $_GET['pa_min'] ?? '',
    'pa_max' => $_GET['pa_max'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? ''
];

// Build query conditions
$where = [];
$params = [];
$types = '';

if ($filters['status'] !== 'all') {
    $where[] = "l.status = ?";
    $params[] = $filters['status'];
    $types .= 's';
}

if ($filters['type'] !== 'all') {
    $where[] = "l.type = ?";
    $params[] = $filters['type'];
    $types .= 's';
}

if ($filters['search']) {
    $where[] = "(s.domain LIKE ? OR l.anchor_text LIKE ?)";
    $params[] = "%{$filters['search']}%";
    $params[] = "%{$filters['search']}%";
    $types .= 'ss';
}

if ($filters['da_min'] !== '') {
    $where[] = "s.da >= ?";
    $params[] = $filters['da_min'];
    $types .= 'i';
}

if ($filters['da_max'] !== '') {
    $where[] = "s.da <= ?";
    $params[] = $filters['da_max'];
    $types .= 'i';
}

if ($filters['pa_min'] !== '') {
    $where[] = "s.pa >= ?";
    $params[] = $filters['pa_min'];
    $types .= 'i';
}

if ($filters['pa_max'] !== '') {
    $where[] = "s.pa <= ?";
    $params[] = $filters['pa_max'];
    $types .= 'i';
}

if ($filters['price_min'] !== '') {
    $where[] = "l.price >= ?";
    $params[] = $filters['price_min'];
    $types .= 'i';
}

if ($filters['price_max'] !== '') {
    $where[] = "l.price <= ?";
    $params[] = $filters['price_max'];
    $types .= 'i';
}

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Build final query
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "
    SELECT l.*, s.domain, s.da, s.pa,
           COUNT(DISTINCT b.id) as active_backlinks,
           AVG(m.loading_time) as avg_loading_time,
           EXISTS(SELECT 1 FROM link_favorites f WHERE f.link_id = l.id AND f.user_id = ?) as is_favorite
    FROM links l
    JOIN sites s ON l.site_id = s.id
    LEFT JOIN backlinks b ON s.id = b.site_id AND b.status = 'active'
    LEFT JOIN link_metrics m ON l.id = m.link_id
    $where_clause
    GROUP BY l.id
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";

// Add pagination parameters
$params = array_merge([$_SESSION['user_id']], $params, [$limit, $offset]);
$types .= 'ii';

// Execute query
$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$count_query = "
    SELECT COUNT(DISTINCT l.id) 
    FROM links l 
    JOIN sites s ON l.site_id = s.id 
    $where_clause
";
$stmt = $db->prepare($count_query);
if ($params) {
    $stmt->bind_param(substr($types, 0, -2), ...array_slice($params, 1, -2));
}
$stmt->execute();
$total = $stmt->get_result()->fetch_row()[0];

echo json_encode([
    'success' => true,
    'links' => $links,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => ceil($total / $limit),
        'total_items' => $total
    ]
]);
?>
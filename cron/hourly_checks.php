<?php
require_once '../includes/init.php';
require_once '../includes/link_monitor.php';

// Check expired links
$db->query("
    UPDATE links 
    SET status = 'expired' 
    WHERE status = 'active' 
    AND expires_at < NOW()
");

// Check link metrics
$links = $db->query("
    SELECT l.id, s.domain 
    FROM links l 
    JOIN sites s ON l.site_id = s.id 
    WHERE l.status = 'active' 
    AND (
        l.last_checked IS NULL 
        OR l.last_checked < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    )
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

foreach ($links as $link) {
    $ch = curl_init("https://" . $link['domain']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $start = microtime(true);
    $content = curl_exec($ch);
    $loading_time = microtime(true) - $start;
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $db->query("
        INSERT INTO link_metrics (
            link_id, 
            response_time, 
            status_code,
            checked_at
        ) VALUES (
            {$link['id']},
            $loading_time,
            $status_code,
            NOW()
        )
    ");
    
    // Update link status if needed
    if ($status_code !== 200) {
        $db->query("UPDATE links SET status = 'error' WHERE id = {$link['id']}");
        
        // Notify user
        $db->query("
            INSERT INTO notifications (
                user_id,
                type,
                message,
                link_id
            ) SELECT 
                s.user_id,
                'link_error',
                'Link error detected: HTTP $status_code',
                {$link['id']}
            FROM links l
            JOIN sites s ON l.site_id = s.id
            WHERE l.id = {$link['id']}
        ");
    }
    
    // Add delay between checks
    sleep(1);
}

// Update site metrics
$db->query("
    UPDATE sites s
    SET 
        active_links = (
            SELECT COUNT(*) 
            FROM links l 
            WHERE l.site_id = s.id 
            AND l.status = 'active'
        ),
        total_links = (
            SELECT COUNT(*) 
            FROM links l 
            WHERE l.site_id = s.id
        ),
        updated_at = NOW()
");
?>
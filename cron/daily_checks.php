<?php
require_once '../includes/init.php';
require_once '../includes/link_monitor.php';
require_once '../includes/quality_checker.php';
require_once '../includes/mailer.php';

// Initialize components
$mailer = new Mailer();
$quality_checker = new QualityChecker($db);
$monitor = new LinkMonitor($db, $mailer, $quality_checker);

// Start monitoring
$monitor->monitorLinks();

// Cleanup old data
$db->query("DELETE FROM rate_limits WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)");
$db->query("DELETE FROM link_metrics WHERE checked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

// Generate daily report
$report = [
    'checked_links' => $db->query("SELECT COUNT(*) FROM link_metrics WHERE DATE(checked_at) = CURDATE()")->fetch_row()[0],
    'active_links' => $db->query("SELECT COUNT(*) FROM links WHERE status = 'active'")->fetch_row()[0],
    'removed_links' => $db->query("SELECT COUNT(*) FROM links WHERE status = 'removed' AND DATE(updated_at) = CURDATE()")->fetch_row()[0],
    'new_users' => $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetch_row()[0]
];

// Send report to admin
$admin_email = $db->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1")->fetch_row()[0];
if ($admin_email) {
    $subject = "Daily System Report - " . date('Y-m-d');
    $message = "Daily System Report\n\n";
    $message .= "Checked Links: {$report['checked_links']}\n";
    $message .= "Active Links: {$report['active_links']}\n";
    $message .= "Removed Links: {$report['removed_links']}\n";
    $message .= "New Users: {$report['new_users']}\n";
    
    $mailer->send($admin_email, $subject, $message);
}
?>
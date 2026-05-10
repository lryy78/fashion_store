<?php
require_once 'config/db.php';

// Check MySQL NOW() vs PHP time
$mysql_now = $pdo->query("SELECT NOW(), @@global.time_zone, @@session.time_zone")->fetch(PDO::FETCH_NUM);
echo "MySQL NOW():  " . $mysql_now[0] . "\n";
echo "MySQL tz (global): " . $mysql_now[1] . "\n";
echo "MySQL tz (session): " . $mysql_now[2] . "\n";
echo "PHP   time(): " . date('Y-m-d H:i:s') . "\n\n";

// Check existing token in users table
$row = $pdo->query("SELECT username, reset_token, reset_expires, NOW() as now FROM users WHERE reset_token IS NOT NULL LIMIT 1")->fetch();
if ($row) {
    echo "User: {$row['username']}\n";
    echo "reset_expires: {$row['reset_expires']}\n";
    echo "NOW():         {$row['now']}\n";
    echo "Is valid? " . ($row['reset_expires'] > $row['now'] ? 'YES' : 'NO') . "\n";
} else {
    echo "No user has a reset token set.\n";
}
?>

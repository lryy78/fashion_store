<?php
require_once 'config/db.php';
echo "<pre>";
print_r($pdo->query('DESCRIBE products')->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>

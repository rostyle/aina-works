<?php
require_once 'config/config.php';
$db = Database::getInstance();
try {
    $cols = $db->select("SHOW COLUMNS FROM jobs");
    echo "<h1>Jobs Table Schema</h1>";
    echo "<pre>";
    print_r($cols);
    echo "</pre>";

    $statusCol = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'status'");
    echo "<h1>Status Column</h1>";
    echo "<pre>";
    print_r($statusCol);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

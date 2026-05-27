<?php

// Direct MySQL connection
$host = '127.0.0.1';
$dbname = 'coopos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Add role column to users table
    $sql = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'cashier') DEFAULT 'cashier' AFTER email";
    $pdo->exec($sql);
    
    echo "Role field added successfully to users table.\n";
    
} catch (PDOException $e) {
    echo "Error adding role field: " . $e->getMessage() . "\n";
}

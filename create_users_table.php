<?php

// Direct MySQL connection
$host = '127.0.0.1';
$dbname = 'coopos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Create users table with role field
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        email_verified_at TIMESTAMP NULL,
        password VARCHAR(255) NOT NULL,
        remember_token VARCHAR(100) NULL,
        role ENUM('admin', 'cashier') DEFAULT 'cashier' NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )";
    $pdo->exec($sql);
    
    echo "Users table created successfully with role field.\n";
    
} catch (PDOException $e) {
    echo "Error creating users table: " . $e->getMessage() . "\n";
}

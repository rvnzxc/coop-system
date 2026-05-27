<?php

// Direct MySQL connection
$host = '127.0.0.1';
$dbname = 'coopos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Create admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Admin User', 'admin@coop.com', $adminPassword, 'admin']);
    
    // Create cashier user
    $cashierPassword = password_hash('cashier123', PASSWORD_DEFAULT);
    $stmt->execute(['Cashier User', 'cashier@coop.com', $cashierPassword, 'cashier']);
    
    echo "Test users created successfully:\n";
    echo "Admin: admin@coop.com / admin123\n";
    echo "Cashier: cashier@coop.com / cashier123\n";
    
} catch (PDOException $e) {
    echo "Error creating test users: " . $e->getMessage() . "\n";
}

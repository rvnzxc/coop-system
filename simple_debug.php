<?php

echo "Simple Debug Script\n";
echo "==================\n\n";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    
    // Check today's purchases with different queries
    echo "1. Testing Today's Purchases:\n";
    
    // Query 1: Simple date check
    $stmt = $pdo->query("SELECT COUNT(*) as count, SUM(amount) as total FROM purchases WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Today (CURDATE): {$result['count']} purchases, ₱{$result['total']}\n";
    
    // Query 2: Using specific date
    $today = date('Y-m-d');
    $stmt = $pdo->query("SELECT COUNT(*) as count, SUM(amount) as total FROM purchases WHERE DATE(created_at) = '$today'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Today ('$today'): {$result['count']} purchases, ₱{$result['total']}\n";
    
    // Query 3: Hourly breakdown
    echo "\n2. Hourly Breakdown Today:\n";
    $stmt = $pdo->query("SELECT HOUR(created_at) as hour, SUM(amount) as total FROM purchases WHERE DATE(created_at) = CURDATE() GROUP BY HOUR(created_at) ORDER BY hour");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "   Hour {$result['hour']}: ₱{$result['total']}\n";
    }
    
    // Check if created_at column format is correct
    echo "\n3. Recent Purchase Timestamps:\n";
    $stmt = $pdo->query("SELECT created_at, amount FROM purchases ORDER BY created_at DESC LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "   {$result['created_at']} - ₱{$result['amount']}\n";
    }
    
    // Test the exact query from the controller
    echo "\n4. Testing Controller Query:\n";
    $stmt = $pdo->query("SELECT HOUR(created_at) as grp, SUM(amount) as value FROM purchases WHERE created_at >= CURDATE() AND created_at <= CURDATE() + INTERVAL 1 DAY GROUP BY HOUR(created_at) ORDER BY grp");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Results: " . count($results) . " rows\n";
    foreach ($results as $result) {
        echo "   Hour {$result['grp']}: ₱{$result['value']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Debug complete!\n";
?>

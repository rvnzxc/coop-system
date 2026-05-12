<?php

echo "Testing Specific Date\n";
echo "===================\n\n";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    
    // Test with the exact date from the logs
    $testDate = '2026-05-12';
    echo "Testing with date: $testDate\n";
    
    // Query 1: Check purchases on that date
    $stmt = $pdo->query("SELECT COUNT(*) as count, SUM(amount) as total FROM purchases WHERE DATE(created_at) = '$testDate'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Purchases on $testDate: {$result['count']} purchases, ₱{$result['total']}\n";
    
    // Query 2: Hourly breakdown
    echo "\nHourly breakdown for $testDate:\n";
    $stmt = $pdo->query("SELECT HOUR(created_at) as hour, SUM(amount) as total FROM purchases WHERE DATE(created_at) = '$testDate' GROUP BY HOUR(created_at) ORDER BY hour");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "  Hour {$result['hour']}: ₱{$result['total']}\n";
    }
    
    // Query 3: Test the exact query format
    echo "\nTesting exact query format:\n";
    $stmt = $pdo->query("SELECT HOUR(created_at) as grp, SUM(amount) as value FROM purchases WHERE created_at >= '$testDate 00:00:00' AND created_at <= '$testDate 23:59:59' GROUP BY HOUR(created_at) ORDER BY grp");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Results: " . count($results) . " rows\n";
    foreach ($results as $result) {
        echo "  Hour {$result['grp']}: ₱{$result['value']}\n";
    }
    
    // Query 4: Check if there are any purchases with different time zones
    echo "\nChecking all purchase timestamps:\n";
    $stmt = $pdo->query("SELECT created_at, amount FROM purchases ORDER BY created_at DESC LIMIT 10");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "  {$result['created_at']} - ₱{$result['amount']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Test complete!\n";
?>

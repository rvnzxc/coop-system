<?php

echo "Testing Analytics API\n";
echo "====================\n\n";

// Test 1: Check if API is accessible
echo "1. Testing API Endpoint:\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$url = 'http://localhost:8000/api/analytics/sales?period=daily';
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "✗ API endpoint not accessible\n";
    echo "   Server might not be running or API route issue\n";
} else {
    echo "✓ API endpoint accessible\n";
    echo "   Response length: " . strlen($response) . " bytes\n";
    
    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data === null) {
        echo "✗ Invalid JSON response\n";
        echo "   Raw response: " . substr($response, 0, 200) . "...\n";
    } else {
        echo "✓ Valid JSON response\n";
        if (isset($data['data'])) {
            echo "   Data points: " . count($data['data']) . "\n";
            echo "   Total revenue: " . ($data['total_revenue'] ?? 0) . "\n";
            echo "   Sample data: " . json_encode(array_slice($data['data'], 0, 3)) . "\n";
        } else {
            echo "✗ No data field in response\n";
            echo "   Response: " . json_encode($data) . "\n";
        }
    }
}

echo "\n2. Checking Database:\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM purchases");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Purchases in database: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT created_at, amount FROM purchases ORDER BY created_at DESC LIMIT 3");
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Recent purchases:\n";
        foreach ($purchases as $purchase) {
            echo "   - " . $purchase['created_at'] . " - ₱" . $purchase['amount'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Other Periods:\n";
$periods = ['daily', 'weekly', 'monthly', 'yearly'];
foreach ($periods as $period) {
    $url = "http://localhost:8000/api/analytics/sales?period={$period}";
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        $dataCount = isset($data['data']) ? count($data['data']) : 0;
        echo "   {$period}: {$dataCount} data points\n";
    } else {
        echo "   {$period}: Failed\n";
    }
}

echo "\n✅ API testing complete!\n";
?>

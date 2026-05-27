<?php

echo "Testing All Time Periods\n";
echo "======================\n\n";

$periods = ['daily', 'weekly', 'monthly', 'yearly'];

foreach ($periods as $period) {
    echo "Testing $period:\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);

    $url = "http://localhost:8000/api/analytics/sales?period={$period}";
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo "  ✗ API endpoint not accessible\n";
    } else {
        $data = json_decode($response, true);
        if ($data === null) {
            echo "  ✗ Invalid JSON response\n";
            echo "    Raw response: " . substr($response, 0, 200) . "...\n";
        } else {
            echo "  ✓ Valid JSON response\n";
            if (isset($data['data'])) {
                echo "    Data points: " . count($data['data']) . "\n";
                echo "    Total revenue: " . ($data['total_revenue'] ?? 0) . "\n";
                
                // Show sample data
                $sampleData = array_slice($data['data'], 0, 3);
                foreach ($sampleData as $item) {
                    echo "    - {$item['label']}: {$item['value']}\n";
                }
                
                // Check if all values are 0
                $nonZeroValues = array_filter($data['data'], function($item) {
                    return $item['value'] > 0;
                });
                echo "    Non-zero data points: " . count($nonZeroValues) . "\n";
            } else {
                echo "  ✗ No data field in response\n";
                echo "    Response: " . json_encode($data) . "\n";
            }
        }
    }
    echo "\n";
}

echo "✅ Testing complete!\n";
?>

<?php

// Direct MySQL connection
$host = '127.0.0.1';
$dbname = 'coopos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Check if products table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemCount = $result['count'];
    
    echo "Current item count: $itemCount\n";
    
    if ($itemCount == 0) {
        echo "No items found in inventory. Creating sample items...\n";
        
        // Create sample items
        $items = [
            ['Coca Cola', 'Beverages', 25.00, 100],
            ['Pepsi', 'Beverages', 25.00, 100],
            ['Bread', 'Essentials', 35.00, 50],
            ['Rice', 'Essentials', 50.00, 100],
            ['Soap', 'Personal Care', 20.00, 75],
            ['Shampoo', 'Personal Care', 45.00, 60],
            ['Instant Noodles', 'Snacks', 12.00, 200],
            ['Chips', 'Snacks', 15.00, 150]
        ];
        
        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO items (item_name, category, price, quantity, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$item[0], $item[1], $item[2], $item[3]]);
            echo "Created item: {$item[0]} (₱{$item[2]}, {$item[3]} units)\n";
        }
        
        echo "\nSample items created successfully!\n";
    } else {
        echo "Items already exist in inventory.\n";
    }
    
    // Show existing items
    $stmt = $pdo->query("SELECT item_name, category, price, quantity FROM items LIMIT 5");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample items for testing:\n";
    foreach ($items as $item) {
        echo "- {$item['item_name']} ({$item['category']}) - ₱{$item['price']} ({$item['quantity']} units)\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

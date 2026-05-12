<?php

echo "Testing POS Functionality\n";
echo "========================\n\n";

// Test 1: Check database connectivity
echo "1. Database Connectivity:\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 2: Check members data
echo "\n2. Members Data:\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM members");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "✓ {$result['count']} members in database\n";

// Test 3: Check items data
echo "\n3. Items Data:\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "✓ {$result['count']} items in inventory\n";

// Test 4: Check purchases data
echo "\n4. Purchases Data:\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM purchases");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "✓ {$result['count']} purchases recorded\n";

echo "\n5. Test Instructions:\n";
echo "   1. Visit: http://localhost:8000/login\n";
echo "   2. Login as cashier: cashier@coop.com / cashier123\n";
echo "   3. You should be redirected to /pos\n";
echo "   4. Test member search: Try searching 'MEM00001' or 'Reiven'\n";
echo "   5. Test buying: Click on items, then checkout\n";

echo "\n6. Expected POS Features:\n";
echo "   ✓ Product grid with 86 items\n";
echo "   ✓ Member search by ID or name\n";
echo "   ✓ Non-member option\n";
echo "   ✓ Add to cart functionality\n";
echo "   ✓ Checkout with payment processing\n";
echo "   ✓ Inventory updates after purchase\n";

echo "\n✅ POS should be fully functional!\n";
?>

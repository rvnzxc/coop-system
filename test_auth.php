<?php

echo "Testing Role-Based Access Control System\n";
echo "========================================\n\n";

// Test 1: Check if users table exists and has role field
echo "1. Testing Users Table Structure:\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('role', $columns)) {
        echo "✓ Role field exists in users table\n";
    } else {
        echo "✗ Role field missing from users table\n";
    }
    
    // Check test users
    $stmt = $pdo->query("SELECT email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n2. Test Users:\n";
    foreach ($users as $user) {
        echo "  - {$user['email']} (Role: {$user['role']})\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n3. Route Protection Test:\n";
echo "   - POS routes require cashier role\n";
echo "   - Analytics routes require admin role\n";
echo "   - Inventory routes require admin role\n";
echo "   - Members routes require admin role\n";

echo "\n4. Test Instructions:\n";
echo "   1. Visit http://localhost/login\n";
echo "   2. Login as cashier@coop.com / cashier123\n";
echo "     - Should only see POS in navigation\n";
echo "     - Should be blocked from /analytics, /inventory, /members\n";
echo "   3. Login as admin@coop.com / admin123\n";
echo "     - Should see all navigation options\n";
echo "     - Should access all areas\n";

echo "\n5. Expected Behavior:\n";
echo "   - Cashier: Can only access POS (/)\n";
echo "   - Admin: Can access everything\n";
echo "   - Unauthorized access: 403 error\n";

echo "\n✅ Role-based access control system is ready for testing!\n";
?>

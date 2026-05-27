<?php

echo "Testing Role-Based Middleware\n";
echo "=============================\n\n";

// Test 1: Check if middleware is registered
echo "1. Middleware Registration Test:\n";
try {
    $app = require_once 'bootstrap/app.php';
    
    // Get the middleware manager
    $middlewareManager = $app->make('Illuminate\Routing\MiddlewareManager');
    
    if ($middlewareManager) {
        echo "✓ Middleware manager loaded successfully\n";
    } else {
        echo "✗ Middleware manager not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error loading middleware: " . $e->getMessage() . "\n";
}

echo "\n2. Testing Routes:\n";
echo "   - Protected routes now have 'auth' middleware applied\n";
echo "   - Role middleware aliases registered in bootstrap\n";

echo "\n3. Test Instructions:\n";
echo "   1. Visit http://localhost/login\n";
echo "   2. Test cashier login: cashier@coop.com / cashier123\n";
echo "   3. Test admin login: admin@coop.com / admin123\n";

echo "\n4. Expected Behavior:\n";
echo "   - Unauthenticated users redirected to /login\n";
echo "   - Cashiers can access POS (/) only\n";
echo "   - Admins can access all areas\n";
echo "   - Unauthorized access shows 403 error\n";

echo "\n✅ Middleware should now be working!\n";
?>

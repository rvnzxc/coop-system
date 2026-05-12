<?php

echo "Debugging Analytics Queries\n";
echo "==========================\n\n";

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Load Laravel
require_once 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';

// Test direct queries
echo "1. Testing Direct Database Queries:\n";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    
    // Check today's purchases
    echo "   Today's purchases:\n";
    $stmt = $pdo->query("SELECT HOUR(created_at) as hour, SUM(amount) as total FROM purchases WHERE DATE(created_at) = CURDATE() GROUP BY HOUR(created_at) ORDER BY hour");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "     Hour {$result['hour']}: ₱{$result['total']}\n";
    }
    
    // Check last 7 days
    echo "\n   Last 7 days purchases:\n";
    $stmt = $pdo->query("SELECT DATE(created_at) as date, SUM(amount) as total FROM purchases WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "     {$result['date']}: ₱{$result['total']}\n";
    }
    
    // Check current month
    echo "\n   Current month purchases:\n";
    $stmt = $pdo->query("SELECT DATE(created_at) as date, SUM(amount) as total FROM purchases WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) GROUP BY DATE(created_at) ORDER BY date LIMIT 10");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        echo "     {$result['date']}: ₱{$result['total']}\n";
    }
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing Laravel Query Builder:\n";
try {
    // Test Laravel's Carbon and query builder
    $today = Carbon::now()->format('Y-m-d');
    echo "   Today (Carbon): $today\n";
    
    $rows = DB::table('purchases')
        ->selectRaw("HOUR(created_at) as grp, SUM(amount) as value")
        ->where('created_at', '>=', Carbon::now()->startOfDay())
        ->where('created_at', '<=', Carbon::now()->endOfDay())
        ->groupBy('grp')
        ->orderBy('grp')
        ->get();
    
    echo "   Laravel daily query results: " . $rows->count() . " rows\n";
    foreach ($rows as $row) {
        echo "     Hour {$row->grp}: ₱{$row->value}\n";
    }
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Purchase Table Structure:\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=coopos", 'root', '');
    $stmt = $pdo->query("DESCRIBE purchases");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Purchase table columns:\n";
    foreach ($columns as $column) {
        echo "     - {$column['Field']} ({$column['Type']})\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Debug complete!\n";
?>

<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Add role column to users table
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    Schema::table('users', function ($table) {
        $table->enum('role', ['admin', 'cashier'])->default('cashier')->after('email');
    });
    
    echo "Role field added to users table successfully.\n";
} catch (Exception $e) {
    echo "Error adding role field: " . $e->getMessage() . "\n";
}

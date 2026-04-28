<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Create products table
    \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->id();
        $table->string('item_name');
        $table->integer('quantity');
        $table->decimal('price', 8, 2)->default(0.00);
        $table->string('category')->nullable();
        $table->timestamps();
    });
    echo "Products table created successfully\n";
} catch (\Exception $e) {
    echo "Error creating products table: " . $e->getMessage() . "\n";
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('sales', 'receipt_number')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique(['receipt_number']);
                $table->dropColumn('receipt_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('sales', 'receipt_number')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('receipt_number')->nullable()->unique()->after('id');
            });
        }
    }
};

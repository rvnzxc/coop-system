<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY COLUMN id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        // No safe way to reverse this
    }
};

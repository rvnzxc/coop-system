<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('customer_type')->after('member_number')->default('member')->index();
        });

        // Backfill existing records: if member_id is present, mark as member
        DB::table('purchases')
            ->whereNull('customer_type')
            ->update(['customer_type' => 'member']);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }
};

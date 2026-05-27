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
        Schema::table('members', function (Blueprint $table) {
            $table->string('member_number')->unique()->after('id');
        });

        // Update existing members to have member numbers
        \DB::table('members')->orderBy('id')->get()->each(function ($member) {
            \DB::table('members')
                ->where('id', $member->id)
                ->update(['member_number' => '#' . str_pad($member->id, 5, '0', STR_PAD_LEFT)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('member_number');
        });
    }
};

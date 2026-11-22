<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('credit_payments');

        if (! Schema::hasTable('credits')) {
            return;
        }

        if (Schema::hasIndex('credits', ['status'])) {
            Schema::table('credits', function (Blueprint $table) {
                $table->dropIndex(['status']);
            });
        }

        $columns = array_values(array_filter(
            ['amount_paid', 'status'],
            fn (string $column): bool => Schema::hasColumn('credits', $column)
        ));

        if ($columns !== []) {
            Schema::table('credits', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('credits')) {
            $missingColumns = array_values(array_filter(
                ['amount_paid', 'status'],
                fn (string $column): bool => ! Schema::hasColumn('credits', $column)
            ));

            if ($missingColumns !== []) {
                Schema::table('credits', function (Blueprint $table) use ($missingColumns) {
                    if (in_array('amount_paid', $missingColumns, true)) {
                        $table->decimal('amount_paid', 10, 2)->default(0)->after('amount');
                    }

                    if (in_array('status', $missingColumns, true)) {
                        $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('amount_paid')->index();
                    }
                });
            }
        }

        if (! Schema::hasTable('credit_payments')) {
            Schema::create('credit_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credit_id')->constrained('credits')->onDelete('cascade');
                $table->decimal('amount_paid', 10, 2);
                $table->timestamp('paid_at');
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }
};

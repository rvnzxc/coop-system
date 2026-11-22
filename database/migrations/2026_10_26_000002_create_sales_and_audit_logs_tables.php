<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('member_number')->nullable();
            $table->string('customer_type')->default('member')->index();
            $table->string('payment_method', 20)->default('cash')->index();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 20)->default('completed')->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index('cashier_id');
            $table->index('member_id');
            $table->index('created_at');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('sale_id');
            $table->index('item_id');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->index();
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });

        $this->backfillLegacySales();
    }

    /**
     * Turn each legacy purchase row into a sale so old transactions remain
     * visible. Cashier is unknown for these because purchases never recorded
     * who rang them up.
     */
    private function backfillLegacySales(): void
    {
        DB::table('purchases')
            ->orderBy('id')
            ->chunkById(200, function ($purchases) {
                foreach ($purchases as $purchase) {
                    $quantity = max(1, (int) ($purchase->quantity ?? 1));
                    $amount = (float) $purchase->amount;
                    $unitPrice = round($amount / $quantity, 2);

                    $saleId = DB::table('sales')->insertGetId([
                        'cashier_id' => null,
                        'member_id' => $purchase->member_id,
                        'member_number' => $purchase->member_number,
                        'customer_type' => $purchase->customer_type ?: ($purchase->member_id ? 'member' : 'non_member'),
                        'payment_method' => $purchase->payment_method ?: 'cash',
                        'total' => $amount,
                        'status' => 'completed',
                        'created_at' => $purchase->created_at ?: $purchase->purchase_date,
                        'updated_at' => $purchase->updated_at ?: $purchase->created_at ?: $purchase->purchase_date,
                    ]);

                    DB::table('sale_items')->insert([
                        'sale_id' => $saleId,
                        'item_id' => null,
                        'product_name' => $purchase->product_name ?: 'Unknown item',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $amount,
                        'created_at' => $purchase->created_at ?: $purchase->purchase_date,
                        'updated_at' => $purchase->updated_at ?: $purchase->created_at ?: $purchase->purchase_date,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};

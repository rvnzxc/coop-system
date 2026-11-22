<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        if (! Schema::hasColumn('items', 'barcode')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('barcode', 64)->nullable()->after('item_name');
            });
        }

        if (! Schema::hasColumn('items', 'manufacturer_barcode')) {
            Schema::table('items', function (Blueprint $table) {
                // Stored as a string on purpose. A UPC-A such as 012345678905
                // carries a leading zero that an integer column would discard,
                // and EAN/UPC values are identifiers rather than quantities.
                $table->string('manufacturer_barcode', 64)->nullable()->after('barcode');
            });
        }

        // Existing items need a barcode before they can be scanned, so give
        // every row a unique value up front. This is inlined rather than
        // delegating to Item::generateBarcode() so a future model change
        // cannot silently alter what this migration wrote.
        DB::table('items')
            ->where(function ($query) {
                $query->whereNull('barcode')->orWhere('barcode', '');
            })
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    DB::table('items')
                        ->where('id', $item->id)
                        ->update(['barcode' => $this->generateBarcode()]);
                }
            });

        if (! Schema::hasIndex('items', 'items_barcode_unique')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique('barcode', 'items_barcode_unique');
            });
        }

        // A packaging barcode identifies one specific product and size, so a
        // duplicate means the wrong item was linked rather than a legitimate
        // variant. MySQL allows unlimited NULLs, so items that simply have no
        // packaging barcode are unaffected by this index.
        if (! Schema::hasIndex('items', 'items_manufacturer_barcode_unique')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique('manufacturer_barcode', 'items_manufacturer_barcode_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        if (Schema::hasIndex('items', 'items_manufacturer_barcode_unique')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropUnique('items_manufacturer_barcode_unique');
            });
        }

        if (Schema::hasIndex('items', 'items_barcode_unique')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropUnique('items_barcode_unique');
            });
        }

        if (Schema::hasColumn('items', 'manufacturer_barcode')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('manufacturer_barcode');
            });
        }

        if (Schema::hasColumn('items', 'barcode')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('barcode');
            });
        }
    }

    /**
     * Produce a barcode value that is not already taken.
     */
    private function generateBarcode(): string
    {
        do {
            $barcode = 'P'.random_int(100000, 999999);
        } while (DB::table('items')->where('barcode', $barcode)->exists());

        return $barcode;
    }
};

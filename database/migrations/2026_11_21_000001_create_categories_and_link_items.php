<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        } elseif (! Schema::hasIndex('categories', ['name'], 'unique')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique('name');
            });
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $idColumn = collect(DB::select("SHOW COLUMNS FROM categories LIKE 'id'"))->first();

            if ($idColumn && stripos((string) ($idColumn->Extra ?? ''), 'auto_increment') === false) {
                DB::statement('ALTER TABLE categories MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            }
        }

        if (DB::table('categories')->count() === 0) {
            foreach (['disposable', 'condiments', 'frozen', 'canned', 'laundry', 'personal-care', 'snacks', 'ice-cream', 'biscuits', 'beverages', 'candy', 'essentials'] as $name) {
                DB::table('categories')->insert([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('items')
            ->select('category')
            ->whereNotNull('category')
            ->whereRaw("TRIM(category) <> ''")
            ->orderBy('category')
            ->get()
            ->pluck('category')
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->each(function (string $name): void {
                $exists = DB::table('categories')
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                    ->exists();

                if (! $exists) {
                    DB::table('categories')->insert([
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        if (! Schema::hasColumn('items', 'category_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('category');
                $table->index('category_id');
            });
        }

        DB::table('items')
            ->select('id', 'category')
            ->whereNotNull('category')
            ->whereRaw("TRIM(category) <> ''")
            ->orderBy('id')
            ->chunkById(500, function ($items): void {
                foreach ($items as $item) {
                    $categoryId = DB::table('categories')
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $item->category))])
                        ->value('id');

                    if ($categoryId !== null) {
                        DB::table('items')->where('id', $item->id)->update(['category_id' => $categoryId]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('items', 'category_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropIndex(['category_id']);
                $table->dropColumn('category_id');
            });
        }
    }
};

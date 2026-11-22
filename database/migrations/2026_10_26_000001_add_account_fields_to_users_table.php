<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 64)->nullable()->unique()->after('name');
            });
        }

        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('role');
            });
        }

        // Accounts are username based now, so an email address is optional.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        $used = DB::table('users')
            ->whereNotNull('username')
            ->pluck('username')
            ->map(fn ($username) => mb_strtolower($username))
            ->all();

        DB::table('users')->orderBy('id')->get()->each(function ($user) use (&$used) {
            if (! blank($user->username)) {
                return;
            }

            $base = '';

            if (! blank($user->email)) {
                $base = Str::slug(Str::before($user->email, '@'), '_');
            }

            if ($base === '') {
                $base = Str::slug((string) $user->name, '_');
            }

            $base = mb_strtolower(preg_replace('/[^A-Za-z0-9._-]/', '', $base) ?: 'user');
            $base = $base === '' ? 'user' : $base;
            $candidate = $base;
            $suffix = 1;

            while (in_array($candidate, $used, true)) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            $used[] = $candidate;

            DB::table('users')->where('id', $user->id)->update([
                'username' => $candidate,
                'is_active' => true,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('email')->orderBy('id')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'email' => 'user'.$user->id.'@local',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });

        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_username_unique');
                $table->dropColumn(['username', 'is_active']);
            });
        }
    }
};

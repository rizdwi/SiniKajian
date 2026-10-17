<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('avatar')->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('kajians', 'event_datetime')) {
            Schema::table('kajians', function (Blueprint $table) {
                $table->dateTime('event_datetime')->nullable()->after('time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }

        if (Schema::hasColumn('kajians', 'event_datetime')) {
            Schema::table('kajians', function (Blueprint $table) {
                $table->dropColumn('event_datetime');
            });
        }
    }
};

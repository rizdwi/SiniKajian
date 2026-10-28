<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follow ustadz — cermin lokal dari tabel Supabase ustadz_follows
     * (Segmen No.5, 2026-10-28). Pola sama dengan majelis_follows:
     * user login pakai user_id, tamu pakai session_token.
     */
    public function up(): void
    {
        Schema::create('ustadz_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token')->nullable()->index();
            $table->string('ustadz')->index();
            $table->timestamps();

            $table->index(['user_id', 'ustadz']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ustadz_follows');
    }
};

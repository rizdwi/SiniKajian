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
        Schema::create('kajians', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('ustadz');
            $table->string('category')->index();
            $table->string('audience')->default('Umum');
            $table->string('masjid');
            $table->text('address');
            $table->double('lat');
            $table->double('lon');
            $table->string('day');
            $table->string('time');
            $table->text('description')->nullable();
            $table->boolean('verified')->default(false)->index();
            $table->string('source')->default('facebook');
            $table->string('source_url')->nullable();
            $table->string('status')->default('aktif');
            $table->text('alasan_batal')->nullable();
            $table->boolean('is_rutin')->default(true);
            $table->string('link_video')->nullable();
            $table->string('poster_url')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lat', 'lon']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token')->nullable()->index();
            $table->foreignId('kajian_id')->constrained('kajians')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'kajian_id']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token')->nullable()->index();
            $table->foreignId('kajian_id')->constrained('kajians')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamps();

            $table->index(['user_id', 'kajian_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('kajians');
    }
};

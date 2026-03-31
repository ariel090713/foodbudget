<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->integer('duration_days')->default(30); // how many days of premium
            $table->integer('max_uses')->default(1); // 0 = unlimited
            $table->integer('times_used')->default(0);
            $table->timestamp('expires_at')->nullable(); // null = never expires
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Track which users redeemed which codes
        Schema::create('promo_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promo_code_id', 'user_id']); // 1 redemption per user per code
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_redemptions');
        Schema::dropIfExists('promo_codes');
    }
};

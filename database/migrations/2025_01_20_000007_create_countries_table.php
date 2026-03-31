<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->string('code', 2)->primary(); // ISO 3166-1 alpha-2
            $table->string('name');
            $table->string('currency_code', 3); // ISO 4217
            $table->string('currency_symbol', 10);
            $table->string('currency_name');
            $table->decimal('tier_poor_min', 10, 2)->default(2.15);
            $table->decimal('tier_middle_class_min', 10, 2)->default(10);
            $table->decimal('tier_rich_min', 10, 2)->default(50);
            $table->boolean('prices_populated')->default(false);
            $table->timestamp('prices_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};

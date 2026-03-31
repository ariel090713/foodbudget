<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_prices', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('item_name'); // e.g. "Rice", "Egg", "Chicken"
            $table->string('local_name')->nullable(); // e.g. "Bigas", "Itlog"
            $table->string('unit'); // e.g. "1 kg", "1 pc", "1 L"
            $table->decimal('price_min', 10, 2);
            $table->decimal('price_max', 10, 2);
            $table->string('currency_code', 3);
            $table->string('category'); // staple, protein, vegetable, fruit, condiment, snack, dairy, beverage
            $table->boolean('is_common')->default(true);
            $table->timestamps();

            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnDelete();
            $table->index(['country_code', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_prices');
    }
};

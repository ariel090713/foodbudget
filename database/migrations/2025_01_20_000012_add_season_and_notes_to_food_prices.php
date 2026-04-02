<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_prices', function (Blueprint $table) {
            $table->string('season')->nullable()->after('is_common'); // wet, dry, summer, winter, spring, autumn, all_year
            $table->string('availability')->default('all_year')->after('season'); // all_year, seasonal, limited
            $table->text('notes')->nullable()->after('availability'); // AI notes about the item
        });
    }

    public function down(): void
    {
        Schema::table('food_prices', function (Blueprint $table) {
            $table->dropColumn(['season', 'availability', 'notes']);
        });
    }
};

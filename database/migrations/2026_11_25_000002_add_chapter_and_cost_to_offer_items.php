<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adăugări exclusive: capitolul opțional și costul unitar introdus direct pe deviz.
     * Nu se modifică și nu se șterg coloanele existente.
     */
    public function up(): void
    {
        Schema::table('offer_items', function (Blueprint $table) {
            $table->foreignId('chapter_id')->nullable()->index()->after('section');
            $table->decimal('unit_cost', 10, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropColumn(['chapter_id', 'unit_cost']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Regimul TVA se alege per ofertă. Valoarea implicită păstrează
     * comportamentul ofertelor existente (cu TVA, conform setării firmei).
     */
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('vat_applied')->default(true)->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('vat_applied');
        });
    }
};

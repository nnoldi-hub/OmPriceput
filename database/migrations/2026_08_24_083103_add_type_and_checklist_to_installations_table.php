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
        Schema::table('installations', function (Blueprint $table) {
            $table->enum('type', ['instalare', 'reparatie', 'mentenanta', 'verificare', 'urgenta'])->default('instalare')->after('offer_id');
            $table->string('requested_type')->nullable()->after('type');
            $table->json('checklist')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn(['type', 'requested_type', 'checklist']);
        });
    }
};

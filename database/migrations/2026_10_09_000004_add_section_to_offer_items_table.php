<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_items', function (Blueprint $table) {
            $table->string('section', 20)->default('labor')->after('service_id');
            $table->string('unit', 20)->nullable()->after('quantity');
        });

        DB::table('offer_items')
            ->whereNotNull('equipment_id')
            ->update(['section' => 'materials']);
    }

    public function down(): void
    {
        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropColumn(['section', 'unit']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_packages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->decimal('price_from', 10, 2)->default(0);
            $table->unsignedInteger('vizite_an')->default(0);
            $table->string('timp_raspuns')->nullable();
            $table->string('prioritate')->nullable();
            $table->json('features');
            $table->boolean('highlight')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        foreach (config('packages.tiers', []) as $order => $package) {
            DB::table('site_packages')->insert([
                'key' => $package['key'],
                'name' => $package['name'],
                'price_from' => $package['price_from'],
                'vizite_an' => $package['vizite_an'],
                'timp_raspuns' => $package['timp_raspuns'],
                'prioritate' => $package['prioritate'],
                'features' => json_encode($package['features']),
                'highlight' => $package['highlight'] ?? false,
                'active' => true,
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_packages');
    }
};

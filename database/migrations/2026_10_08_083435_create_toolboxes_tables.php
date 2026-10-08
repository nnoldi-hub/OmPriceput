<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolboxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('contents')->nullable();      // ce trebuie să fie în cutie
            $table->boolean('always_carry')->default(false); // Master
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_toolbox', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('toolbox_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'toolbox_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_toolbox');
        Schema::dropIfExists('toolboxes');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('agenda_item_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agenda_item_id', 'agenda_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_item_tag');
        Schema::dropIfExists('agenda_tags');
    }
};

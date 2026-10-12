<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('text')->nullable();
            $table->enum('type', ['task', 'nota', 'idee', 'obiectiv', 'masuratoare'])->default('task');
            $table->enum('status', ['inbox', 'de_facut', 'in_lucru', 'gata', 'arhivat'])->default('inbox');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->dateTime('due_at')->nullable();
            $table->dateTime('reminder_at')->nullable();
            $table->string('repeat')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('pinned_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_at']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};

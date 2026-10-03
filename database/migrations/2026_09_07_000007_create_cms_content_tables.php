<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->json('content')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['page_id', 'section_key']);
        });

        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description');
            $table->string('icon')->nullable();
            $table->boolean('is_dynamic')->default(false);
            $table->timestamps();
        });

        DB::table('pages')->insert([
            ['slug' => 'acasa', 'title' => 'Acasa', 'subtitle' => 'Pagina principala', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'despre', 'title' => 'Despre noi', 'subtitle' => 'Cine suntem', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'servicii', 'title' => 'Servicii', 'subtitle' => 'Electric, sanit, montaj, vopsire', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'contact', 'title' => 'Contact', 'subtitle' => 'Ia legatura cu noi', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stats');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
    }
};

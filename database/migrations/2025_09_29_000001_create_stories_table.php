<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->date('upload_date')->nullable();
            $table->text('content')->nullable();
            $table->enum('content_type', ['stories', 'news', 'blog'])->default('stories');
            $table->boolean('is_active')->default(true);
            $table->boolean('soft_delete')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};



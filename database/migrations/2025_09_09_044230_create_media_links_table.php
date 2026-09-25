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
        Schema::create('media_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            
            // liên kết đa hình (polymorphic manual)
            $table->unsignedBigInteger('model_id');  
            $table->string('model_type'); // ví dụ: App\Models\Product, App\Models\Post, App\Models\User
            $table->string('role')->default('gallery');
            $table->index(['model_id', 'model_type']); // tối ưu query
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_links');
    }
};

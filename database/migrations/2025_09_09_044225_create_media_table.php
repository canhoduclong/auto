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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            
            $table->string('file_name');      // tên gốc của file
            $table->string('file_path');      // đường dẫn lưu trong storage
            $table->string('mime_type')->nullable(); // image/png, image/jpeg, ...
            $table->unsignedBigInteger('file_size')->nullable(); // byte
            $table->string('type')->nullable(); // image, video, document...

            // Ai đã upload file
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

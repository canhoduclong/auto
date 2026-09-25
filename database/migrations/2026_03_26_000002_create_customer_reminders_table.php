<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('note')->nullable();
            $table->dateTime('remind_at');
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('customer_reminders');
    }
};

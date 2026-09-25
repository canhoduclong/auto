<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipper_dispatch_histories', function (Blueprint $table) {
            $table->id();
            $table->date('schedule_date')->index();
            $table->unsignedInteger('version');
            $table->longText('route_plan');
            $table->text('notes')->nullable();
            $table->unsignedInteger('shippers_count')->default(0);
            $table->unsignedInteger('trips_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('total_fee', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['schedule_date', 'version']);
            $table->index(['schedule_date', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipper_dispatch_histories');
    }
};

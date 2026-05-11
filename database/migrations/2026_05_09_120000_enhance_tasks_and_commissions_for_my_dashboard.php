<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_assignments')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE task_assignments MODIFY status ENUM('draft','pending','in_progress','processing','completed','done','rejected','cancelled') NOT NULL DEFAULT 'pending'");
            }
        }

        if (Schema::hasTable('task_assignees')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE task_assignees MODIFY status ENUM('pending','in_progress','processing','completed','rejected') NOT NULL DEFAULT 'pending'");
            }
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'commission_percent')) {
                    $table->decimal('commission_percent', 5, 2)->default(0)->after('customer_status');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'commission_percent_snapshot')) {
                    $table->decimal('commission_percent_snapshot', 5, 2)->nullable()->after('total');
                }
                if (!Schema::hasColumn('orders', 'commission_amount_snapshot')) {
                    $table->decimal('commission_amount_snapshot', 15, 2)->default(0)->after('commission_percent_snapshot');
                }
                if (!Schema::hasColumn('orders', 'commission_created_at')) {
                    $table->timestamp('commission_created_at')->nullable()->after('commission_amount_snapshot');
                }
            });
        }

        if (!Schema::hasTable('order_commissions')) {
            Schema::create('order_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->unsignedBigInteger('sale_user_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->decimal('order_total', 15, 2)->default(0);
                $table->decimal('commission_percent', 5, 2)->default(0);
                $table->decimal('commission_amount', 15, 2)->default(0);
                $table->string('status')->default('confirmed');
                $table->unsignedBigInteger('confirmed_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
                $table->foreign('sale_user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['sale_user_id', 'confirmed_at']);
                $table->index(['customer_id', 'confirmed_at']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_commissions')) {
            Schema::dropIfExists('order_commissions');
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $dropColumns = [];
                if (Schema::hasColumn('orders', 'commission_percent_snapshot')) {
                    $dropColumns[] = 'commission_percent_snapshot';
                }
                if (Schema::hasColumn('orders', 'commission_amount_snapshot')) {
                    $dropColumns[] = 'commission_amount_snapshot';
                }
                if (Schema::hasColumn('orders', 'commission_created_at')) {
                    $dropColumns[] = 'commission_created_at';
                }

                if (!empty($dropColumns)) {
                    $table->dropColumn($dropColumns);
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'commission_percent')) {
                    $table->dropColumn('commission_percent');
                }
            });
        }
    }
};

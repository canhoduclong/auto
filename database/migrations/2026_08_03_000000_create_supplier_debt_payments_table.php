<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_debt_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->dateTime('paid_at')->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        DB::table('procurement_purchases')
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($purchases): void {
                foreach ($purchases as $purchase) {
                    $transactionId = null;
                    if ($purchase->payment_transaction_id) {
                        $approved = DB::table('transactions')
                            ->where('id', $purchase->payment_transaction_id)
                            ->where('status', 'approved')
                            ->exists();
                        $transactionId = $approved ? $purchase->payment_transaction_id : null;
                    }

                    DB::table('supplier_debt_payments')->insert([
                        'procurement_purchase_id' => $purchase->id,
                        'transaction_id' => $transactionId,
                        'amount' => $purchase->paid_amount,
                        'paid_at' => $purchase->purchased_at ?? $purchase->created_at,
                        'recorded_by' => $purchase->created_by,
                        'note' => 'Số dư thanh toán được chuyển từ dữ liệu công nợ cũ',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_debt_payments');
    }
};

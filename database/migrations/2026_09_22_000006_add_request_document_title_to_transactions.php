<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'request_document_title')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->string('request_document_title', 255)->nullable()->after('request_form_type');
            });
        }

        DB::table('transactions')->whereNotNull('request_source')->whereNull('request_document_title')
            ->update(['request_document_title' => DB::raw("CASE WHEN request_form_type = 'payment_proposal' THEN 'Phiếu đề nghị thanh toán' ELSE 'Phiếu yêu cầu' END")]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'request_document_title')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropColumn('request_document_title');
            });
        }
    }
};

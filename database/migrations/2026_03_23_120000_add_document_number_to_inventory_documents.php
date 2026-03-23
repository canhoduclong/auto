<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->string('document_number')->nullable()->unique()->after('id');
        });

        // Back-fill existing rows
        DB::table('inventory_documents')->orderBy('id')->each(function ($doc) {
            $prefix = match ($doc->type) {
                'import'     => 'PNK',
                'export'     => 'PXK',
                default      => 'PDC',
            };
            $date = date('Ymd', strtotime($doc->created_at));
            DB::table('inventory_documents')
                ->where('id', $doc->id)
                ->update(['document_number' => $prefix . '-' . $date . '-' . str_pad($doc->id, 4, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->dropColumn('document_number');
        });
    }
};

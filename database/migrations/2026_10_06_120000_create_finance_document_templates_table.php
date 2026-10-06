<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('finance_document_templates', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('form_type');
            $table->string('flow_direction'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        foreach ([['Phiếu yêu cầu','cash_request','both'], ['Phiếu chi','cash_request','out'], ['Phiếu thu','cash_request','in'], ['Đề nghị tạm ứng','advance_request','out'], ['Phiếu đề nghị thanh toán','payment_proposal','out']] as [$name,$type,$flow]) {
            DB::table('finance_document_templates')->insert(['name'=>$name,'form_type'=>$type,'flow_direction'=>$flow,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void { Schema::dropIfExists('finance_document_templates'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Chuyển cột production từ float sang string để lưu giá trị như "120 con", "5 tấn/tháng", v.v.
        // Cần cast giá trị số cũ sang string trước khi đổi kiểu
        DB::statement('ALTER TABLE customers MODIFY production VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Rollback: chỉ chuyển về float nếu dữ liệu toàn là số, bỏ qua bản ghi text
        DB::statement('ALTER TABLE customers MODIFY production FLOAT NULL');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void { DB::table('roles')->where('name', 'procurement_manager')->update(['layout_mobile_name' => 'My_app / Procurement', 'layout_mobile_slug' => 'my_app_procurement', 'updated_at' => now()]); }
    public function down(): void { DB::table('roles')->where('name', 'procurement_manager')->update(['layout_mobile_name' => null, 'layout_mobile_slug' => null]); }
};

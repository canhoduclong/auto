<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->whereRaw('LOWER(name) = ?', ['package'])
            ->update([
                'layout_mobile_name' => 'My_app / Package',
                'layout_mobile_slug' => 'my_app_package',
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->whereRaw('LOWER(name) = ?', ['package'])
            ->update([
                'layout_mobile_name' => 'My_app / Home',
                'layout_mobile_slug' => 'my_app_home',
            ]);
    }
};

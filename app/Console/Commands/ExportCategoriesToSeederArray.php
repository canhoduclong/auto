<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportCategoriesToSeederArray extends Command
{
    protected $signature = 'export:categories-seeder';
    protected $description = 'Export categories table to PHP array for seeder';

    public function handle()
    {
        $categories = DB::table('categories')->get();
        $array = [];
        foreach ($categories as $c) {
            $array[] = [
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'parent_id' => $c->parent_id,
                'image' => $c->image,
            ];
        }
        $export = var_export($array, true);
        $content = "// Copy this array to your CategorySeeder.php\n\n\$categories = $export;";
        file_put_contents(base_path('categories_seeder_array.php'), $content);
        $this->info('Exported categories to categories_seeder_array.php');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportProductsToSeederArray extends Command
{
    protected $signature = 'export:products-seeder';
    protected $description = 'Export products table to PHP array for seeder';

    public function handle()
    {
        $products = DB::table('products')->get();
        $array = [];
        foreach ($products as $p) {
            $array[] = [
                'user_id' => $p->user_id,
                'category_id' => $p->category_id,
                'name' => $p->name,
                'slug' => $p->slug,
                'description' => $p->description,
                'price' => $p->price,
                'stock' => $p->stock,
                'image' => $p->image,
            ];
        }
        $export = var_export($array, true);
        $content = "// Copy this array to your ProductSeeder.php\n\n\$products = $export;";
        file_put_contents(base_path('products_seeder_array.php'), $content);
        $this->info('Exported products to products_seeder_array.php');
    }
}

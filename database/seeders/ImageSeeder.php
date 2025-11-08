<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Media;
use App\Models\MediaLink;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Consider truncating media and media_links if you run this multiple times
        // Media::truncate();
        // MediaLink::truncate();
        DB::statement('SET FOREIGN_key_checks=1;');

        /**
         * INSTRUCTIONS:
         * 1. Add the product/variant identifier (slug or SKU).
         * 2. Specify the model type ('product' or 'variant').
         * 3. Define the role ('gallery', 'thumbnail', 'variant', etc.).
         * 
         * The 'logo' is a special case for a site setting.
         */
        $imageMappings = [
            [
                'file_path' => 'media/logo.png',
                'model_type' => 'setting',
                'identifier' => 'logo', // The 'key' of the setting
                'role' => 'setting_value'
            ],
            [
                'file_path' => 'media/uc-vit-ngan-co-canh-sau.png',
                'model_type' => 'product', // 'product' or 'variant'
                'identifier' => 'your-product-slug-here', // Product slug or Variant SKU
                'role' => 'gallery' // 'gallery', 'thumbnail', or 'variant'
            ],
            [
                'file_path' => 'media/uc-ngan-co-canh.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/VIT-HOANG-LONG-DUNG.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/canh-vit.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/chan-vit.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/dau-co.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/dui-vit-1-4.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/luoi-vit.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/phao-cau-vit.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/uc-vit-phi-le.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/vit-bong-khong-dau-chan.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
            [
                'file_path' => 'media/vit-bong-khong-dau-chan-co.png',
                'model_type' => 'product',
                'identifier' => 'your-product-slug-here',
                'role' => 'gallery'
            ],
        ];

        foreach ($imageMappings as $mapping) {
            $this->command->info("Processing {$mapping['file_path']}...");

            // Create the Media record
            $media = Media::firstOrCreate(
                ['file_path' => $mapping['file_path']],
                [
                    'file_name' => basename($mapping['file_path']),
                    'mime_type' => 'image/png', // Corrected column name
                    'file_size' => 0, // You might want to get the actual size
                    'type' => 'image',
                    'uploaded_by' => 1, // Corrected column name
                ]
            );

            $model = null;
            if ($mapping['model_type'] === 'product') {
                $model = Product::where('slug', $mapping['identifier'])->first();
            } elseif ($mapping['model_type'] === 'variant') {
                $model = ProductVariant::where('sku', $mapping['identifier'])->first();
            } elseif ($mapping['model_type'] === 'setting') {
                // Special case for settings like the logo
                Setting::updateOrCreate(
                    ['key' => $mapping['identifier']],
                    ['value' => $media->id]
                );
                $this->command->info("Updated setting '{$mapping['identifier']}'.");
                continue; // Skip MediaLink creation for settings
            }

            if ($model) {
                // Create the link
                MediaLink::create([
                    'media_id' => $media->id,
                    'model_id' => $model->id,
                    'model_type' => get_class($model),
                    'role' => $mapping['role'],
                ]);
                $this->command->info("Linked to {$mapping['model_type']} #{$model->id}");
            } else {
                $this->command->error("Could not find {$mapping['model_type']} with identifier '{$mapping['identifier']}'");
            }
        }
    }
}
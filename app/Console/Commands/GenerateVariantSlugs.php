<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVariantSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'variants:generate-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for existing product variants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $variants = \App\Models\ProductVariant::all();
        $bar = $this->output->createProgressBar($variants->count());
        $bar->start();

        foreach ($variants as $variant) {
            if (empty($variant->slug)) {
                $productName = $variant->product->name;
                $attributes = $variant->values->pluck('value')->implode('-');
                $variant->slug = \Illuminate\Support\Str::slug($productName . '-' . $attributes . '-' . $variant->id . '-' . time());
                $variant->save();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info('\nSlugs for product variants have been generated.');
    }
}

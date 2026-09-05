<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\ProductCuttingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingMaterialOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cutting_imports_main_and_remaining_weights_using_shared_percentages(): void
    {
        $warehouse = Warehouse::create(['name' => 'Kho pha lóc tỷ lệ', 'status' => true]);
        $user = \App\Models\User::factory()->create();
        $main = Product::factory()->create(['product_type' => Product::TYPE_CUT]);
        $side = Product::factory()->create(['product_type' => Product::TYPE_CUT, 'cutting_percentage' => 15]);
        $whole = Product::factory()->create(['product_type' => Product::TYPE_WHOLE, 'cutting_product_targets' => [$main->id => [$side->id]]]);
        $source = ProductVariant::factory()->create(['product_id' => $whole->id, 'kg' => 2.5, 'status' => true]);
        $target = ProductVariant::factory()->create(['product_id' => $main->id, 'status' => true]);
        $part = ProductVariant::factory()->create(['product_id' => $side->id, 'status' => true]);
        $inventory = Inventory::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $source->id, 'quantity' => 20]);
        $service = app(ProductCuttingService::class);
        $plan = $service->planForDemand($target, $warehouse->id, 10);
        $this->assertSame(5, $plan['selected_materials'][$source->id]['quantity']);
        $batch = $service->start($warehouse->id, $target, array_values($plan['selected_materials']), '', $user->id);
        $this->assertSame(10.625, (float) $batch->planned_finished_weight);
        $this->assertSame(15.0, (float) $inventory->fresh()->quantity);
        $service->complete($batch, $batch->planned_finished_weight, $batch->planned_components, $user->id);
        $this->assertSame(10.625, (float) Inventory::where('warehouse_id', $warehouse->id)->where('product_variant_id', $target->id)->value('quantity'));
        $this->assertSame(1.875, (float) Inventory::where('warehouse_id', $warehouse->id)->where('product_variant_id', $part->id)->value('quantity'));
        $unconfigured = ProductVariant::factory()->create(['product_id' => Product::factory()->create(['product_type' => Product::TYPE_WHOLE])->id, 'kg' => 1]);
        $this->assertSame(0.0, $service->preview($target, [['variant_id' => $unconfigured->id, 'quantity' => 1]])['finished_weight']);
    }

    public function test_percentages_scale_with_source_size_and_reject_totals_over_100(): void
    {
        $whole = Product::factory()->create(['product_type' => Product::TYPE_WHOLE]);
        $mainProduct = Product::factory()->create(['product_type' => Product::TYPE_CUT]);
        $sideProduct = Product::factory()->create(['product_type' => Product::TYPE_CUT, 'cutting_percentage' => 15]);
        $target = ProductVariant::factory()->create(['product_id' => $mainProduct->id, 'status' => true]);
        $side = ProductVariant::factory()->create(['product_id' => $sideProduct->id, 'status' => true]);
        $source = ProductVariant::factory()->create(['product_id' => $whole->id, 'kg' => 2.5]);
        $controller = (new \ReflectionClass(\App\Http\Controllers\ProductController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'syncCuttingComponentTemplate');
        $payload = [
            'cutting_product_targets_present' => true,
            'cutting_product_targets' => [$mainProduct->id => ['enabled' => true, 'remaining' => [$sideProduct->id]]],
            'cutting_percentages' => [$mainProduct->id => [$mainProduct->id => 80, $sideProduct->id => 15]],
        ];
        $method->invoke($controller, $payload, $whole);
        $service = app(ProductCuttingService::class);
        $preview = $service->preview($target, [['variant_id' => $source->id, 'quantity' => 1]]);
        $this->assertSame(2.125, $preview['finished_weight']);
        $this->assertSame(0.375, $preview['components']->first()['weight']);
        $source->update(['kg' => 3]);
        $preview = $service->preview($target, [['variant_id' => $source->id, 'quantity' => 2]]);
        $this->assertSame(5.1, $preview['finished_weight']);
        $this->assertSame(0.9, $preview['components']->first()['weight']);
        $sideProduct->update(['cutting_percentage' => 20]);
        $preview = $service->preview($target, [['variant_id' => $source->id, 'quantity' => 1]]);
        $this->assertSame(2.4, $preview['finished_weight']);
        $this->assertSame(0.6, $preview['components']->first()['weight']);
        $sideProduct->update(['cutting_percentage' => null]);
        $preview = $service->preview($target, [['variant_id' => $source->id, 'quantity' => 1]]);
        $this->assertSame(0.0, $preview['finished_weight']);
    }

    public function test_product_targets_apply_to_each_target_variant_and_picker_groups_sizes(): void
    {
        $whole = Product::factory()->create(['product_type' => Product::TYPE_WHOLE]);
        $main = Product::factory()->create(['product_type' => Product::TYPE_CUT]);
        $side = Product::factory()->create(['product_type' => Product::TYPE_CUT, 'cutting_percentage' => 16.667]);
        $source = ProductVariant::factory()->create(['product_id' => $whole->id, 'kg' => 3]);
        $first = ProductVariant::factory()->create(['product_id' => $main->id]);
        $second = ProductVariant::factory()->create(['product_id' => $main->id]);
        $remaining = ProductVariant::factory()->create(['product_id' => $side->id]);
        $controller = (new \ReflectionClass(\App\Http\Controllers\ProductController::class))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($controller, 'syncCuttingComponentTemplate'))->invoke($controller, [
            'cutting_product_targets_present' => true,
            'cutting_product_targets' => [$main->id => ['enabled' => true, 'remaining' => [$side->id]]],
        ], $whole);
        $this->assertSame([$side->id], $whole->fresh()->cutting_product_targets[$main->id]);
        foreach ([$first, $second, $remaining] as $variant) {
            \App\Models\ProductComponentRatio::create(['source_product_variant_id' => $source->id, 'component_product_variant_id' => $variant->id, 'standard_weight' => $variant->id === $remaining->id ? 0.5 : 2, 'percentage' => 0]);
        }
        foreach ([$first, $second] as $target) {
            $preview = app(ProductCuttingService::class)->preview($target, [['variant_id' => $source->id, 'quantity' => 1]]);
            $this->assertSame(2.5, $preview['finished_weight']);
            $this->assertSame([$remaining->id], $preview['components']->pluck('variant_id')->all());
        }
        $scripts = \Illuminate\Support\Facades\Blade::render("@include('products._cutting_target_builder') @stack('scripts')", ['product' => $whole->fresh(), 'cutComponentVariants' => collect([$first, $second, $remaining])]);
        preg_match('/const choices = (.*);/', $scripts, $matches);
        $choices = json_decode($matches[1], true);
        $this->assertCount(2, $choices);
        $this->assertSame('2 biến thể', $choices[0]['variant']);
        $this->assertStringContainsString('cutting_product_targets[', $scripts);
    }

    public function test_target_configuration_is_saved_and_only_selected_remaining_components_are_used(): void
    {
        $whole = Product::factory()->create(['product_type' => Product::TYPE_WHOLE]);
        $cut = Product::factory()->create(['product_type' => Product::TYPE_CUT]);
        $source = ProductVariant::factory()->create(['product_id' => $whole->id, 'kg' => 3]);
        $targets = collect(range(1, 4))->map(fn () => ProductVariant::factory()->create(['product_id' => $cut->id]));
        $controller = (new \ReflectionClass(\App\Http\Controllers\ProductController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'syncCuttingComponentTemplate');
        $method->invoke($controller, [
            'cutting_targets_present' => true,
            'cutting_targets' => [
                $targets[0]->id => ['enabled' => true, 'remaining' => [$targets[1]->id]],
                $targets[2]->id => ['enabled' => true, 'remaining' => [$targets[3]->id]],
            ],
        ], $whole);
        $this->assertSame([$targets[1]->id], $whole->fresh()->cutting_targets[$targets[0]->id]);
        foreach ([2, 0.3, 1.5, 0.5] as $index => $weight) {
            \App\Models\ProductComponentRatio::query()->create([
                'source_product_variant_id' => $source->id,
                'component_product_variant_id' => $targets[$index]->id,
                'standard_weight' => $weight, 'percentage' => 0,
            ]);
        }
        $service = app(ProductCuttingService::class);
        $first = $service->preview($targets[0], [['variant_id' => $source->id, 'quantity' => 2]]);
        $this->assertSame(4.0, $first['finished_weight']);
        $this->assertSame([$targets[1]->id], $first['components']->pluck('variant_id')->all());
        $second = $service->preview($targets[2], [['variant_id' => $source->id, 'quantity' => 2]]);
        $this->assertSame(3.0, $second['finished_weight']);
        $this->assertSame([$targets[3]->id], $second['components']->pluck('variant_id')->all());
    }

    public function test_all_whole_variants_in_warehouse_are_available_without_component_configuration(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho pha lóc', 'status' => true]);
        $otherWarehouse = Warehouse::query()->create(['name' => 'Kho khác', 'status' => true]);
        $whole = Product::factory()->create(['product_type' => Product::TYPE_WHOLE]);
        $cut = Product::factory()->create(['product_type' => Product::TYPE_CUT]);
        $target = ProductVariant::factory()->create(['product_id' => $cut->id, 'status' => true]);
        $variants = collect();
        foreach (range(1, 3) as $index) {
            $variant = ProductVariant::factory()->create(['product_id' => $whole->id, 'status' => true, 'kg' => 2]);
            Inventory::query()->create([
                'warehouse_id' => $index === 3 ? $otherWarehouse->id : $warehouse->id,
                'product_variant_id' => $variant->id,
                'quantity' => $index === 2 ? 0 : 10,
                'reserved_quantity' => $index === 1 ? 3 : 0,
            ]);
            $variants->push($variant);
        }
        Inventory::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $target->id, 'quantity' => 10]);
        $options = app(ProductCuttingService::class)->sourceMaterialOptions($target, $warehouse->id)->keyBy('variant_id');
        $this->assertCount(2, $options);
        $this->assertSame(7.0, $options[$variants[0]->id]['available']);
        $this->assertSame(0.0, $options[$variants[1]->id]['available']);
        $this->assertFalse($options->has($variants[2]->id));
        $this->assertFalse($options->has($target->id));
        $html = view('warehouse.cutting._order_modal', [
            'cuttingOrder' => new \App\Models\Order(['id' => 1, 'code' => 'CUT-PREVIEW']),
            'cuttingPlan' => ['target_variant_id' => $target->id, 'materials' => [], 'material_options' => $options->values()],
        ])->render();
        $this->assertStringContainsString('name="materials[0][variant_id]" value="'.$variants[0]->id.'"', $html);
        $this->assertStringContainsString('name="materials[1][variant_id]" value="'.$variants[1]->id.'"', $html);
    }
}

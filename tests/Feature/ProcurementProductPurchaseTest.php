<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ProcurementPurchase;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementProductPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_purchase_calculates_lines_and_warehouse_receipt_creates_inventory(): void
    {
        [$procurement, $warehouseUser, $supplier, $warehouse, $variants] = $this->fixtures();
        $response = $this->actingAs($procurement)->post(route('procurement.purchases.store'), [
            'purchase_type'=>'product_purchase', 'supplier_id'=>$supplier->id, 'purchased_at'=>now()->format('Y-m-d H:i:s'),
            'broker_fee'=>1000, 'processing_fee'=>0, 'procurement_fee'=>2000, 'transportation_fee'=>3000, 'other_fee'=>0, 'paid_amount'=>0,
            'product_items'=>[
                ['product_variant_id'=>$variants[0]->id, 'quantity'=>5, 'weight'=>10, 'unit_cost'=>20000],
                ['product_variant_id'=>$variants[1]->id, 'quantity'=>3.5, 'weight'=>3.5, 'unit_cost'=>30000],
            ],
        ]);
        $response->assertSessionHas('success');
        $purchase=ProcurementPurchase::latest('id')->first();
        $this->assertSame('product_lines',$purchase->entry_mode);
        $this->assertSame(205000.0,(float)$purchase->subtotal);
        $this->assertSame(211000.0,(float)$purchase->total_amount);
        $this->assertCount(2,$purchase->productItems);

        $purchase->update(['warehouse_id'=>$warehouse->id,'status'=>ProcurementPurchase::STATUS_SENT]);
        $items=$purchase->productItems()->orderBy('id')->get();
        $this->actingAs($warehouseUser)->post(route('warehouse.procurement-receipts.receive',$purchase),[
            'warehouse_rating'=>5,'warehouse_condition'=>'Đạt','warehouse_comment'=>'Đủ hàng',
            'product_items'=>[
                ['id'=>$items[0]->id,'received_quantity'=>4,'received_weight'=>8,'condition'=>'Tươi'],
                ['id'=>$items[1]->id,'received_quantity'=>3.5,'received_weight'=>3.5,'condition'=>'Tốt'],
            ],
        ])->assertSessionHas('success');
        $purchase->refresh();
        $this->assertNotNull($purchase->inventory_document_id);
        $this->assertDatabaseHas('inventory_document_items',['inventory_document_id'=>$purchase->inventory_document_id,'product_variant_id'=>$variants[0]->id,'quantity'=>4]);
        $this->assertDatabaseHas('inventories',['warehouse_id'=>$warehouse->id,'product_variant_id'=>$variants[1]->id,'quantity'=>3.5]);
    }

    public function test_procurement_can_save_product_purchase_template(): void
    {
        [$procurement,,$supplier,,$variants]=$this->fixtures();
        $this->actingAs($procurement)->postJson(route('procurement.purchase-templates.store'),[
            'name'=>'Mẫu đùi và lòng','supplier_id'=>$supplier->id,'items'=>[
                ['product_variant_id'=>$variants[0]->id,'quantity'=>10,'weight'=>20],
                ['product_variant_id'=>$variants[1]->id,'quantity'=>5,'weight'=>5],
            ],
        ])->assertOk()->assertJsonPath('template.name','Mẫu đùi và lòng')->assertJsonCount(2,'template.items');
        $this->assertDatabaseHas('procurement_purchase_templates',['name'=>'Mẫu đùi và lòng','supplier_id'=>$supplier->id]);
    }

    private function fixtures(): array
    {
        $warehouse=Warehouse::create(['name'=>'Kho test']);
        $procurement=User::factory()->create();$procurement->roles()->attach(Role::firstOrCreate(['name'=>'procurement_manager']));
        $warehouseUser=User::factory()->create(['warehouse_id'=>$warehouse->id]);$warehouseUser->roles()->attach(Role::firstOrCreate(['name'=>'warehouse']));
        $supplier=Supplier::create(['name'=>'Nhà cung cấp sản phẩm','is_active'=>true]);
        $category=Category::create(['name'=>'Sản phẩm vịt','description'=>'Test']);
        $products=collect([['Đùi vịt','cai',2],['Lòng vịt','kg',1]])->map(function($row)use($procurement,$category,$supplier){
            $product=Product::create(['name'=>$row[0],'unit'=>$row[1],'kg'=>$row[2],'is_priced_by_kg'=>$row[1]==='kg','user_id'=>$procurement->id,'category_id'=>$category->id,'status'=>true]);
            SupplierProduct::create(['supplier_id'=>$supplier->id,'product_id'=>$product->id,'active'=>true]);
            return ProductVariant::create(['product_id'=>$product->id,'name'=>'Mặc định','sku'=>'TEST-'.$product->id,'kg'=>$row[2],'status'=>true]);
        });
        return [$procurement,$warehouseUser,$supplier,$warehouse,$products->values()->all()];
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use App\Models\{Order,User};
use App\Http\Controllers\Api\Mobile\ShipperApiController;
use Illuminate\Http\Request;
class UndoCompleteDeliveryTest extends \PHPUnit\Framework\TestCase {
 private $app;
 protected function setUp():void {
  $this->app=require dirname(__DIR__, 2).'/bootstrap/app.php';
  $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  config(['cache.default'=>'array']);
  foreach(['orders'=>'shipper_id,status,delivered_at,accounting_sales_import_batch_id,collected_amount','accounting_reconciliations'=>'order_id,status','order_returns'=>'order_id','order_histories'=>'order_id,action,user_id,role,status_before,status_after,note,source','inventories'=>'quantity,reserved_quantity'] as $name=>$columns) Schema::create($name,function(Blueprint $t)use($columns){$t->temporary();$t->id();foreach(explode(',',$columns) as $c)$t->string($c)->nullable();$t->timestamps();});
  DB::table('orders')->insert(['id'=>1,'shipper_id'=>7,'status'=>'completed','delivered_at'=>now(),'collected_amount'=>100]);
  DB::table('inventories')->insert(['quantity'=>10,'reserved_quantity'=>0]);
 }
 protected function tearDown():void {foreach(['orders','accounting_reconciliations','order_returns','order_histories','inventories'] as $t) DB::statement('DROP TEMPORARY TABLE IF EXISTS `'.$t.'`');$this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown();}
 private function runUndo(int $id=7) {
  $user=new class extends User {public function hasRole($roles, ?string $guard=null):bool{return true;}}; $user->id=$id;
  $r=Request::create('/','POST',['reason'=>'Bấm nhầm']);$r->setUserResolver(fn()=>$user);
  return (new ShipperApiController)->undoCompleteDelivery($r,Order::findOrFail(1));
 }
 public function test_reopens_without_restocking_or_erasing_money():void {
  DB::table('accounting_reconciliations')->insert(['order_id'=>1,'status'=>'pending']);
  self::assertSame(200,$this->runUndo()->status());
  self::assertSame('delivering',Order::find(1)->status);self::assertNull(Order::find(1)->delivered_at);
  self::assertEquals(100,Order::find(1)->collected_amount);self::assertEquals(10,DB::table('inventories')->value('quantity'));
  self::assertSame(0,DB::table('accounting_reconciliations')->count());self::assertSame(1,DB::table('order_histories')->count());
 }
 public function test_other_shipper_is_denied():void {$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);$this->runUndo(8);}
 public function test_confirmed_reconciliation_stays_intact():void {
  DB::table('accounting_reconciliations')->insert(['order_id'=>1,'status'=>'confirmed']);
  try{$this->runUndo();self::fail();}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){self::assertSame(422,$e->getStatusCode());}
  self::assertSame('completed',Order::find(1)->status);self::assertSame(1,DB::table('accounting_reconciliations')->count());
 }
 public function test_existing_return_blocks_duplicate_processing():void {DB::table('order_returns')->insert(['order_id'=>1]);$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);$this->runUndo();}
 public function test_repeat_is_rejected():void {$this->runUndo();$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);$this->runUndo();}
}

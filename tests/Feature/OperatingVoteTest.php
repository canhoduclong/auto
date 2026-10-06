<?php
namespace Tests\Feature;
use App\Models\{OperatingProposal,User};
use App\Services\OperatingVoteService;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
class OperatingVoteTest extends TestCase {
 private $app;
 protected function setUp(): void {
  $this->app=require dirname(__DIR__,2).'/bootstrap/app.php';$this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');
  Schema::create('operating_proposals',function(Blueprint $t){$t->id();$t->integer('created_by');$t->string('title');$t->text('description');$t->string('status')->default('open');$t->integer('quorum');$t->integer('approval_percent');$t->timestamp('closes_at');$t->timestamp('closed_at')->nullable();$t->integer('closed_by')->nullable();$t->text('conclusion')->nullable();$t->timestamps();});
  Schema::create('operating_votes',function(Blueprint $t){$t->id();$t->integer('proposal_id');$t->integer('user_id');$t->string('choice')->nullable();$t->text('comment')->nullable();$t->timestamp('voted_at')->nullable();$t->timestamps();$t->unique(['proposal_id','user_id']);});
 }
 protected function tearDown():void{DB::disconnect('sqlite');$this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown();}
 private function actor(int $id):User{$u=new User;$u->id=$id;$u->setRelation('roles',new \Illuminate\Database\Eloquent\Collection);return $u;}
 private function proposal(int $quorum=2,int $percent=51):OperatingProposal{$p=OperatingProposal::create(['created_by'=>1,'title'=>'Đề xuất','description'=>'Nội dung','quorum'=>$quorum,'approval_percent'=>$percent,'closes_at'=>now()->addDay()]);foreach([2,3] as $id)$p->votes()->create(['user_id'=>$id]);return $p;}
 private function reject(callable $action,int $code):void{try{$action();self::fail('Expected rejection');}catch(HttpException $e){self::assertSame($code,$e->getStatusCode());}}
 public function test_unanimous_votes_can_close_early_and_keep_audit():void{$p=$this->proposal();$s=new OperatingVoteService;foreach([2,3] as $id)$s->vote($p->id,$this->actor($id),'agree','Đồng ý');self::assertSame('approved',$s->close($p->id,$this->actor(1),'Triển khai'));self::assertNotNull($p->fresh()->closed_at);self::assertSame(1,$p->fresh()->closed_by);self::assertSame(2,$p->votes()->whereNotNull('voted_at')->count());}
 public function test_abstention_counts_for_quorum_but_not_approval():void{$p=$this->proposal();$s=new OperatingVoteService;$s->vote($p->id,$this->actor(2),'agree',null);$s->vote($p->id,$this->actor(3),'abstain',null);self::assertSame('rejected',$s->close($p->id,$this->actor(1),'Không đủ tỷ lệ'));}
 public function test_missing_votes_do_not_inflate_approval():void{$p=$this->proposal(1);$s=new OperatingVoteService;$s->vote($p->id,$this->actor(2),'agree',null);$p->update(['closes_at'=>now()->subMinute()]);self::assertSame('rejected',$s->close($p->id,$this->actor(1),'Một phiếu trên hai'));}
 public function test_no_quorum_is_recorded_separately():void{$p=$this->proposal();$p->update(['closes_at'=>now()->subMinute()]);self::assertSame('no_quorum',(new OperatingVoteService)->close($p->id,$this->actor(1),'Không đủ phiếu'));}
 public function test_voter_cannot_vote_twice_or_change_cast_vote():void{$p=$this->proposal();$s=new OperatingVoteService;$s->vote($p->id,$this->actor(2),'agree',null);$this->reject(fn()=>$s->vote($p->id,$this->actor(2),'disagree',null),422);self::assertSame('agree',$p->votes()->where('user_id',2)->value('choice'));}
 public function test_uninvited_user_cannot_vote_and_member_cannot_close():void{$p=$this->proposal();$s=new OperatingVoteService;$this->reject(fn()=>$s->vote($p->id,$this->actor(4),'agree',null),403);$this->reject(fn()=>$s->close($p->id,$this->actor(2),'Đóng'),403);}
 public function test_early_close_and_late_votes_are_rejected():void{$p=$this->proposal();$s=new OperatingVoteService;$this->reject(fn()=>$s->close($p->id,$this->actor(1),'Đóng sớm'),422);$p->update(['closes_at'=>now()->subMinute()]);$this->reject(fn()=>$s->vote($p->id,$this->actor(2),'agree',null),422);}
 public function test_closed_result_cannot_be_closed_again():void{$p=$this->proposal();$s=new OperatingVoteService;$p->update(['closes_at'=>now()->subMinute()]);$s->close($p->id,$this->actor(1),'Chốt');$this->reject(fn()=>$s->close($p->id,$this->actor(1),'Chốt lại'),422);self::assertSame('Chốt',$p->fresh()->conclusion);}
}

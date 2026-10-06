<?php
namespace App\Services;
use App\Models\{OperatingProposal, User};
use Illuminate\Support\Facades\DB;
class OperatingVoteService {
    public function vote(int $id, User $actor, string $choice, ?string $comment): void {
        DB::transaction(function()use($id,$actor,$choice,$comment){
            $proposal=OperatingProposal::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($proposal->status==='open' && $proposal->closes_at->isFuture(),422,'Biểu quyết đã đóng hoặc hết hạn.');
            abort_unless(in_array($choice,['agree','disagree','abstain'],true),422);
            $vote=$proposal->votes()->where('user_id',$actor->id)->lockForUpdate()->first();
            abort_unless($vote,403); abort_if($vote->voted_at!==null,422,'Bạn đã bỏ phiếu. Phiếu đã gửi được giữ nguyên.');
            $vote->update(['choice'=>$choice,'comment'=>$comment,'voted_at'=>now()]);
        });
    }
    public function close(int $id, User $actor, string $conclusion): string {
        return DB::transaction(function()use($id,$actor,$conclusion){
            $proposal=OperatingProposal::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless((int)$proposal->created_by===(int)$actor->id || $actor->hasRole('admin'),403);
            abort_unless($proposal->status==='open',422,'Biểu quyết đã được chốt.');
            $votes=$proposal->votes()->get();$cast=$votes->whereNotNull('voted_at');
            abort_if($proposal->closes_at->isFuture() && $cast->count()<$votes->count(),422,'Chỉ chốt khi hết hạn hoặc tất cả thành viên đã bỏ phiếu.');
            $agree=$cast->where('choice','agree')->count();
            // All invited members form the denominator. Abstentions/non-voters do not increase approval.
            $status=$cast->count()<$proposal->quorum ? 'no_quorum'
                : ($agree*100 >= $votes->count()*$proposal->approval_percent ? 'approved' : 'rejected');
            $proposal->update(['status'=>$status,'closed_at'=>now(),'closed_by'=>$actor->id,'conclusion'=>$conclusion]);
            return $status;
        });
    }
}

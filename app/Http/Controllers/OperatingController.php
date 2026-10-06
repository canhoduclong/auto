<?php
namespace App\Http\Controllers;
use App\Models\{OperatingProposal,TaskAssignment,User};
use App\Services\{OperatingVoteService,TaskMenuService};
use App\Support\TaskWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OperatingController extends Controller {
    public function index(Request $request){
        $user=$request->user(); $filter=$request->input('filter','mine');
        $tasks=TaskAssignment::with(['creator','assignees.user'])->where(function($q)use($user){
            $q->where('created_by',$user->id)->orWhereHas('assignees',fn($a)=>$a->where('user_id',$user->id));
            if($user->hasRole('admin'))$q->orWhereRaw('1=1');
        })->when($filter==='received',fn($q)=>$q->whereHas('assignees',fn($a)=>$a->where('user_id',$user->id)))
          ->when($filter==='assigned',fn($q)=>$q->where('created_by',$user->id))
          ->when(in_array($filter,['execution','coordination'],true),fn($q)=>$filter==='execution' ? $q->where(fn($k)=>$k->where('work_kind','execution')->orWhereNull('work_kind')) : $q->where('work_kind','coordination'))
          ->when($filter==='overdue',fn($q)=>$q->where('due_date','<',now())->whereNotIn('status',['done','cancelled']))
          ->when($filter==='verification',fn($q)=>$q->where('created_by',$user->id)->where('status','completed'))
          ->orderByDesc('id')->paginate(20)->withQueryString();
        $proposals=OperatingProposal::with('creator')->withCount('votes')->where(function($q)use($user){
            $q->where('created_by',$user->id)->orWhereHas('votes',fn($v)=>$v->where('user_id',$user->id));
            if($user->hasRole('admin'))$q->orWhereRaw('1=1');
        })->orderByDesc('id')->paginate(10,['*'],'proposal_page')->withQueryString();
        $layout=TaskWorkspace::layout($user);
        $canCreate=$this->canCreate($user);
        return view('operating.index',compact('tasks','proposals','layout','filter','canCreate'));
    }
    private function canCreate(User $user): bool {
        return TaskMenuService::canAssignTasks($user) || \App\Models\TaskDelegateConfig::canAssignTasks($user)
            || $user->roles->contains(fn($r)=>in_array(strtolower($r->name),['ceo','director'],true));
    }
    private function canView(OperatingProposal $proposal,User $user): bool {
        return $user->hasRole('admin') || (int)$proposal->created_by===(int)$user->id || $proposal->votes()->where('user_id',$user->id)->exists();
    }
    public function create(Request $request){
        abort_unless($this->canCreate($request->user()),403);
        $layout=TaskWorkspace::layout($request->user());
        $users=User::orderBy('name')->get(['id','name']);
        return view('operating.create',compact('layout','users'));
    }
    public function store(Request $request){
        abort_unless($this->canCreate($request->user()),403);
        $data=$request->validate(['title'=>'required|string|max:255','description'=>'required|string|max:20000',
            'voter_ids'=>'required|array|min:1|max:100','voter_ids.*'=>'required|integer|distinct|exists:users,id',
            'quorum'=>'required|integer|min:1','approval_percent'=>'required|integer|min:1|max:100','closes_at'=>'required|date|after:now']);
        if($data['quorum']>count($data['voter_ids']))throw \Illuminate\Validation\ValidationException::withMessages(['quorum'=>'Số phiếu tối thiểu không được lớn hơn số người tham gia.']);
        $proposal=DB::transaction(function()use($request,$data){
            $proposal=OperatingProposal::create(['created_by'=>$request->user()->id,'title'=>$data['title'],
                'description'=>\App\Support\TaskDescription::sanitize($data['description']),'quorum'=>$data['quorum'],
                'approval_percent'=>$data['approval_percent'],'closes_at'=>$data['closes_at']]);
            foreach($data['voter_ids'] as $id)$proposal->votes()->create(['user_id'=>$id]);
            return $proposal;
        });
        \Illuminate\Support\Facades\Notification::send(User::whereIn('id',$data['voter_ids'])->get(),new \App\Notifications\OperatingNotification('Mời biểu quyết',$proposal->title,route('operating.proposals.show',$proposal)));
        return redirect()->route('operating.proposals.show',$proposal)->with('success','Đã mở biểu quyết và gửi vào danh sách của các thành viên.');
    }
    public function show(Request $request,OperatingProposal $proposal){
        abort_unless($this->canView($proposal,$request->user()),403);
        $proposal->load(['creator','votes.user','tasks']);$layout=TaskWorkspace::layout($request->user());
        return view('operating.show',compact('proposal','layout'));
    }
    public function vote(Request $request,OperatingProposal $proposal,OperatingVoteService $service){
        $data=$request->validate(['choice'=>'required|in:agree,disagree,abstain','comment'=>'nullable|string|max:2000']);
        try{$service->vote($proposal->id,$request->user(),$data['choice'],$data['comment']??null);}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){if($e->getStatusCode()!==422)throw $e;return back()->with('error',$e->getMessage());}
        return back()->with('success','Đã ghi nhận phiếu biểu quyết.');
    }
    public function close(Request $request,OperatingProposal $proposal,OperatingVoteService $service){
        $data=$request->validate(['conclusion'=>'required|string|max:5000']);
        try{$service->close($proposal->id,$request->user(),$data['conclusion']);}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){if($e->getStatusCode()!==422)throw $e;return back()->with('error',$e->getMessage());}
        \Illuminate\Support\Facades\Notification::send(User::whereIn('id',$proposal->votes()->pluck('user_id'))->get(),new \App\Notifications\OperatingNotification('Đã chốt biểu quyết',$proposal->title,route('operating.proposals.show',$proposal)));
        return back()->with('success','Đã chốt kết quả biểu quyết.');
    }
}

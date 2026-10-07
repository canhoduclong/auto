<?php
namespace App\Http\Controllers;
use App\Models\{OperatingProposal,TaskAssignment,User,Setting};
use App\Services\{OperatingVoteService,TaskMenuService};
use App\Support\TaskWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
class OperatingController extends Controller {
    public function index(Request $request){
        $request->validate([
            'filter'=>'nullable|in:mine,received,assigned,unaccepted,working,reported,verification,overdue,history,all,deleted,execution,coordination,votes',
            'kinds'=>'nullable|array', 'kinds.*'=>'required|distinct|in:execution,coordination,vote',
            'q'=>'nullable|string|max:200',
            'status'=>'nullable|in:task_pending,task_processing,task_completed,task_done,task_rejected,task_cancelled,task_draft,vote_open,vote_expired,vote_approved,vote_rejected,vote_no_quorum',
            'from'=>'nullable|date_format:Y-m-d', 'to'=>'nullable|date_format:Y-m-d'.($request->filled('from')?'|after_or_equal:from':''),
            'sort'=>'nullable|in:newest,oldest,due', 'per_page'=>'nullable|in:10,20,50,100',
            'task_q'=>'nullable|string|max:200', 'proposal_q'=>'nullable|string|max:200',
            'task_status'=>'nullable|in:pending,processing,completed,done,rejected,cancelled,draft',
            'task_kind'=>'nullable|in:execution,coordination',
            'proposal_status'=>'nullable|in:open,expired,approved,rejected,no_quorum',
            'proposal_vote'=>'nullable|in:pending,voted',
            'task_from'=>'nullable|date_format:Y-m-d', 'task_to'=>'nullable|date_format:Y-m-d'.($request->filled('task_from')?'|after_or_equal:task_from':''),
            'proposal_from'=>'nullable|date_format:Y-m-d', 'proposal_to'=>'nullable|date_format:Y-m-d'.($request->filled('proposal_from')?'|after_or_equal:proposal_from':''),
            'task_sort'=>'nullable|in:newest,oldest,due', 'proposal_sort'=>'nullable|in:newest,oldest,due',
            'task_per_page'=>'nullable|in:10,20,50,100', 'proposal_per_page'=>'nullable|in:10,20,50,100',
        ]);
        $user=$request->user(); $filter=$request->input('filter','mine');
        $selectedKinds=$request->has('kinds_present') || $request->has('kinds') ? (array)$request->input('kinds',[]) : match($filter){
            'execution'=>['execution'], 'coordination'=>['coordination'], 'votes'=>['vote'], default=>['execution','coordination','vote'],
        };
        $sharedStatus=$request->input('status','');
        $request->merge([
            'task_q'=>$request->input('q',$request->input('task_q')),
            'proposal_q'=>$request->input('q',$request->input('proposal_q')),
            'task_from'=>$request->input('from',$request->input('task_from')),
            'task_to'=>$request->input('to',$request->input('task_to')),
            'proposal_from'=>$request->input('from',$request->input('proposal_from')),
            'proposal_to'=>$request->input('to',$request->input('proposal_to')),
            'task_status'=>str_starts_with($sharedStatus,'task_')?substr($sharedStatus,5):$request->input('task_status'),
            'proposal_status'=>str_starts_with($sharedStatus,'vote_')?substr($sharedStatus,5):$request->input('proposal_status'),
        ]);

        abort_if(in_array($filter,['all','deleted'],true) && !$user->hasRole('admin'),403);
        $tasks=TaskAssignment::with(['creator','assignees.user'])->when($filter==='deleted',fn($q)=>$q->onlyTrashed())->where(function($q)use($user,$filter){
            $q->where('created_by',$user->id)->orWhereHas('assignees',fn($a)=>$a->where('user_id',$user->id));
            if($user->hasRole('admin') && in_array($filter,['all','deleted'],true))$q->orWhereRaw('1=1');
        })->when($filter==='received',fn($q)=>$q->whereHas('assignees',fn($a)=>$a->where('user_id',$user->id)))
          ->when($filter==='assigned',fn($q)=>$q->where('created_by',$user->id))
          ->when(in_array($filter,['execution','coordination'],true),fn($q)=>$filter==='execution' ? $q->where(fn($k)=>$k->where('work_kind','execution')->orWhereNull('work_kind')) : $q->where('work_kind','coordination'))
          ->when($filter==='overdue',fn($q)=>$q->where('due_date','<',now())->whereNotIn('status',['done','cancelled']))
          ->when($filter==='verification',fn($q)=>$q->when(!$user->hasRole('admin'),fn($q)=>$q->where('created_by',$user->id))->where('status','completed'))
          ->when($filter==='unaccepted',fn($q)=>$q->whereNotIn('status',['done','cancelled','completed'])->whereHas('assignees',fn($a)=>$a->where('user_id',$user->id)->whereNull('accepted_at')->where('status','pending')))
          ->when($filter==='working',fn($q)=>$q->whereNotIn('status',['done','cancelled'])->whereHas('assignees',fn($a)=>$a->where('user_id',$user->id)->whereIn('status',['processing','in_progress'])))
          ->when($filter==='reported',fn($q)=>$q->whereNotIn('status',['done','cancelled'])->whereHas('assignees',fn($a)=>$a->where('user_id',$user->id)->where('status','completed')))
          ->when($filter==='history',fn($q)=>$q->whereIn('status',['done','cancelled']))
          ->when($filter==='votes',fn($q)=>$q->whereRaw('1=0'))
          ->when($request->filled('task_q'),function($q)use($request){
              $term='%'.$request->input('task_q').'%';
              $q->where(fn($s)=>$s->where('title','like',$term)->orWhere('code','like',$term)->orWhereHas('creator',fn($c)=>$c->where('name','like',$term))->orWhereHas('assignees.user',fn($a)=>$a->where('name','like',$term)));
          })
          ->when($request->filled('task_status'),fn($q)=>$request->task_status==='processing' ? $q->whereIn('status',['processing','in_progress']) : $q->where('status',$request->task_status))
          ->when($request->filled('task_kind'),fn($q)=>$request->task_kind==='execution' ? $q->where(fn($k)=>$k->where('work_kind','execution')->orWhereNull('work_kind')) : $q->where('work_kind','coordination'))
          ->when($request->filled('task_from'),fn($q)=>$q->whereDate('created_at','>=',$request->task_from))
          ->when($request->filled('task_to'),fn($q)=>$q->whereDate('created_at','<=',$request->task_to));
        $proposals=OperatingProposal::with('creator')->withCount(['votes','votes as voted_count'=>fn($q)=>$q->whereNotNull('choice')])->where(function($q)use($user,$filter){
            $q->where('created_by',$user->id)->orWhereHas('votes',fn($v)=>$v->where('user_id',$user->id));
            if($user->hasRole('admin') && in_array($filter,['all','deleted'],true))$q->orWhereRaw('1=1');
        })->when($request->filled('proposal_q'),function($q)use($request){
            $term='%'.$request->proposal_q.'%';
            $q->where(fn($s)=>$s->where('title','like',$term)->orWhereHas('creator',fn($c)=>$c->where('name','like',$term)));
        })->when($request->filled('proposal_status'),function($q)use($request){
            if($request->proposal_status==='expired') $q->where('status','open')->where('closes_at','<=',now());
            elseif($request->proposal_status==='open') $q->where('status','open')->where('closes_at','>',now());
            else $q->where('status',$request->proposal_status);
        })->when($request->filled('proposal_vote'),fn($q)=>$q->whereHas('votes',fn($v)=>$v->where('user_id',$user->id)->when($request->proposal_vote==='pending',fn($v)=>$v->whereNull('choice'),fn($v)=>$v->whereNotNull('choice'))))
          ->when($request->filled('proposal_from'),fn($q)=>$q->whereDate('created_at','>=',$request->proposal_from))
          ->when($request->filled('proposal_to'),fn($q)=>$q->whereDate('created_at','<=',$request->proposal_to));
        $tasks->where(function($q)use($selectedKinds){
            $q->whereRaw('1=0');
            if(in_array('execution',$selectedKinds,true)) $q->orWhere(fn($k)=>$k->where('work_kind','execution')->orWhereNull('work_kind'));
            if(in_array('coordination',$selectedKinds,true)) $q->orWhere('work_kind','coordination');
        });
        if(str_starts_with($sharedStatus,'vote_')) $tasks->whereRaw('1=0');
        if(!in_array('vote',$selectedKinds,true) || str_starts_with($sharedStatus,'task_') || in_array($filter,['unaccepted','working','reported','verification','deleted'],true)) $proposals->whereRaw('1=0');
        if($filter==='assigned') $proposals->where('created_by',$user->id);
        if($filter==='received') $proposals->whereHas('votes',fn($q)=>$q->where('user_id',$user->id));
        if($filter==='history') $proposals->where('status','!=','open');
        if($filter==='overdue') $proposals->where('status','open')->where('closes_at','<',now());
        $taskRows=(clone $tasks)->select([])->selectRaw("task_assignments.id as entity_id, 'task' as entity_type, task_assignments.created_at as created_at, task_assignments.due_date as deadline")->toBase();
        $proposalRows=(clone $proposals)->select([])->selectRaw("operating_proposals.id as entity_id, 'vote' as entity_type, operating_proposals.created_at as created_at, operating_proposals.closes_at as deadline")->toBase();
        $listingQuery=DB::query()->fromSub($taskRows->unionAll($proposalRows),'operating_listing');
        $sort=$request->input('sort','newest');
        if($sort==='due') $listingQuery->orderByRaw('deadline IS NULL')->orderBy('deadline');
        $listing=$listingQuery->orderBy('created_at',$sort==='oldest'?'asc':'desc')->orderBy('entity_type')->orderByDesc('entity_id')
            ->paginate((int)$request->input('per_page',20))->appends($request->only(['filter','kinds','kinds_present','q','status','from','to','sort','per_page']));
        $taskModels=TaskAssignment::withTrashed()->with(['creator','assignees.user','approvalSteps'])->whereIn('id',$listing->getCollection()->where('entity_type','task')->pluck('entity_id'))->get()->keyBy('id');
        $proposalModels=OperatingProposal::with('creator')->withCount(['votes','votes as voted_count'=>fn($q)=>$q->whereNotNull('choice')])
            ->whereIn('id',$listing->getCollection()->where('entity_type','vote')->pluck('entity_id'))->get()->keyBy('id');
        $listing->getCollection()->transform(fn($row)=>(object)['type'=>$row->entity_type,'entity'=>$row->entity_type==='task'?$taskModels[$row->entity_id]:$proposalModels[$row->entity_id]]);
        $layout=TaskWorkspace::layout($user);
        $canCreate=$this->canCreate($user);
        return view('operating.index',compact('listing','selectedKinds','layout','filter','canCreate'))->with('settings',$this->settings());
    }
    private function settings()
    {
        return Cache::remember('settings',60,fn()=>Setting::all()->keyBy('key'));
    }

    public function deletedTask(Request $request, int $id)
    {
        abort_unless($request->user()->hasRole('admin'),403);
        $task=TaskAssignment::onlyTrashed()->findOrFail($id);
        return app(TaskAssignmentController::class)->show($task);
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
        return view('operating.create',compact('layout','users'))->with('settings',$this->settings());
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
        return view('operating.show',compact('proposal','layout'))->with('settings',$this->settings());
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

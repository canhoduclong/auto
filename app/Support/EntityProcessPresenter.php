<?php

namespace App\Support;

use App\Models\ProcessDefinition;
use App\Models\ProcessSubject;
use App\Services\ProcessEngine;

class EntityProcessPresenter
{
    public function compose($view): void
    {
        if (! auth()->check()) {
            return;
        }$data = $view->getData();
        $product = str_contains($view->name(), 'products');
        $type = $product ? 'product' : 'order';
        $collection = $data[$product ? 'products' : 'orders'] ?? null;
        $single = $data[$product ? 'product' : 'order'] ?? null;
        $ids = $collection ? collect($collection instanceof \Illuminate\Pagination\AbstractPaginator ? $collection->items() : $collection)->pluck('id')->all() : ($single ? [$single->id] : []);
        $engine = app(ProcessEngine::class);
        $subjects = ProcessSubject::with('run')->where('subject_type', $type)->whereIn('subject_id', $ids)->get()->filter(fn ($s) => $s->run && $engine->canView($s->run, auth()->user()))->groupBy('subject_id');
        $defs = ProcessDefinition::where('is_active', true)->where('activity', $product ? 'product_review' : 'order_review')->get()->filter(fn ($d) => $engine->hasRole(auth()->user(), $d->configuration['initiator_role']));
        $view->with('entityProcessSubjects', $subjects)->with('entityProcessDefinitions', $defs);
    }
}

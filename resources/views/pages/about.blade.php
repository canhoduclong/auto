@extends('layouts.site')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Giới thiệu', 'url' => route('pages.about')], 
    ['label' => 'Giới thiệu', 'url' => '']
]"/>
@endsection

@section('content')
    <div class="container">
        <h1>{{ $pages->first()->title ?? 'Giới thiệu' }}</h1>
        <p>{!! $pages->first()->content ?? 'This is the about page.' !!}</p>
    </div>
@endsection

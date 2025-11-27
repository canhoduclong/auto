@extends('layouts.site')

@section('breadcrumb')
    <x-breadcrumb
    title="Giới thiệu"
    :items="[  
        ['label' => 'Giới thiệu', 'url' => '']
    ]"/> 
@endsection 

@section('content')
    <div class="container">
        <h1>Giới thiệu</h1>
        <p>This is the about page.</p>
    </div>
@endsection

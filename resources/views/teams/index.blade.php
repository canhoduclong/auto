@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">{{ __('teams.index.title') }}</h2>
        <a href="{{ route('teams.create') }}" class="btn btn-primary">{{ __('teams.index.add') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>{{ __('teams.index.id') }}</th>
                <th>{{ __('teams.index.name') }}</th>
                <th>{{ __('teams.index.code') }}</th>
                <th>{{ __('teams.index.users_count') }}</th>
                <th>{{ __('teams.index.note') }}</th>
                <th>{{ __('teams.index.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teams as $team)
                <tr>
                    <td>{{ $team->id }}</td>
                    <td>{{ $team->name }}</td>
                    <td>{{ $team->code ?? '-' }}</td>
                    <td>{{ $team->users_count }}</td>
                    <td>{{ $team->note ?? '-' }}</td>
                    <td>
                        <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-warning">{{ __('common.actions.edit') }}</a>
                        <form action="{{ route('teams.destroy', $team) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('teams.index.delete_confirm') }}')">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">{{ __('teams.index.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $teams->links() }}
</div>
@endsection

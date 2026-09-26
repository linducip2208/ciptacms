@extends('admin.layout')
@section('title', 'Components')
@section('crumb', 'Page Builder / Components')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Component library</h2>
        <div class="text-muted">
            The authoritative list, read from
            <code>App\Core\Services\BlockLibrary::catalog()</code>. Adding a component there adds it to the builder palette
            and the public renderer in one step.
        </div>
    </div>
    <a href="{{ route('admin.cms.sections.index') }}" class="btn btn-outline">Sections</a>
</div>

@php
    $grouped = collect($catalog)->groupBy('group');
@endphp

@foreach($grouped as $group => $components)
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ ucfirst($group) }} ({{ $components->count() }})</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Component</th><th>Type key</th><th>Inspector fields</th><th>Defaults</th></tr></thead>
                <tbody>
                    @foreach($components as $c)
                        <tr>
                            <td>
                                <b>{{ $c['label'] }}</b>
                                @if(!empty($c['icon']))
                                    <i class="{{ $c['icon'] }} text-muted"></i>
                                @endif
                            </td>
                            <td><code>{{ $c['type'] }}</code></td>
                            <td>
                                @foreach($c['fields'] as $key => $field)
                                    <span class="badge" title="{{ $field['type'] ?? 'text' }}">{{ $field['label'] ?? $key }}</span>
                                @endforeach
                            </td>
                            <td class="text-muted text-xs" style="max-width:240px">
                                <div class="text-truncate-cell">{{ json_encode($c['defaults']) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection

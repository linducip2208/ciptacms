@extends('admin.layout')
@section('title', $label.' Updates')
@section('crumb', 'Updates / '.$label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">{{ $label }} updates</h2>
        <div class="text-muted">Versions come from each package's manifest on disk.</div>
    </div>
    <form method="POST" action="{{ route('admin.updates.check') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <button class="btn btn-primary">Check</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Package</th><th>Slug</th><th>Installed</th><th>Latest</th><th>Installed?</th><th>Active?</th><th>State</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><b>{{ $row['name'] }}</b></td>
                        <td><code>{{ $row['slug'] }}</code></td>
                        <td><code>v{{ $row['current'] }}</code></td>
                        <td><code>v{{ $row['latest'] }}</code></td>
                        <td>
                            <span class="badge bg-{{ $row['installed'] ? 'green' : 'secondary' }}">
                                {{ $row['installed'] ? 'yes' : 'no' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $row['is_active'] ? 'green' : 'secondary' }}">
                                {{ $row['is_active'] ? 'yes' : 'no' }}
                            </span>
                        </td>
                        <td>
                            @if($row['update_available'])
                                <span class="badge bg-orange">update available</span>
                            @else
                                <span class="badge bg-green">current</span>
                            @endif
                        </td>
                        <td>
                            @if($row['update_available'])
                                <form method="POST" action="{{ route('admin.updates.apply') }}"
                                      onsubmit="return confirm('Apply this update? A full backup runs first.')">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <input type="hidden" name="slug" value="{{ $row['slug'] }}">
                                    <input type="hidden" name="to_version" value="{{ $row['latest'] }}">
                                    <input type="hidden" name="confirm" value="1">
                                    <button class="text-indigo-600">Apply</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        No {{ strtolower($label) }} discovered. Drop folders with a
                        <code>{{ $type === 'theme' ? 'theme' : rtrim($type, 'e') }}.json</code> into
                        <code>{{ $type }}s/</code>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

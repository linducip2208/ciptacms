@extends('admin.layout')
@section('title', 'SEO')
@section('crumb', 'SEO / '.ucfirst($type))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">SEO metadata</h2>
        <div class="text-muted">Overrides stored per record. Public pages fall back to the global SEO settings.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.seo.sitemap') }}" class="btn btn-outline">Sitemap</a>
        <a href="{{ route('admin.seo.robots') }}" class="btn btn-outline">robots.txt</a>
        <a href="{{ route('admin.seo.redirects') }}" class="btn btn-outline">Redirects</a>
        <a href="{{ route('admin.seo.schema') }}" class="btn btn-outline">Schema</a>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    @foreach(['all' => 'All', 'page' => 'Pages', 'post' => 'Posts', 'other' => 'Other'] as $slug => $label)
        <li class="nav-item">
            <a class="nav-link {{ $type === $slug ? 'active' : '' }}"
               href="{{ route('admin.seo.index', ['type' => $slug]) }}">
                {{ $label }} <span class="badge bg-secondary ms-1">{{ $counts[$slug] ?? 0 }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Type</th><th>Record</th><th>Meta title</th><th>Robots</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $meta)
                    @php
                        $typeSlug = match ($meta->seoable_type) {
                            \App\Models\Page::class => 'page',
                            \App\Models\Post::class => 'post',
                            default => null,
                        };
                    @endphp
                    <tr>
                        <td><code class="text-xs">{{ class_basename($meta->seoable_type) }}</code></td>
                        <td>
                            @if($meta->seoable)
                                <b>{{ $meta->seoable->title ?? $meta->seoable->name ?? '#'.$meta->seoable_id }}</b>
                            @else
                                <span class="text-muted">#{{ $meta->seoable_id }} (deleted)</span>
                            @endif
                        </td>
                        <td>
                            @if($meta->meta_title)
                                {{ \Illuminate\Support\Str::limit($meta->meta_title, 60) }}
                            @else
                                <span class="text-muted">not set</span>
                            @endif
                        </td>
                        <td><span class="badge">{{ $meta->robots ?: 'index,follow' }}</span></td>
                        <td>
                            @if($typeSlug)
                                <a class="text-indigo-600" href="{{ route('admin.seo.edit', [$typeSlug, $meta->seoable_id]) }}">Edit</a>
                            @else
                                <span class="text-muted small">edit from its own module</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No SEO records yet. Edit a page or post to add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection

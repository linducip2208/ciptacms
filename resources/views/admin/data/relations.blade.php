@extends('admin.layout')
@section('title', 'Relations')
@section('crumb', 'Data / Relations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Relation types</h2>
        <div class="text-muted">Supported relationship kinds for the data builder and module models.</div>
    </div>
    <a href="{{ route('admin.cms.types.index') }}" class="btn btn-outline">Content types</a>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['hasOne', 'One child per parent', 'A single related record. e.g. a page has one hero block.'],
        ['hasMany', 'Many children per parent', 'A collection. e.g. an order has many line items.'],
        ['belongsTo', 'Each record points at one parent', 'The inverse side of hasOne/hasMany.'],
        ['belongsToMany', 'Many-to-many with a pivot', 'e.g. posts tagged with many tags.'],
        ['morphOne', 'One polymorphic child', 'A single related record across any type. e.g. SEO on pages, posts and services.'],
        ['morphMany', 'Many polymorphic children', 'Comments, revisions and activity records attached to any type.'],
    ] as [$name, $summary, $detail])
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title"><code>{{ $name }}</code></h3>
                    <p class="text-muted small mb-2">{{ $summary }}</p>
                    <p class="text-muted text-xs mb-0">{{ $detail }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Polymorphic relations in use</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Relation</th><th>Owner model</th><th>Kind</th><th></th></tr></thead>
            <tbody>
                @foreach([
                    ['seo', \App\Models\Page::class, 'morphOne'],
                    ['seo', \App\Models\Post::class, 'morphOne'],
                    ['seo', \App\Models\Cp\Service::class, 'morphOne'],
                    ['seo', \App\Models\Cp\Product::class, 'morphOne'],
                    ['seo', \App\Models\Cp\Portfolio::class, 'morphOne'],
                    ['seo', \App\Models\Cp\Career::class, 'morphOne'],
                    ['revisions', \App\Models\Page::class, 'hasMany'],
                    ['comments', \App\Models\Post::class, 'hasMany'],
                    ['images', \App\Models\Cp\GalleryAlbum::class, 'hasMany'],
                    ['applications', \App\Models\Cp\Career::class, 'hasMany'],
                ] as [$name, $model, $kind])
                    <tr>
                        <td><code>{{ $name }}</code></td>
                        <td>{{ class_basename($model) }}</td>
                        <td><span class="badge">{{ $kind }}</span></td>
                        <td class="text-muted small">declared on <code>{{ $model }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

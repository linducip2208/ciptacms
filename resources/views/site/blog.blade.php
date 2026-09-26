@extends('site.layout')
@section('title', ($seo['title'] ?? 'Blog').' — '.setting('general.site_name', 'Lindu CMS'))

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Blog'],
    ]) !!}
    <div class="sec-head"><h2 class="page-title">Blog</h2><p>{{ setting('general.blog_intro', 'Insights, news and updates.') }}</p></div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid g3">
            @forelse($posts as $p)
                <div class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column">
                    @if($p->featured_image)
                        <img src="{{ $p->featured_image }}" alt="{{ $p->title }}" style="width:100%;height:180px;object-fit:cover;display:block" loading="lazy">
                    @endif
                    <div style="padding:18px;flex:1;display:flex;flex-direction:column">
                        @if($p->category)<span class="badge" style="align-self:flex-start">{{ $p->category->name }}</span>@endif
                        <h3 style="margin:8px 0 6px"><a href="{{ route('site.post', $p->slug) }}" style="text-decoration:none">{{ $p->title }}</a></h3>
                        <p style="color:#64748b;margin:0 0 10px;flex:1">{{ \Illuminate\Support\Str::limit($p->excerpt ?: strip_tags((string) $p->body), 120) }}</p>
                        <div style="color:#94a3b8;font-size:.85rem">
                            {{ optional($p->published_at)->format('M j, Y') }} · {{ $p->views }} views
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty" style="grid-column:1/-1">No posts published yet.</div>
            @endforelse
        </div>
        <div style="margin-top:26px">{{ $posts->links() }}</div>
    </div>
</section>
@endsection

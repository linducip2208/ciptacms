@extends('site.layout')
@section('title', $seo['title'] ?? 'Products')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Products'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Our Products</h2>
        <p>{{ setting('general.products_intro', 'Solutions built to solve real business problems.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid g3">
            @forelse($products as $p)
                <div class="card">
                    @if($p->image)
                        <img src="{{ $p->image }}" alt="{{ $p->title }}" style="width:100%;height:170px;object-fit:cover;border-radius:10px;margin-bottom:14px">
                    @endif
                    <h3><a href="{{ route('site.product', $p->slug) }}" style="text-decoration:none">{{ $p->title }}</a></h3>
                    <p style="color:#64748b;margin:0">{{ \Illuminate\Support\Str::limit($p->excerpt, 110) }}</p>
                </div>
            @empty
                <div class="empty" style="grid-column:1/-1">No products published yet.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection

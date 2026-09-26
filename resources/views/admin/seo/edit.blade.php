@extends('admin.layout')
@section('title', 'SEO — '.$row->title)
@section('crumb', 'SEO / '.ucfirst($type).' / Edit')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.seo.index', ['type' => $type]) }}" class="text-muted">← Back to SEO</a>
    @if($row->status === 'published')
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-outline">View live page ↗</a>
    @endif
</div>

<form method="POST" action="{{ route('admin.seo.update', [$type, $row->id]) }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Meta title <small class="text-muted">(max 60 characters for best results)</small></label>
                        <input name="meta_title" value="{{ old('meta_title', $meta['meta_title'] ?? '') }}" class="form-control" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Meta description <small class="text-muted">(max 160 characters)</small></label>
                        <textarea name="meta_description" rows="3" class="form-control" maxlength="500">{{ old('meta_description', $meta['meta_description'] ?? '') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Canonical URL</label>
                            <input name="canonical" value="{{ old('canonical', $meta['canonical'] ?? '') }}" class="form-control" placeholder="{{ $publicUrl }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Robots</label>
                            <select name="robots" class="form-control">
                                @foreach(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $v)
                                    <option value="{{ $v }}" @selected(($meta['robots'] ?? 'index,follow') === $v)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Twitter card</label>
                            <select name="twitter_card" class="form-control">
                                @foreach(['summary_large_image' => 'Large image', 'summary' => 'Summary'] as $v => $l)
                                    <option value="{{ $v }}" @selected(($meta['twitter_card'] ?? 'summary_large_image') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h4 class="mt-4">OpenGraph</h4>
                    <div class="form-group">
                        <label>OG title</label>
                        <input name="og_title" value="{{ old('og_title', $meta['og_title'] ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>OG description</label>
                        <textarea name="og_description" rows="2" class="form-control">{{ old('og_description', $meta['og_description'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>OG image URL</label>
                        <input name="og_image" value="{{ old('og_image', $meta['og_image'] ?? '') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Custom JSON-LD schema</label>
                        <textarea name="schema" rows="6" class="form-control font-monospace"
                                  placeholder='&#123;"&#64;context":"https://schema.org","&#64;type":"Article", …&#125;'>{{ old('schema', $meta['schema'] ?? '') }}</textarea>
                        <small class="text-muted">Injected verbatim as <code>application/ld+json</code>. Leave empty to use the auto-generated Organization markup.</small>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.seo.index', ['type' => $type]) }}" class="btn btn-outline me-2">Cancel</a>
                    <button class="btn btn-primary">Save SEO</button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Search preview</h3></div>
                <div class="card-body">
                    @php
                        $gTitle = $meta['meta_title'] ?? $row->title;
                        $gDesc = $meta['meta_description'] ?? ($row->excerpt ?? '');
                        $gUrl = $meta['canonical'] ?? $publicUrl;
                    @endphp
                    <div style="font-family:Arial,sans-serif">
                        <div style="color:#202124;font-size:18px;line-height:1.3">{{ \Illuminate\Support\Str::limit($gTitle, 60) }}</div>
                        <div style="color:#0d652d;font-size:13px">{{ \Illuminate\Support\Str::limit(str_replace(url('/'), '', $gUrl), 60) }}</div>
                        <div style="color:#4d5156;font-size:13px;margin-top:3px">{{ \Illuminate\Support\Str::limit($gDesc, 160) ?: 'No meta description set.' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

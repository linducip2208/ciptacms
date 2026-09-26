@extends('admin.layout')
@php
    $publicRoute = [
        'services' => 'site.service', 'products' => 'site.product',
        'portfolio' => 'site.portfolio.item', 'careers' => 'site.career',
    ][$resource] ?? null;
@endphp
@section('title', ($row->exists ? 'Edit' : 'New').' '.$def['singular'])
@section('crumb', 'Company Profile / '.$def['label'].' / '.($row->exists ? 'Edit' : 'New'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.company.index', $resource) }}" class="text-muted">← Back to {{ $def['label'] }}</a>
    @if($row->exists && $publicRoute && $row->slug)
        <div class="d-flex gap-2">
            <a class="btn btn-outline" href="{{ route($publicRoute, $row->slug) }}" target="_blank" rel="noopener">View on site ↗</a>
        </div>
    @endif
</div>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($def['fields'] as $name => $field)
                            @php
                                $val = old($name, $row->{$name} ?? null);
                                if (is_array($val)) $val = implode("\n", array_map(fn($v) => is_array($v) ? json_encode($v) : (string) $v, $val));
                                $col = $field['col'] ?? 12;
                            @endphp
                            <div class="col-md-{{ $col }}">
                                <label class="form-label" for="f-{{ $name }}">
                                    {{ \Illuminate\Support\Str::headline($name) }}
                                    @if(str_contains($field['rules'] ?? '', 'required')) <span class="text-rose-600">*</span> @endif
                                </label>

                                @php $t = $field['type'] ?? 'text'; @endphp

                                @if($t === 'textarea' || $t === 'richtext')
                                    <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $t === 'richtext' ? 10 : 4 }}"
                                              class="form-control @if($t === 'richtext') font-monospace @endif">{{ $val }}</textarea>
                                @elseif($t === 'lines')
                                    <textarea id="f-{{ $name }}" name="{{ $name }}" rows="5" class="form-control font-monospace">{{ $val }}</textarea>
                                @elseif($t === 'select')
                                    <select id="f-{{ $name }}" name="{{ $name }}" class="form-control">
                                        <option value="">—</option>
                                        @foreach(($field['options'] ?? []) as $ov => $ol)
                                            <option value="{{ $ov }}" @selected((string) $val === (string) $ov)>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @elseif($t === 'number')
                                    <input id="f-{{ $name }}" type="number" name="{{ $name }}" value="{{ $val }}" class="form-control">
                                @elseif($t === 'date')
                                    <input id="f-{{ $name }}" type="date" name="{{ $name }}"
                                           value="{{ $val ? \Illuminate\Support\Carbon::parse($val)->format('Y-m-d') : '' }}" class="form-control">
                                @elseif($t === 'image')
                                    <div class="d-flex gap-2 align-items-start">
                                        <input id="f-{{ $name }}" name="{{ $name }}" value="{{ $val }}" class="form-control" placeholder="/storage/… or https://…">
                                        @if($val)
                                            <img src="{{ $val }}" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:4px">
                                        @endif
                                    </div>
                                    <small class="text-muted">Paste a URL, or pick from <a href="{{ route('admin.media.index') }}" target="_blank">Media Library</a>.</small>
                                @else
                                    <input id="f-{{ $name }}" name="{{ $name }}" value="{{ $val }}" class="form-control">
                                @endif

                                @if(!empty($field['help']))
                                    <small class="text-muted d-block mt-1">{{ $field['help'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Publish</h3></div>
                <div class="card-body">
                    <button class="btn btn-primary w-100">{{ $row->exists ? 'Save changes' : 'Create '.$def['singular'] }}</button>
                    <a href="{{ route('admin.company.index', $resource) }}" class="btn btn-outline w-100 mt-2">Cancel</a>
                    @if($row->exists)
                        <div class="text-muted small mt-3">
                            Slug: <code>{{ $row->slug ?? '—' }}</code><br>
                            Created {{ optional($row->created_at)->diffForHumans() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">SEO</h3></div>
                <div class="card-body">
                    @php $seo = $row->exists ? \App\Models\SeoMeta::firstWhere(['seoable_type' => get_class($row), 'seoable_id' => $row->getKey()]) : null; @endphp
                    <div class="form-group">
                        <label>Meta title</label>
                        <input name="seo[meta_title]" value="{{ old('seo.meta_title', $seo->meta_title ?? '') }}" class="form-control" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Meta description</label>
                        <textarea name="seo[meta_description]" rows="3" class="form-control" maxlength="500">{{ old('seo.meta_description', $seo->meta_description ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Canonical URL</label>
                        <input name="seo[canonical]" value="{{ old('seo.canonical', $seo->canonical ?? '') }}" class="form-control">
                    </div>
                    <div class="row">
                        <div class="form-group col-6">
                            <label>Robots</label>
                            <select name="seo[robots]" class="form-control">
                                @foreach(['index,follow' => 'Index, Follow', 'noindex,follow' => 'No Index', 'index,nofollow' => 'No Follow', 'noindex,nofollow' => 'No Index, No Follow'] as $v => $l)
                                    <option value="{{ $v }}" @selected(($seo->robots ?? 'index,follow') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label>Twitter card</label>
                            <select name="seo[twitter_card]" class="form-control">
                                @foreach(['summary_large_image' => 'Large', 'summary' => 'Summary'] as $v => $l)
                                    <option value="{{ $v }}" @selected(($seo->twitter_card ?? 'summary_large_image') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>OG image URL</label>
                        <input name="seo[og_image]" value="{{ old('seo.og_image', $seo->og_image ?? '') }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

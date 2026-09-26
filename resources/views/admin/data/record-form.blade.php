@extends('admin.layout')
@section('title', ($record->exists ? 'Edit' : 'New').' '.$ct->name)
@section('crumb', 'Data / '.$ct->name.' / '.($record->exists ? 'Edit' : 'New'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.cms.records.list', $ct) }}" class="text-muted">← {{ $ct->name }} records</a>
</div>

<form method="POST" action="{{ $action }}">
    @csrf
    @if($record->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Fields</h3></div>
                <div class="card-body">
                    @php $data = (array) ($record->data ?? []); @endphp
                    @forelse($ct->fields ?? [] as $f)
                        @php
                            $slug = $f['slug'];
                            $val = old('data.'.$slug, $data[$slug] ?? null);
                            $opts = (array) ($f['options'] ?? []);
                        @endphp
                        <div class="form-group">
                            <label class="form-label">
                                {{ $f['name'] ?? $slug }}
                                @if($f['required'] ?? false)<span class="text-rose-600">*</span>@endif
                                <span class="badge ml-1">{{ $f['type'] }}</span>
                            </label>

                            @php $t = $f['type']; @endphp
                            @if($t === 'boolean')
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="data[{{ $slug }}]" value="1" @checked((bool) $val)>
                                    <span class="form-check-label">{{ (bool) $val ? 'Yes' : 'No' }}</span>
                                </label>
                            @elseif($t === 'select')
                                <select name="data[{{ $slug }}]" class="form-control">
                                    <option value="">—</option>
                                    @foreach($opts as $k => $v)
                                        @php $ov = is_int($k) ? $v : $k; $ol = is_int($k) ? $v : $v; @endphp
                                        <option value="{{ $ov }}" @selected((string) $val === (string) $ov)>{{ $ol }}</option>
                                    @endforeach
                                </select>
                            @elseif($t === 'multiselect')
                                <select name="data[{{ $slug }}][]" class="form-control" multiple>
                                    @foreach($opts as $k => $v)
                                        @php $ov = is_int($k) ? $v : $k; @endphp
                                        <option value="{{ $ov }}" @selected(in_array((string) $ov, array_map('strval', (array) $val), true))>{{ $v }}</option>
                                    @endforeach
                                </select>
                            @elseif(in_array($t, ['longtext', 'richtext', 'repeater', 'json'], true))
                                <textarea name="data[{{ $slug }}]" rows="5"
                                          class="form-control {{ in_array($t, ['repeater', 'json']) ? 'font-monospace' : '' }}">{{ is_array($val) ? json_encode($val, JSON_PRETTY_PRINT) : $val }}</textarea>
                            @elseif($t === 'number' || $t === 'decimal')
                                <input type="number" step="{{ $t === 'decimal' ? '0.01' : '1' }}" name="data[{{ $slug }}]" value="{{ $val }}" class="form-control">
                            @elseif($t === 'date')
                                <input type="date" name="data[{{ $slug }}]" value="{{ $val ? \Illuminate\Support\Carbon::parse($val)->format('Y-m-d') : '' }}" class="form-control">
                            @elseif($t === 'datetime')
                                <input type="datetime-local" name="data[{{ $slug }}]" value="{{ $val ? \Illuminate\Support\Carbon::parse($val)->format('Y-m-d\TH:i') : '' }}" class="form-control">
                            @elseif($t === 'email')
                                <input type="email" name="data[{{ $slug }}]" value="{{ $val }}" class="form-control">
                            @elseif($t === 'url')
                                <input type="url" name="data[{{ $slug }}]" value="{{ $val }}" class="form-control">
                            @elseif($t === 'image' || $t === 'file')
                                <div class="d-flex gap-2">
                                    <input name="data[{{ $slug }}]" value="{{ $val }}" class="form-control" placeholder="/storage/… or https://…">
                                    @if($val && $t === 'image')<img src="{{ $val }}" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:4px">@endif
                                </div>
                            @else
                                <input name="data[{{ $slug }}]" value="{{ $val }}" class="form-control">
                            @endif

                            @if(!empty($f['help']))<small class="text-muted d-block mt-1">{{ $f['help'] }}</small>@endif
                        </div>
                    @empty
                        <p class="text-muted">This content type has no fields yet. Add some first.</p>
                    @endforelse
                </div>
                <div class="card-footer text-right"><button class="btn btn-primary">Save record</button></div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Publish</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="published" @selected(($record->status ?? 'published') === 'published')>Published</option>
                            <option value="draft" @selected(($record->status ?? '') === 'draft')>Draft</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100">{{ $record->exists ? 'Save changes' : 'Create record' }}</button>
                    <a href="{{ route('admin.cms.records.list', $ct) }}" class="btn btn-outline w-100 mt-2">Cancel</a>
                    @if($record->exists)
                        <div class="text-muted small mt-3">
                            ID {{ $record->id }}<br>Created {{ optional($record->created_at)->diffForHumans() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

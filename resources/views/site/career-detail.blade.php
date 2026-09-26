@extends('site.layout')
@section('title', $seo['title'] ?? $item->position)

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Careers', 'url' => route('site.careers')],
        ['name' => $item->position],
    ]) !!}
</div>

<section style="padding-top:24px">
    <div class="wrap" style="max-width:900px">
        <h1 class="page-title">{{ $item->position }}</h1>
        <div style="color:#64748b;display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px">
            @if($item->location)<span>📍 {{ $item->location }}</span>@endif
            @if($item->employment_type)<span>💼 {{ $item->employment_type }}</span>@endif
            @if($item->deadline)<span>⏳ {{ $item->deadline->format('M j, Y') }}</span>@endif
        </div>

        @if($item->description)<div class="prose">{!! $item->description !!}</div>@endif

        @if($item->requirements)
            <h3 style="margin-top:30px">Requirements</h3>
            <ul style="padding-left:20px;color:#475569">
                @foreach($item->requirements as $req)
                    <li>{{ is_array($req) ? ($req['title'] ?? json_encode($req)) : $req }}</li>
                @endforeach
            </ul>
        @endif

        @if($item->deadline && $item->deadline->isPast())
            <div class="alert alert-err" style="margin-top:26px">This position has closed. Thank you for your interest.</div>
        @else
            <h3 style="margin-top:36px">Apply for this position</h3>
            <form method="POST" action="{{ route('site.career.apply', $item) }}" enctype="multipart/form-data" class="card">
                @csrf
                <div class="grid g2">
                    <div class="form-row">
                        <label for="ap-name">Full name *</label>
                        <input id="ap-name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-row">
                        <label for="ap-email">Email *</label>
                        <input id="ap-email" type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <label for="ap-phone">Phone / WhatsApp</label>
                    <input id="ap-phone" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="form-row">
                    <label for="ap-cv">CV / Resume (PDF, DOC, DOCX — max 4MB)</label>
                    <input id="ap-cv" type="file" name="cv" accept=".pdf,.doc,.docx">
                </div>
                <div class="form-row">
                    <label for="ap-cover">Cover letter</label>
                    <textarea id="ap-cover" name="cover_letter" placeholder="Tell us why you are a good fit…">{{ old('cover_letter') }}</textarea>
                </div>
                <button class="btn" type="submit">Submit Application</button>
            </form>
        @endif
    </div>
</section>
@endsection

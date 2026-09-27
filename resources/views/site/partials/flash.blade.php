{{--
    Flash and validation messages. Rendered on every public page through one
    partial so a message looks the same wherever it is produced.
--}}
<div class="container-xl mt-3">
    @if (session('ok'))
        <x-site.alert variant="success">{{ session('ok') }}</x-site.alert>
    @endif

    @if (session('error'))
        <x-site.alert variant="danger">{{ session('error') }}</x-site.alert>
    @endif

    @if ($errors->any())
        <x-site.alert variant="danger">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-site.alert>
    @endif
</div>

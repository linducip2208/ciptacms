@if (session('ok'))
    <div class="container-xl mt-3">
        <div class="alert alert-success d-flex align-items-center mb-0" role="status">
            <i class="ti ti-check-circle me-2"></i>
            <span>{{ session('ok') }}</span>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="container-xl mt-3">
        <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <span>{{ session('error') }}</span>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="container-xl mt-3">
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

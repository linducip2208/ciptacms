@props([
    'title' => 'Nothing to show yet',
    'message' => null,
    'icon' => 'ti-inbox',
])

<div class="text-center py-5 text-secondary">
    <div class="display-6 mb-2 opacity-50"><i class="ti {{ $icon }}"></i></div>
    <p class="h4 mb-1">{{ $title }}</p>
    @if ($message)
        <p class="mb-0">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>

@props(['items' => []])

@if (filled($items))
    <div class="accordion" id="faqAccordion">
        @foreach ($items as $i => $item)
            @php
                $id = 'faq-' . ($item['id'] ?? $i);
                $question = $item['question'] ?? '';
                $answer = $item['answer'] ?? '';
            @endphp
            <div class="accordion-item">
                <h3 class="accordion-header" id="h-{{ $id }}">
                    <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#c-{{ $id }}"
                            aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="c-{{ $id }}">
                        {{ $question }}
                    </button>
                </h3>
                <div id="c-{{ $id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                     aria-labelledby="h-{{ $id }}"
                     @if ($i === 0) data-bs-parent="#faqAccordion" @endif>
                    <div class="accordion-body lindu-prose">{!! nl2br(e($answer)) !!}</div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <x-site.empty-state title="No questions published yet" icon="ti-help-circle" />
@endif

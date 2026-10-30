{{-- AI analysis button, top right of the first card-body of a show page.
     Rendered only when AI is enabled and the user may analyse this object (never for auditors). --}}
@if (\App\Models\Configuration::aiEnabled() && \App\Services\Ai\AiSubjects::canUseAi(Auth::user(), $type, $object))
{{-- Zero-height anchor: the button stays top right without pushing the rows of the page --}}
<div class="ai-button-anchor">
<button type="button" class="btn btn-ai {{ $object->hasAiAnalysis() ? 'has-analysis' : '' }}"
        title="{{ trans('ai.button') }}{{ $object->hasAiAnalysis() ? ' — ' . trans('ai.has_analysis') : '' }}"
        aria-label="{{ trans('ai.button') }}"
        aria-controls="ai-panel"
        data-ai-type="{{ $type }}"
        data-ai-id="{{ $object->getKey() }}"
        data-ai-title="{{ trans(\App\Services\Ai\AiSubjects::TYPES[$type]['label']) }} {{ \App\Services\Ai\AiSubjects::title($type, $object) }}">
    <i class="bi bi-robot" aria-hidden="true"></i>
</button>
</div>
@once
    @include('partials.ai-panel')
@endonce
@endif

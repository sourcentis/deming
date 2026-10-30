@extends("layout")

@section("content")
@php
    // Tab => [label, icon, partial, data]; the documents data also feeds the templates tab
    $tabs = [
        'documents' => [trans('menu.configuration.documents'), 'bi-file-earmark-text', 'documents._settings', $documents],
        'templates' => [trans('menu.configuration.templates'), 'bi-file-earmark-word', 'documents._templates', $documents],
        'scoring' => [trans('menu.configuration.scoring'), 'bi-calculator', 'risks.scoring._list', $scoring],
        'ai' => [trans('menu.configuration.ai'), 'bi-robot', 'config._ai', $ai],
        'notifications' => [trans('menu.configuration.notifications'), 'bi-alarm', 'config._notifications', $notifications],
    ];
    $tabs = array_intersect_key($tabs, array_flip($visible));
@endphp
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'parameters'])<i class="bi bi-sliders"></i> {{ trans('menu.configuration.parameters') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <ul class="nav nav-tabs mb-3" id="parameter-tabs" role="tablist">
        @foreach ($tabs as $name => [$label, $icon])
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold {{ $tab === $name ? 'active' : '' }}"
                    id="tab-{{ $name }}-btn" data-bs-toggle="tab" data-bs-target="#tab-{{ $name }}" data-tab="{{ $name }}"
                    type="button" role="tab" aria-controls="tab-{{ $name }}" aria-selected="{{ $tab === $name ? 'true' : 'false' }}">
                <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
            </button>
        </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($tabs as $name => [$label, $icon, $partial, $data])
        <div class="tab-pane fade {{ $tab === $name ? 'show active' : '' }}" id="tab-{{ $name }}" role="tabpanel" aria-labelledby="tab-{{ $name }}-btn">
            @include($partial, $data)
        </div>
        @endforeach
    </div>

    </div>
</div>

<script>
// Keep the active tab in the URL, so that a reload or a redirect reopens it
document.addEventListener('shown.bs.tab', function (event) {
    const tab = event.target.dataset.tab;
    if (tab) {
        history.replaceState(null, '', '/config/parameters?tab=' + tab);
    }
});
</script>
@endsection

@extends("layout")

@section("styles")
<style>
    .ai-config details { border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 4px; padding: .5rem .75rem; margin-bottom: .5rem; }
    .ai-config details > summary { cursor: pointer; font-weight: 600; }
    .ai-config details[open] > summary { margin-bottom: .5rem; }
    .ai-config pre.ai-schema { max-height: 24rem; overflow: auto; font-size: .8rem; background: var(--bs-light, #f8f9fa); padding: .5rem; }
    .ai-config textarea { font-family: monospace; font-size: .85rem; }
    .ai-config .ai-privacy ul { margin-bottom: .25rem; }
    .ai-config details.ai-purge > summary { list-style: none; display: inline-block; }
    .ai-config details.ai-purge > summary::-webkit-details-marker { display: none; }
    .ai-config details.ai-purge { border: 0; padding: 0; }
</style>
@endsection

@section("content")
<div class="card ai-config">
    <div class="card-header">@include('partials.help', ['page' => 'ai_config'])<i class="bi bi-stars"></i> {{ trans('ai.config.title') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <div class="alert alert-warning ai-privacy col-lg-9" role="note">
        <strong><i class="bi bi-shield-lock"></i> {{ trans('ai.config.privacy_title') }}</strong>
        <ul>
            <li>{{ trans('ai.config.privacy_sent') }}</li>
            <li>{{ trans('ai.config.privacy_not_sent') }}</li>
            <li>{{ trans('ai.config.privacy_limit') }}</li>
            <li>{{ trans('ai.config.privacy_dpo') }}</li>
        </ul>
        <a href="{{ trans('help.url') }}ai/#privacy" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i> {{ trans('ai.config.privacy_link') }}
        </a>
    </div>

    <form method="POST" action="/config/ai/save">
        @csrf

        <div class="container-fluid px-0">
            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <strong>{{ trans('ai.config.enabled') }}</strong>
                </div>
                <div class="col-md-9 col-lg-10">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="enabled" id="enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <label for="base_url"><strong>{{ trans('ai.config.base_url') }}</strong></label>
                </div>
                <div class="col-md-9 col-lg-6">
                    <input class="form-control" type="url" name="base_url" id="base_url" value="{{ old('base_url', $base_url) }}" required maxlength="2048">
                    <small class="text-muted">{{ trans('ai.config.base_url_help') }}</small>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <label for="model"><strong>{{ trans('ai.config.model') }}</strong></label>
                </div>
                <div class="col-md-9 col-lg-4">
                    <input class="form-control" type="text" name="model" id="model" value="{{ old('model', $model) }}" required maxlength="191">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <label for="api_key"><strong>{{ trans('ai.config.api_key') }}</strong></label>
                </div>
                <div class="col-md-9 col-lg-4">
                    <input class="form-control" type="password" name="api_key" id="api_key" value="" autocomplete="new-password">
                    <small class="text-muted">{{ trans('ai.config.api_key_help') }}</small>
                </div>
                <div class="col-md-12 col-lg-4">
                    @if ($has_key)
                        <span class="badge bg-success"><i class="bi bi-key"></i> {{ trans('ai.config.api_key_saved') }}</span>
                        <div class="form-check d-inline-block ms-2">
                            <input class="form-check-input" type="checkbox" name="delete_api_key" id="delete_api_key" value="1">
                            <label class="form-check-label" for="delete_api_key">{{ trans('ai.config.api_key_delete') }}</label>
                        </div>
                    @else
                        <span class="badge bg-secondary">{{ trans('ai.config.api_key_none') }}</span>
                    @endif
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <strong>{{ trans('ai.config.prompts') }}</strong>
                </div>
                <div class="col-md-9 col-lg-10">
                    <small class="text-muted d-block mb-1">{{ trans('ai.config.prompt_help') }}</small>
                    @foreach ($prompts as $type => $prompt)
                    <details>
                        <summary>
                            {{ trans(\App\Services\Ai\AiSubjects::TYPES[$type]['label']) }}
                            <span class="badge {{ $prompt['custom'] ? 'bg-primary' : 'bg-light text-dark border' }} ms-1">
                                {{ $prompt['custom'] ? trans('ai.config.prompt_custom') : trans('ai.config.prompt_default') }}
                            </span>
                        </summary>
                        <textarea class="form-control" name="prompts[{{ $type }}]" id="prompt_{{ $type }}" rows="18" maxlength="100000">{{ old('prompts.' . $type, $prompt['value']) }}</textarea>
                        <button type="submit" class="btn btn-light border mt-1" name="restore" value="{{ $type }}" formnovalidate>
                            <i class="bi bi-arrow-counterclockwise"></i> {{ trans('ai.config.prompt_restore') }}
                        </button>
                    </details>
                    @endforeach
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <strong>{{ trans('ai.config.schema') }}</strong>
                </div>
                <div class="col-md-9 col-lg-10">
                    <details>
                        <summary><i class="bi bi-lock"></i> {{ trans('ai.config.schema') }}</summary>
                        <small class="text-muted d-block mb-1">{{ trans('ai.config.schema_help') }}</small>
                        <pre class="ai-schema">{{ $schema }}</pre>
                    </details>
                </div>
            </div>

            <div class="row mb-3">
                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <span class="bi bi-floppy"></span> {{ trans('ai.config.save') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="ai-test">
                        <span class="bi bi-plug"></span> {{ trans('ai.config.test') }}
                    </button>
                    <span id="ai-test-result" class="ms-2" role="status" aria-live="polite"></span>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="/config/ai/purge">
        @csrf
        <details class="ai-purge">
            <summary class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ trans('ai.config.purge') }}</summary>
            <div class="alert alert-danger mt-2 col-lg-7">
                <p class="mb-2">{{ trans('ai.config.purge_help') }}</p>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> {{ trans('ai.config.purge_confirm') }}
                </button>
            </div>
        </details>
    </form>

    </div>
</div>

@php
    $testLabels = [
        'running' => trans('ai.config.test_running'),
        'ok' => trans('ai.config.test_ok'),
        'failed' => trans('ai.config.test_failed'),
        'status' => trans('ai.config.test_status'),
        'latency' => trans('ai.config.test_latency'),
        'model' => trans('ai.config.test_model'),
        'error' => trans('ai.errors.generic'),
    ];
@endphp
<script>
(function () {
    const button = document.getElementById('ai-test');
    const result = document.getElementById('ai-test-result');
    const labels = @json($testLabels);

    button.addEventListener('click', function () {
        button.disabled = true;
        result.className = 'ms-2';
        result.textContent = labels.running;

        fetch('/config/ai/test', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                const parts = [data.ok ? labels.ok : labels.failed];
                if (data.status) { parts.push(labels.status + ' ' + data.status); }
                if (data.latency_ms !== undefined) { parts.push(labels.latency + ' ' + data.latency_ms + ' ms'); }
                if (data.model) { parts.push(labels.model + ' ' + data.model); }
                if (!data.ok && data.error) { parts.push(data.error); }
                result.className = 'ms-2 ' + (data.ok ? 'text-success' : 'text-danger');
                result.textContent = parts.join(' · ');
            })
            .catch(function () {
                result.className = 'ms-2 text-danger';
                result.textContent = labels.error;
            })
            .finally(function () { button.disabled = false; });
    });
})();
</script>
@endsection

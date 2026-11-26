{{-- "AI" tab of the settings page --}}
<style>
    .ai-config details { border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 4px; padding: .5rem .75rem; margin-bottom: .5rem; }
    .ai-config details > summary { cursor: pointer; font-weight: 600; }
    .ai-config details[open] > summary { margin-bottom: .5rem; }
    .ai-config .ai-privacy ul { margin-bottom: .25rem; }
    .ai-config details.ai-purge > summary { list-style: none; display: inline-block; }
    .ai-config details.ai-purge > summary::-webkit-details-marker { display: none; }
    .ai-config details.ai-purge { border: 0; padding: 0; }
    .ai-config .ai-prompt textarea { font-family: var(--bs-font-monospace, monospace); font-size: .8rem; }
    .ai-config .ai-contract { white-space: pre-wrap; word-break: break-word; font-size: .75rem; max-height: 16rem; overflow-y: auto;
        background: var(--bs-tertiary-bg, #f8f9fa); padding: .5rem; border-radius: .25rem; margin: .25rem 0 .5rem; }
</style>

<div class="ai-config">
    @include('partials.help', ['page' => 'ai_config'])

    <div class="alert alert-info col-lg-9" role="note">
        <strong><i class="bi bi-key"></i> {{ trans('ai.config.access_title') }}</strong>
        <ul class="mb-0">
            <li>{{ trans('ai.config.access_key') }}</li>
            <li>{{ trans('ai.config.access_own_engine') }}</li>
        </ul>
    </div>

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

            {{-- Disabled while AI is off (toggled live by the switch); disabled fields are not submitted --}}
            <fieldset id="ai-settings" class="border-0 p-0 m-0" {{ $enabled ? '' : 'disabled' }}>
            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <label for="base_url"><strong>{{ trans('ai.config.base_url') }}</strong></label>
                </div>
                <div class="col-md-9 col-lg-6">
                    <select class="form-select mb-1" id="ai-preset" aria-label="{{ trans('ai.config.preset') }}">
                        <option value="">{{ trans('ai.config.preset') }}…</option>
                        @foreach ($presets as $preset => $url)
                            <option value="{{ $url }}">{{ trans('ai.config.presets.' . $preset) }}</option>
                        @endforeach
                        <option value="">{{ trans('ai.config.presets.custom') }}</option>
                    </select>
                    <input class="form-control" type="url" name="base_url" id="base_url" value="{{ old('base_url', $base_url) }}" required maxlength="2048" placeholder="https://…/v1">
                    <small class="text-muted">{{ trans('ai.config.base_url_help') }}</small>
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
                    <label for="model"><strong>{{ trans('ai.config.model') }}</strong></label>
                </div>
                <div class="col-md-9 col-lg-4">
                    <input class="form-control" type="text" name="model" id="model" value="{{ old('model', $model) }}" required maxlength="191" list="ai-models" autocomplete="off">
                    <datalist id="ai-models"></datalist>
                    <small class="text-muted">{{ trans('ai.config.model_help') }}</small>
                </div>
                <div class="col-md-12 col-lg-4">
                    <button type="button" class="btn btn-light border" id="ai-load-models">
                        <span class="bi bi-list-ul"></span> {{ trans('ai.config.load_models') }}
                    </button>
                    <span id="ai-models-result" class="ms-2 small" role="status" aria-live="polite"></span>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <strong>{{ trans('ai.config.parameters') }}</strong>
                </div>
                <div class="col-md-9 col-lg-8">
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <label for="temperature" class="small">{{ trans('ai.config.temperature') }}</label>
                            <input class="form-control" type="number" name="temperature" id="temperature" value="{{ old('temperature', $temperature) }}" min="0" max="2" step="0.1" required>
                        </div>
                        <div class="col-sm-4">
                            <label for="max_tokens" class="small">{{ trans('ai.config.max_tokens') }}</label>
                            <input class="form-control" type="number" name="max_tokens" id="max_tokens" value="{{ old('max_tokens', $max_tokens) }}" min="16" max="200000" step="1" required>
                        </div>
                        <div class="col-sm-4">
                            <label for="timeout" class="small">{{ trans('ai.config.timeout') }}</label>
                            <input class="form-control" type="number" name="timeout" id="timeout" value="{{ old('timeout', $timeout) }}" min="5" max="600" step="1" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="structured_output" class="small">{{ trans('ai.config.structured_output') }}</label>
                            <select class="form-select" name="structured_output" id="structured_output">
                                @foreach (\App\Http\Controllers\AiConfigController::STRUCTURED_OUTPUTS as $mode)
                                    <option value="{{ $mode }}" {{ old('structured_output', $structured_output) === $mode ? 'selected' : '' }}>{{ trans('ai.config.structured_outputs.' . $mode) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="output_language" class="small">{{ trans('ai.config.output_language') }}</label>
                            <select class="form-select" name="output_language" id="output_language">
                                @foreach (\App\Services\AiAgentService::OUTPUT_LANGUAGES as $language)
                                    <option value="{{ $language }}" {{ old('output_language', $output_language) === $language ? 'selected' : '' }}>{{ trans('ai.config.output_languages.' . $language) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <small class="text-muted">{{ trans('ai.config.parameters_help') }}</small>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <strong>{{ trans('ai.config.prompts') }}</strong>
                </div>
                <div class="col-md-9 col-lg-10">
                    <p class="text-muted small mb-2">{{ trans('ai.config.prompts_help') }}</p>
                    @foreach ($prompts as $type => $prompt)
                        <details class="ai-prompt">
                            <summary>
                                {{ $prompt['label'] }}
                                @if ($prompt['custom'])
                                    <span class="badge bg-info text-dark ms-1">{{ trans('ai.config.prompt_custom') }}</span>
                                @endif
                            </summary>
                            <label for="prompt-{{ $type }}" class="small">{{ trans('ai.config.prompt_instructions') }}</label>
                            <textarea class="form-control" name="prompts[{{ $type }}]" id="prompt-{{ $type }}" rows="14" maxlength="{{ $prompt_max_length }}" spellcheck="false">{{ old('prompts.' . $type, $prompt['value']) }}</textarea>
                            <small class="text-muted">{{ trans('ai.config.prompt_length', ['max' => $prompt_max_length]) }}</small>
                            <div class="small mt-2"><strong>{{ trans('ai.config.prompt_contract') }}</strong> — {{ trans('ai.config.prompt_contract_help') }}</div>
                            <pre class="ai-contract" aria-readonly="true">{{ $prompt['contract'] }}</pre>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" formaction="/config/ai/prompt/{{ $type }}/reset" formnovalidate {{ $prompt['custom'] ? '' : 'disabled' }}>
                                <span class="bi bi-arrow-counterclockwise"></span> {{ trans('ai.config.prompt_reset') }}
                            </button>
                        </details>
                    @endforeach
                </div>
            </div>
            </fieldset>

            <div class="row mb-3">
                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <span class="bi bi-floppy"></span> {{ trans('ai.config.save') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="ai-test" {{ $enabled ? '' : 'disabled' }}>
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

@php
    $testLabels = [
        'running' => trans('ai.config.test_running'),
        'ok' => trans('ai.config.test_ok'),
        'failed' => trans('ai.config.test_failed'),
        'status' => trans('ai.config.test_status'),
        'latency' => trans('ai.config.test_latency'),
        'model' => trans('ai.config.test_model'),
        'error' => trans('ai.errors.generic'),
        'models_loading' => trans('ai.config.models_loading'),
        'models_found' => trans('ai.config.models_found'),
    ];
@endphp
<script @nonce>
// URL, key and model typed in the form: the buttons test them before they are saved
function aiFormSettings() {
    return {
        base_url: document.getElementById('base_url').value,
        api_key: document.getElementById('api_key').value,
        model: document.getElementById('model').value,
    };
}

(function () {
    // AI switch: enable or disable the settings without reloading the page
    const enabled = document.getElementById('enabled');
    enabled.addEventListener('change', function () {
        const off = !enabled.checked;
        document.getElementById('ai-settings').disabled = off;
        document.getElementById('ai-test').disabled = off;
    });

    // Presets only fill the URL field
    document.getElementById('ai-preset').addEventListener('change', function () {
        if (this.value) { document.getElementById('base_url').value = this.value; }
    });
})();

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
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(aiFormSettings()),
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

// Models offered by the provider, with the URL and key typed in the form
(function () {
    const button = document.getElementById('ai-load-models');
    const result = document.getElementById('ai-models-result');
    const list = document.getElementById('ai-models');
    const labels = @json($testLabels);

    button.addEventListener('click', function () {
        button.disabled = true;
        result.className = 'ms-2 small';
        result.textContent = labels.models_loading;

        fetch('/config/ai/models', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(aiFormSettings()),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                list.replaceChildren();
                (data.models || []).forEach(function (id) {
                    const option = document.createElement('option');
                    option.value = id;
                    list.appendChild(option);
                });
                result.className = 'ms-2 small ' + (data.ok ? 'text-success' : 'text-danger');
                result.textContent = data.ok ? labels.models_found.replace(':count', data.models.length) : data.error;
            })
            .catch(function () {
                result.className = 'ms-2 small text-danger';
                result.textContent = labels.error;
            })
            .finally(function () { button.disabled = false; });
    });
})();
</script>

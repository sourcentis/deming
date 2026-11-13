{{-- "AI" tab of the settings page --}}
<style>
    .ai-config details { border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 4px; padding: .5rem .75rem; margin-bottom: .5rem; }
    .ai-config details > summary { cursor: pointer; font-weight: 600; }
    .ai-config details[open] > summary { margin-bottom: .5rem; }
    .ai-config .ai-privacy ul { margin-bottom: .25rem; }
    .ai-config details.ai-purge > summary { list-style: none; display: inline-block; }
    .ai-config details.ai-purge > summary::-webkit-details-marker { display: none; }
    .ai-config details.ai-purge { border: 0; padding: 0; }
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

            @if ($enabled)
            <div class="row mb-2">
                <div class="col-md-3 col-lg-2">
                    <strong>{{ trans('ai.config.tasks') }}</strong>
                </div>
                <div class="col-md-9 col-lg-6" id="ai-tasks">
                    @if ($tasks === null)
                        <span class="text-muted"><i class="bi bi-exclamation-triangle"></i> {{ trans('ai.config.tasks_unavailable') }}</span>
                    @elseif ($tasks === [])
                        <span class="text-muted">{{ trans('ai.config.tasks_none') }}</span>
                    @else
                        <table class="table table-sm mb-1">
                            <tbody>
                            @foreach ($tasks as $task)
                                <tr>
                                    <td>{{ $task['title'] }}</td>
                                    <td class="text-end"><code>{{ $task['version'] }}</code></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                    <small class="text-muted">{{ trans('ai.config.tasks_help') }}</small>
                </div>
            </div>
            @endif
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
        'tasks' => trans('ai.config.test_tasks'),
        'error' => trans('ai.errors.generic'),
    ];
@endphp
<script @nonce>
(function () {
    // AI switch: enable or disable the settings without reloading the page
    const enabled = document.getElementById('enabled');
    enabled.addEventListener('change', function () {
        const off = !enabled.checked;
        document.getElementById('ai-settings').disabled = off;
        document.getElementById('ai-test').disabled = off;
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
            },
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                const parts = [data.ok ? labels.ok : labels.failed];
                if (data.status) { parts.push(labels.status + ' ' + data.status); }
                if (data.latency_ms !== undefined) { parts.push(labels.latency + ' ' + data.latency_ms + ' ms'); }
                if (data.tasks_found !== null && data.tasks_found !== undefined) {
                    parts.push(labels.tasks.replace(':found', data.tasks_found).replace(':total', data.tasks_expected));
                }
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

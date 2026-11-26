<?php

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Models\User;
use App\Services\Ai\AiPrompt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function aiConfigPayload(array $overrides = []): array
{
    return array_replace([
        'enabled' => '1',
        'base_url' => 'https://agent.example.com/v1',
        'api_key' => '',
        'model' => 'test-model',
        'temperature' => '0.2',
        'max_tokens' => '8000',
        'timeout' => '120',
        'structured_output' => 'json_object',
        'output_language' => 'auto',
    ], $overrides);
}

test('guest is redirected to login', function () {
    $this->get('/config/parameters?tab=ai')->assertRedirect('/login');
});

test('a forged request by a guest changes nothing', function (string $uri) {
    $before = DB::table('configuration')->orderBy('name')->get()->toArray();
    Http::fake();

    $this->post($uri, aiConfigPayload(['base_url' => 'https://evil.example.com/v1', 'prompts' => ['risk' => 'Forged']]))
        ->assertRedirect('/login');

    expect(DB::table('configuration')->orderBy('name')->get()->toArray())->toEqual($before);
    Http::assertNothingSent();
})->with(['/config/ai/save', '/config/ai/test', '/config/ai/models', '/config/ai/purge', '/config/ai/prompt/risk/reset']);

test('the page shows the provider settings, the four prompts and their locked contract', function () {
    aiEnable();
    Http::fake();

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertSee('name="model"', false)
        ->assertSee('value="test-model"', false)
        ->assertSee('name="prompts[control]"', false)
        ->assertSee('name="prompts[measure]"', false)
        ->assertSee('name="prompts[action]"', false)
        ->assertSee('name="prompts[risk]"', false)
        ->assertSee(AiPrompt::default('risk'))
        ->assertSee(AiPrompt::contract('risk'))
        ->assertSee('formaction="/config/ai/prompt/risk/reset"', false)
        ->assertSee('https://api.openai.com/v1', false)
        ->assertSee('http://localhost:11434/v1', false);
    // Displaying the page never calls the provider
    Http::assertNothingSent();
});

test('a customised prompt is shown escaped and flagged', function () {
    Configuration::set('ai.prompt.measure', '<script>alert(1)</script> {{ $x }}');

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; {{ $x }}', false)
        ->assertSee(trans('ai.config.prompt_custom'));
});

test('the page shows the data protection box and the documentation link', function () {
    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertSee(trans('ai.config.privacy_title'))
        ->assertSee('ai/#privacy', false);
});

test('non admins get 403 on every action', function (string $role) {
    $user = User::factory()->{$role}()->create();
    Http::fake();

    $this->actingAs($user)->get('/config/parameters?tab=ai')->assertForbidden();
    $this->actingAs($user)->post('/config/ai/save', aiConfigPayload())->assertForbidden();
    $this->actingAs($user)->post('/config/ai/test')->assertForbidden();
    $this->actingAs($user)->post('/config/ai/models')->assertForbidden();
    $this->actingAs($user)->post('/config/ai/purge')->assertForbidden();
    $this->actingAs($user)->post('/config/ai/prompt/risk/reset')->assertForbidden();
    Http::assertNothingSent();
})->with(['user', 'auditor', 'auditee', 'apiUser']);

test('a forged save by a non admin leaves the configuration unchanged', function (string $role) {
    Configuration::set('ai.prompt.risk', 'Admin prompt.');
    $before = DB::table('configuration')->orderBy('name')->get()->toArray();

    $this->actingAs(User::factory()->{$role}()->create())
        ->post('/config/ai/save', aiConfigPayload([
            'api_key' => 'sk-forged', 'base_url' => 'https://evil.example.com/v1', 'model' => 'evil',
            'prompts' => ['risk' => 'Forged prompt.'],
        ]))
        ->assertForbidden();
    $this->actingAs(User::factory()->{$role}()->create())->post('/config/ai/prompt/risk/reset')->assertForbidden();

    expect(DB::table('configuration')->orderBy('name')->get()->toArray())->toEqual($before);
})->with(['user', 'auditor', 'auditee', 'apiUser']);

test('admin saves the configuration without duplicates', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['base_url' => 'https://a.example.com/v1']))->assertRedirect('/config/parameters?tab=ai');
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload([
        'base_url' => 'https://b.example.com/v1/', 'model' => ' gpt-4o-mini ', 'temperature' => '0.5', 'max_tokens' => '4000',
        'timeout' => '60', 'structured_output' => 'json_schema', 'output_language' => 'de',
    ]))->assertRedirect('/config/parameters?tab=ai')->assertSessionHasNoErrors();

    Configuration::clearCache();
    expect(Configuration::bool('ai.enabled'))->toBeTrue()
        ->and(Configuration::get('ai.base_url'))->toBe('https://b.example.com/v1')
        ->and(Configuration::get('ai.model'))->toBe('gpt-4o-mini')
        ->and(Configuration::get('ai.temperature'))->toBe('0.5')
        ->and(Configuration::get('ai.max_tokens'))->toBe('4000')
        ->and(Configuration::get('ai.timeout'))->toBe('60')
        ->and(Configuration::get('ai.structured_output'))->toBe('json_schema')
        ->and(Configuration::get('ai.output_language'))->toBe('de')
        ->and(DB::table('configuration')->where('name', 'ai.base_url')->count())->toBe(1)
        ->and(DB::table('configuration')->where('name', 'ai.enabled')->count())->toBe(1);
});

test('invalid parameters are refused', function (string $field, string $value) {
    $this->actingAs($this->admin)->from('/config/parameters?tab=ai')
        ->post('/config/ai/save', aiConfigPayload([$field => $value]))
        ->assertSessionHasErrors($field);

    expect(DB::table('configuration')->where('name', 'ai.' . $field)->exists())->toBeFalse();
})->with([
    ['temperature', '3'],
    ['max_tokens', '0'],
    ['timeout', '9999'],
    ['structured_output', 'xml'],
    ['output_language', 'it'],
    ['model', ''],
]);

test('fields of a disabled AI are not stored even when forged', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', ['enabled' => '0', 'base_url' => 'ftp://evil', 'model' => 'evil'])
        ->assertSessionHasNoErrors();

    expect(DB::table('configuration')->whereIn('name', ['ai.base_url', 'ai.model'])->exists())->toBeFalse();
});

// -----------------------------------------------------------------------------
// Prompts
// -----------------------------------------------------------------------------

test('admin edits a prompt, the others stay on their default', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['prompts' => [
        'control' => AiPrompt::default('control'),
        'measure' => "  My control prompt.\r\nSecond line.  ",
        'action' => '',
    ]]))->assertSessionHasNoErrors();

    Configuration::clearCache();
    expect(Configuration::get('ai.prompt.measure'))->toBe("My control prompt.\nSecond line.")
        ->and(AiPrompt::instructions('measure'))->toBe("My control prompt.\nSecond line.")
        ->and(DB::table('configuration')->whereIn('name', ['ai.prompt.control', 'ai.prompt.action', 'ai.prompt.risk'])->exists())->toBeFalse()
        ->and(AiPrompt::instructions('control'))->toBe(AiPrompt::default('control'));
});

test('a prompt equal to the default or emptied goes back to the default', function (string $value) {
    Configuration::set('ai.prompt.risk', 'Custom.');

    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['prompts' => ['risk' => $value]]));

    Configuration::clearCache();
    expect(Configuration::get('ai.prompt.risk'))->toBeNull()
        ->and(AiPrompt::instructions('risk'))->toBe(AiPrompt::default('risk'));
})->with([
    'default' => [fn () => str_replace("\n", "\r\n", AiPrompt::default('risk')) . "\n"],
    'empty' => [''],
]);

test('resetting a prompt sets it back to null and the default prompt is used', function () {
    aiEnable();
    Configuration::set('ai.prompt.action', 'Custom action prompt.');

    $this->actingAs($this->admin)->post('/config/ai/prompt/action/reset', aiConfigPayload(['prompts' => ['action' => 'Ignored']]))
        ->assertRedirect('/config/parameters?tab=ai');

    Configuration::clearCache();
    expect(Configuration::get('ai.prompt.action'))->toBeNull()
        ->and(AiPrompt::custom('action'))->toBeNull()
        ->and(AiPrompt::system('action', 'en'))->toStartWith(AiPrompt::default('action'));

    aiFakeGateway();
    $object = aiCreateSubject('action');
    $this->actingAs($this->admin)->postJson("/ai/action/{$object->id}")->assertOk();
    Http::assertSent(fn (Request $r) => str_starts_with($r['messages'][0]['content'], AiPrompt::default('action')));
});

test('resetting an unknown type is a 404', function () {
    $this->actingAs($this->admin)->post('/config/ai/prompt/other/reset')->assertNotFound();
});

test('a too long prompt is refused', function () {
    Configuration::set('ai.prompt.control', 'Kept.');

    $this->actingAs($this->admin)->from('/config/parameters?tab=ai')
        ->post('/config/ai/save', aiConfigPayload(['prompts' => ['control' => str_repeat('a', AiPrompt::MAX_LENGTH + 1)]]))
        ->assertSessionHasErrors('prompts.control');

    Configuration::clearCache();
    expect(Configuration::get('ai.prompt.control'))->toBe('Kept.');
});

test('a prompt with Blade or PHP syntax is stored and sent literally', function () {
    aiEnable();
    $prompt = "Use {{ config('app.key') }} and {!! phpinfo() !!}\n@php echo 'pwned'; @endphp @include('layouts.app')";

    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['prompts' => ['action' => $prompt]]))
        ->assertSessionHasNoErrors();

    Configuration::clearCache();
    expect(Configuration::get('ai.prompt.action'))->toBe($prompt);

    aiFakeGateway();
    $object = aiCreateSubject('action');
    $this->actingAs($this->admin)->postJson("/ai/action/{$object->id}")->assertOk();
    Http::assertSent(fn (Request $r) => str_starts_with($r['messages'][0]['content'], $prompt . "\n\n# Input data")
        && ! str_contains($r->body(), (string) config('app.key')));
});

test('the audit log keeps who changed a prompt, not its content', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['prompts' => ['risk' => 'Confidential instructions.']]));

    $log = AuditLog::where('subject_type', Configuration::class)->get()
        ->first(fn (AuditLog $log) => ($log->properties['name'] ?? null) === 'ai.prompt.risk');
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->admin->id)
        ->and($log->properties['value'])->toStartWith('sha256:')
        ->and($log->toJson())->not->toContain('Confidential instructions');
});

test('the API key is encrypted, never shown, kept when empty and deletable', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['api_key' => 'sk-very-secret']));

    $raw = DB::table('configuration')->where('name', 'ai.api_key')->value('value');
    expect($raw)->not->toBe('sk-very-secret')
        ->and(Configuration::get('ai.api_key'))->toBe('sk-very-secret');

    aiFakeGateway();
    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertDontSee('sk-very-secret')
        ->assertSee(trans('ai.config.api_key_saved'));

    // Empty field keeps the key
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['api_key' => '']));
    Configuration::clearCache();
    expect(Configuration::get('ai.api_key'))->toBe('sk-very-secret');

    // Checkbox deletes it
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['delete_api_key' => '1']));
    Configuration::clearCache();
    expect(Configuration::get('ai.api_key'))->toBeNull();
});

test('the audit log masks the API key', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['api_key' => 'sk-very-secret']));

    $logs = AuditLog::where('subject_type', Configuration::class)->get();
    expect($logs->toJson())->toContain('***')->not->toContain('sk-very-secret');
});

test('a non https URL is refused, except a local one', function (string $url, bool $accepted) {
    $response = $this->actingAs($this->admin)->from('/config/parameters?tab=ai')
        ->post('/config/ai/save', aiConfigPayload(['base_url' => $url]));

    if ($accepted) {
        $response->assertSessionHasNoErrors();
        expect(Configuration::get('ai.base_url'))->toBe($url);
    } else {
        $response->assertSessionHasErrors('base_url');
        expect(Configuration::get('ai.base_url'))->toBeNull();
    }
})->with([
    ['http://agent.example.com/v1', false],
    ['ftp://agent.example.com/v1', false],
    ['not an url', false],
    ['https://agent.example.com/v1', true],
    ['http://localhost:11434/v1', true],
    ['http://127.0.0.1:8000/v1', true],
    ['http://192.168.1.20:11434/v1', true],
    ['http://ollama:11434/v1', true],
    ['http://169.254.169.254/v1', false],
]);

test('the connection test sends a minimal chat completion and shows the model and the latency, never the key', function () {
    aiConfigure();
    Configuration::set('ai.api_key', 'sk-very-secret');
    aiFakeGateway();

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => true, 'status' => 200, 'model' => 'test-model', 'error' => null]);
    expect($response->json('latency_ms'))->toBeInt()
        ->and($response->getContent())->not->toContain('sk-very-secret');
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'https://agent.example.com/v1/chat/completions'
        && $r['model'] === 'test-model'
        && $r['max_tokens'] === 16
        && ! isset($r['response_format'])
        && $r->hasHeader('Authorization', 'Bearer sk-very-secret'));
});

test('the connection test accepts an answer cut by its small token limit', function () {
    aiConfigure();
    aiFakeGateway(aiAgentResponse('', ['choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => ''], 'finish_reason' => 'length']]]));

    $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk()->assertJson(['ok' => true, 'status' => 200]);
});

test('the connection test reports failures without the response body', function (int $status, string $reason) {
    aiConfigure();
    Configuration::set('ai.api_key', 'sk-very-secret');
    aiFakeGateway(aiGatewayError($status, 'internal'));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => false, 'status' => $status, 'model' => null, 'error' => trans("ai.errors.{$reason}")]);
    expect($response->getContent())->not->toContain('secret provider body')
        ->not->toContain('sk-very-secret');
})->with([
    [401, 'invalid_key'],
    [402, 'quota_exceeded'],
    [404, 'incompatible'],
    [500, 'provider_error'],
]);

test('the connection test handles an unreachable provider', function () {
    aiConfigure();
    Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to host'));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => false, 'status' => null, 'error' => trans('ai.errors.unreachable')]);
    expect($response->getContent())->not->toContain('cURL');
});

test('the connection test needs an URL and a model', function (string $key) {
    aiConfigure();
    Configuration::forget($key);
    Http::fake();

    $this->actingAs($this->admin)->postJson('/config/ai/test')
        ->assertOk()
        ->assertJson(['ok' => false, 'error' => trans('ai.errors.not_configured')]);
    Http::assertNothingSent();
})->with(['ai.base_url', 'ai.model']);

test('the model list comes from GET /models', function () {
    aiConfigure();
    Configuration::set('ai.api_key', 'sk-very-secret');
    aiFakeGateway(null, aiModelsResponse(['mistral-small', 'gpt-4o', 'mistral-small']));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/models')->assertOk();

    $response->assertExactJson(['ok' => true, 'models' => ['gpt-4o', 'mistral-small'], 'error' => null]);
    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://agent.example.com/v1/models'
        && $r->hasHeader('Authorization', 'Bearer sk-very-secret'));
    expect($response->getContent())->not->toContain('sk-very-secret');
});

test('an unavailable model list is reported without the response body', function (Closure $models) {
    aiConfigure();
    aiFakeGateway(null, $models);

    $response = $this->actingAs($this->admin)->postJson('/config/ai/models')->assertOk();

    $response->assertJson(['ok' => false, 'models' => []]);
    expect($response->json('error'))->toContain(trans('ai.config.models_unavailable'))
        ->and($response->getContent())->not->toContain('secret');
})->with([
    'not offered' => [fn () => aiGatewayError(404, 'not_found')],
    'invalid' => [fn () => Http::response(['data' => 'secret'])],
    'unreachable' => [fn () => fn () => throw new ConnectionException('cURL error 7: Failed to connect')],
]);

test('the buttons use the URL, key and model typed in the form, before saving', function () {
    aiConfigure();
    Configuration::set('ai.api_key', 'sk-saved');
    aiFakeGateway();
    $form = ['base_url' => 'http://127.0.0.1:8080/v1/', 'api_key' => 'sk-typed', 'model' => 'typed-model'];

    $this->actingAs($this->admin)->postJson('/config/ai/models', $form)->assertJson(['ok' => true]);
    $this->actingAs($this->admin)->postJson('/config/ai/test', $form)->assertJson(['ok' => true]);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'http://127.0.0.1:8080/v1/models'
        && $r->hasHeader('Authorization', 'Bearer sk-typed'));
    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'http://127.0.0.1:8080/v1/chat/completions'
        && $r['model'] === 'typed-model'
        && $r->hasHeader('Authorization', 'Bearer sk-typed'));
    // Nothing is saved
    Configuration::clearCache();
    expect(Configuration::get('ai.base_url'))->toBe('https://agent.example.com/v1')
        ->and(Configuration::get('ai.model'))->toBe('test-model')
        ->and(Configuration::get('ai.api_key'))->toBe('sk-saved');
});

test('the saved key is sent to the saved URL only', function () {
    aiConfigure();
    Configuration::set('ai.api_key', 'sk-saved');
    aiFakeGateway();

    $this->actingAs($this->admin)->postJson('/config/ai/models', ['base_url' => 'https://agent.example.com/v1/', 'api_key' => ''])->assertJson(['ok' => true]);
    $this->actingAs($this->admin)->postJson('/config/ai/models', ['base_url' => 'https://other.example.com/v1', 'api_key' => ''])->assertJson(['ok' => true]);

    Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://agent.example.com/')
        && $r->hasHeader('Authorization', 'Bearer sk-saved'));
    Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://other.example.com/')
        && ! $r->hasHeader('Authorization'));
    Http::assertNotSent(fn (Request $r) => str_starts_with($r->url(), 'https://other.example.com/')
        && str_contains(implode(' ', $r->header('Authorization')), 'sk-saved'));
});

test('a typed public http URL is refused by the buttons without any call', function () {
    Http::fake();

    $response = $this->actingAs($this->admin)->postJson('/config/ai/models', ['base_url' => 'http://evil.example.com/v1'])->assertOk();

    expect($response->json('error'))->toContain(trans('ai.errors.insecure_url'));
    Http::assertNothingSent();
});

test('a refused key is reported by the model list', function () {
    aiFakeGateway(null, aiGatewayError(401, 'invalid_api_key'));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/models', ['base_url' => 'http://127.0.0.1:8080/v1'])->assertOk();

    expect($response->json('error'))->toBe(trans('ai.errors.invalid_key') . ' ' . trans('ai.config.models_unavailable'))
        ->and($response->getContent())->not->toContain('secret');
});

test('admin purges every analysis with an audit entry without content', function () {
    $tables = ['controls', 'measures', 'actions', 'risks'];
    foreach ($tables as $type => $table) {
        $object = aiCreateSubject(['control', 'measure', 'action', 'risk'][$type]);
        DB::table($table)->where('id', $object->id)->update([
            'ai_analysis' => '{"summary":"secret analysis"}', 'ai_analysis_at' => now(),
            'ai_analysis_model' => 'm', 'ai_analysis_hash' => str_repeat('a', 64), 'ai_analysis_version' => 'v1',
        ]);
    }

    $this->actingAs($this->admin)->post('/config/ai/purge')->assertRedirect('/config/parameters?tab=ai');

    foreach ($tables as $table) {
        expect(DB::table($table)->whereNotNull('ai_analysis')->orWhereNotNull('ai_analysis_hash')->orWhereNotNull('ai_analysis_version')->count())->toBe(0);
    }
    $log = AuditLog::where('description', 'purge AI analyses')->sole();
    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->toJson())->not->toContain('secret analysis');
});

test('a non admin cannot purge', function () {
    $object = aiCreateSubject('risk');
    DB::table('risks')->where('id', $object->id)->update(['ai_analysis' => '{}']);

    $this->actingAs(User::factory()->user()->create())->post('/config/ai/purge')->assertForbidden();

    expect(DB::table('risks')->where('id', $object->id)->value('ai_analysis'))->toBe('{}');
});

test('the former URL opens the AI tab of the settings page', function () {
    $this->actingAs($this->admin)->get('/config/ai')->assertRedirect('/config/parameters?tab=ai');
});

test('the settings fields are disabled while AI is off', function (bool $enabled) {
    Configuration::set('ai.enabled', $enabled ? '1' : '0');
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    aiFakeGateway();

    $html = $this->actingAs($this->admin)->get('/config/parameters?tab=ai')->assertOk()->getContent();

    $disabled = fn (string $tag) => (bool) preg_match('#' . $tag . '[^>]*\sdisabled[\s>]#', $html);
    expect($disabled('<fieldset id="ai-settings"'))->toBe(! $enabled)
        ->and($disabled('<button type="button" class="btn btn-primary" id="ai-test"'))->toBe(! $enabled)
        // The switch itself and the purge stay usable
        ->and($disabled('<input class="form-check-input" type="checkbox" role="switch" name="enabled"'))->toBeFalse();
})->with([true, false]);

test('saving with AI off keeps the settings, whose fields are not submitted', function () {
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    Configuration::set('ai.api_key', 'sk-kept');
    Configuration::set('ai.enabled', '1');

    // Disabled fields are absent from the request: only the switch (unchecked) is sent
    $this->actingAs($this->admin)->post('/config/ai/save', [])
        ->assertRedirect('/config/parameters?tab=ai')
        ->assertSessionHasNoErrors();

    Configuration::clearCache();
    expect(Configuration::bool('ai.enabled'))->toBeFalse()
        ->and(Configuration::get('ai.base_url'))->toBe('https://agent.example.com/v1')
        ->and(Configuration::get('ai.api_key'))->toBe('sk-kept');
});

test('turning AI on still requires the URL and the model', function () {
    $this->actingAs($this->admin)->from('/config/parameters?tab=ai')
        ->post('/config/ai/save', ['enabled' => '1'])
        ->assertSessionHasErrors(['base_url', 'model']);

    expect(Configuration::bool('ai.enabled'))->toBeFalse();
});

test('the AI tab tells which providers can be used', function (string $language) {
    $admin = User::factory()->admin()->create(['language' => $language]);

    $this->actingAs($admin)->get('/config/parameters?tab=ai')
        ->assertSeeText(trans('ai.config.access_title', [], $language))
        ->assertSeeText(trans('ai.config.access_key', [], $language))
        ->assertSeeText(trans('ai.config.access_own_engine', [], $language));
})->with(['fr', 'en', 'de']);

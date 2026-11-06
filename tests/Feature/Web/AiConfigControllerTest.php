<?php

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Models\User;
use App\Services\Ai\AiGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
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
    ], $overrides);
}

test('guest is redirected to login', function () {
    $this->get('/config/parameters?tab=ai')->assertRedirect('/login');
});

test('the page shows neither prompts, nor model, nor schema', function () {
    aiEnable();
    aiFakeGateway();

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertDontSee('prompts[', false)
        ->assertDontSee('name="model"', false)
        ->assertDontSee('name="restore"', false)
        ->assertDontSee('points_de_vue')
        ->assertDontSee('{{langue}}', false);
});

test('the page lists the Deming tasks of the gateway, read-only', function () {
    aiEnable();
    $this->admin->update(['language' => 'de']);
    aiFakeGateway(null, aiTasksResponse('e04f8bdad356'));

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertSeeInOrder(['Analyse control (de)', 'e04f8bdad356', 'Analyse measure (de)', 'Analyse action (de)', 'Analyse risk (de)'])
        ->assertDontSee('Autre application')
        ->assertDontSee(trans('ai.config.tasks_unavailable', [], 'de'));
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->url() === 'https://agent.example.com/v1/tasks');
});

test('the page says when the task list is unavailable', function (Closure $tasks) {
    aiEnable();
    aiFakeGateway(null, $tasks);

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertSee(trans('ai.config.tasks_unavailable'))
        ->assertDontSee('secret');
})->with([
    'unreachable' => [fn () => fn () => throw new ConnectionException('cURL error 7: Failed to connect')],
    'invalid key' => [fn () => aiGatewayError(401, 'invalid_api_key')],
]);

test('the gateway is not called while AI is off', function () {
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    Http::fake();

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertOk()
        ->assertDontSee(trans('ai.config.tasks'));
    Http::assertNothingSent();
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
    $this->actingAs($user)->post('/config/ai/purge')->assertForbidden();
    Http::assertNothingSent();
})->with(['user', 'auditor', 'auditee', 'apiUser']);

test('a forged save by a non admin leaves the configuration unchanged', function () {
    $before = DB::table('configuration')->orderBy('name')->get()->toArray();

    $this->actingAs(User::factory()->user()->create())
        ->post('/config/ai/save', aiConfigPayload(['api_key' => 'sk-forged', 'base_url' => 'https://evil.example.com/v1']))
        ->assertForbidden();

    expect(DB::table('configuration')->orderBy('name')->get()->toArray())->toEqual($before);
});

test('admin saves the configuration without duplicates', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['base_url' => 'https://a.example.com/v1']))->assertRedirect('/config/parameters?tab=ai');
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['base_url' => 'https://b.example.com/v1/']))->assertRedirect('/config/parameters?tab=ai');

    expect(Configuration::bool('ai.enabled'))->toBeTrue()
        ->and(Configuration::get('ai.base_url'))->toBe('https://b.example.com/v1')
        ->and(DB::table('configuration')->where('name', 'ai.base_url')->count())->toBe(1)
        ->and(DB::table('configuration')->where('name', 'ai.enabled')->count())->toBe(1);
});

test('a model, prompts or a schema in the request are ignored', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload([
        'model' => 'evil', 'prompts' => ['risk' => 'Mon prompt'], 'schema' => '{"type":"string"}', 'restore' => 'risk',
    ]))->assertRedirect('/config/parameters?tab=ai');

    expect(DB::table('configuration')->where('name', 'like', 'ai.model')->exists())->toBeFalse()
        ->and(DB::table('configuration')->where('name', 'like', 'ai.prompt%')->exists())->toBeFalse()
        ->and(DB::table('configuration')->where('name', 'like', '%schema%')->exists())->toBeFalse();
});

test('saving the settings forgets the cached task catalog', function () {
    Cache::put(AiGateway::TASKS_CACHE_KEY, [['id' => 'deming/control', 'task_version' => 'old']], 3600);

    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload());

    expect(Cache::has(AiGateway::TASKS_CACHE_KEY))->toBeFalse();
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

test('a non https URL is refused, except localhost', function (string $url, bool $accepted) {
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
    ['http://localhost:8080/v1', true],
    ['http://127.0.0.1:8000/v1', true],
]);

test('the connection test calls GET /tasks and counts the Deming tasks, without the key', function () {
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    Configuration::set('ai.api_key', 'sk-very-secret');
    aiFakeGateway();

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => true, 'status' => 200, 'tasks_found' => 4, 'tasks_expected' => 4, 'error' => null]);
    expect($response->json('latency_ms'))->toBeInt()
        ->and($response->getContent())->not->toContain('sk-very-secret');
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://agent.example.com/v1/tasks'
        && $r->hasHeader('Authorization', 'Bearer sk-very-secret'));
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'chat/completions'));
});

test('the connection test reports missing Deming tasks', function () {
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    aiFakeGateway(null, aiTasksResponse('v1', ['control', 'risk']));

    $this->actingAs($this->admin)->postJson('/config/ai/test')
        ->assertOk()
        ->assertJson(['ok' => false, 'status' => 200, 'tasks_found' => 2, 'tasks_expected' => 4, 'error' => trans('ai.errors.incompatible')]);
});

test('the connection test reports failures without the response body', function (int $status, string $reason) {
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    aiFakeGateway(null, aiGatewayError($status, 'internal'));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => false, 'status' => $status, 'tasks_found' => null, 'error' => trans("ai.errors.{$reason}")]);
    expect($response->getContent())->not->toContain('secret gateway body');
})->with([
    [401, 'invalid_key'],
    [402, 'quota_exceeded'],
    [500, 'unreachable'],
]);

test('the connection test handles an unreachable agent', function () {
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to host'));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => false, 'status' => null, 'error' => trans('ai.errors.unreachable')]);
    expect($response->getContent())->not->toContain('cURL');
});

test('the connection test needs an agent URL', function () {
    Http::fake();

    $this->actingAs($this->admin)->postJson('/config/ai/test')
        ->assertOk()
        ->assertJson(['ok' => false, 'error' => trans('ai.errors.not_configured')]);
    Http::assertNothingSent();
});

test('admin purges every analysis with an audit entry without content', function () {
    $tables = ['controls', 'measures', 'actions', 'risks'];
    foreach ($tables as $type => $table) {
        $object = aiCreateSubject(['control', 'measure', 'action', 'risk'][$type]);
        DB::table($table)->where('id', $object->id)->update([
            'ai_analysis' => '{"synthese":"secret analysis"}', 'ai_analysis_at' => now(),
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

test('turning AI on still requires the agent URL', function () {
    $this->actingAs($this->admin)->from('/config/parameters?tab=ai')
        ->post('/config/ai/save', ['enabled' => '1'])
        ->assertSessionHasErrors('base_url');

    expect(Configuration::bool('ai.enabled'))->toBeFalse();
});

test('the AI tab tells how to get access or use your own engine', function (string $language) {
    $admin = User::factory()->admin()->create(['language' => $language]);

    $this->actingAs($admin)->get('/config/parameters?tab=ai')
        ->assertSeeText(trans('ai.config.access_title', [], $language))
        ->assertSeeText(trans('ai.config.access_key', [], $language))
        ->assertSeeText(trans('ai.config.access_own_engine', [], $language));
})->with(['fr', 'en', 'de']);

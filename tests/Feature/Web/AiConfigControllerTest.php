<?php

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Models\User;
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
        'base_url' => 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1',
        'model' => 'gpt-oss-120b',
        'api_key' => '',
    ], $overrides);
}

test('guest is redirected to login', function () {
    $this->get('/config/parameters?tab=ai')->assertRedirect('/login');
});

test('admin sees the configuration page with the four prompts', function () {
    $response = $this->actingAs($this->admin)->get('/config/parameters?tab=ai')->assertOk();

    foreach (['control', 'measure', 'action', 'risk'] as $type) {
        $response->assertSee("prompts[{$type}]", false);
    }
    $response->assertSee('&quot;points_de_vue&quot;', false);
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
        ->post('/config/ai/save', aiConfigPayload(['api_key' => 'sk-forged', 'model' => 'evil']))
        ->assertForbidden();

    expect(DB::table('configuration')->orderBy('name')->get()->toArray())->toEqual($before);
});

test('admin saves the configuration without duplicates', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['model' => 'llama3']))->assertRedirect('/config/parameters?tab=ai');
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['model' => 'mistral']))->assertRedirect('/config/parameters?tab=ai');

    expect(Configuration::bool('ai.enabled'))->toBeTrue()
        ->and(Configuration::get('ai.model'))->toBe('mistral')
        ->and(DB::table('configuration')->where('name', 'ai.model')->count())->toBe(1)
        ->and(DB::table('configuration')->where('name', 'ai.enabled')->count())->toBe(1);
});

test('the API key is encrypted, never shown, kept when empty and deletable', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['api_key' => 'sk-very-secret']));

    $raw = DB::table('configuration')->where('name', 'ai.api_key')->value('value');
    expect($raw)->not->toBe('sk-very-secret')
        ->and(Configuration::get('ai.api_key'))->toBe('sk-very-secret');

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
        expect(Configuration::get('ai.base_url'))->toBe(Configuration::DEFAULTS['ai.base_url']);
    }
})->with([
    ['http://agent.example.com/v1', false],
    ['ftp://agent.example.com/v1', false],
    ['not an url', false],
    ['https://agent.example.com/v1', true],
    ['http://localhost:11434/v1', true],
    ['http://127.0.0.1:8000/v1', true],
]);

test('the schema cannot be changed', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['schema' => '{"type":"string"}']));

    expect(DB::table('configuration')->where('name', 'like', '%schema%')->exists())->toBeFalse();
    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')->assertSee('&quot;synthese&quot;', false);
});

test('a prompt can be customized and restored', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['prompts' => ['risk' => 'Mon prompt {{langue}}']]));
    Configuration::clearCache();
    expect(Configuration::get('ai.prompt_risk'))->toBe('Mon prompt {{langue}}');

    $this->actingAs($this->admin)->post('/config/ai/save', ['restore' => 'risk'])->assertRedirect('/config/parameters?tab=ai');
    Configuration::clearCache();
    expect(Configuration::get('ai.prompt_risk'))->toBe(file_get_contents(resource_path('ai/prompt_risk.md')))
        ->and(DB::table('configuration')->where('name', 'ai.prompt_risk')->exists())->toBeFalse();
});

test('submitting an unchanged default prompt stores nothing', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload([
        'prompts' => ['control' => file_get_contents(resource_path('ai/prompt_control.md'))],
    ]));

    expect(DB::table('configuration')->where('name', 'ai.prompt_control')->exists())->toBeFalse();
});

test('the connection test returns status, latency and model without the key', function () {
    Configuration::set('ai.api_key', 'sk-very-secret');
    Http::fake(['*' => Http::response(['model' => 'gpt-oss-120b', 'choices' => [['message' => ['content' => 'OK']]]], 200)]);

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => true, 'status' => 200, 'model' => 'gpt-oss-120b']);
    expect($response->json('latency_ms'))->toBeInt()
        ->and($response->getContent())->not->toContain('sk-very-secret');
    Http::assertSent(fn (Request $r) => $r['messages'][0]['content'] === 'Réponds OK');
});

test('the connection test reports failures without the response body', function () {
    Http::fake(['*' => Http::response(['error' => 'internal secret detail'], 401)]);

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => false, 'status' => 401]);
    expect($response->getContent())->not->toContain('internal secret detail');
});

test('the connection test handles an unreachable agent', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to host'));

    $response = $this->actingAs($this->admin)->postJson('/config/ai/test')->assertOk();

    $response->assertJson(['ok' => false, 'status' => null]);
    expect($response->getContent())->not->toContain('cURL');
});

test('admin purges every analysis with an audit entry without content', function () {
    $tables = ['controls', 'measures', 'actions', 'risks'];
    foreach ($tables as $type => $table) {
        $object = aiCreateSubject(['control', 'measure', 'action', 'risk'][$type]);
        DB::table($table)->where('id', $object->id)->update([
            'ai_analysis' => '{"synthese":"secret analysis"}', 'ai_analysis_at' => now(),
            'ai_analysis_model' => 'm', 'ai_analysis_hash' => str_repeat('a', 64),
        ]);
    }

    $this->actingAs($this->admin)->post('/config/ai/purge')->assertRedirect('/config/parameters?tab=ai');

    foreach ($tables as $table) {
        expect(DB::table($table)->whereNotNull('ai_analysis')->orWhereNotNull('ai_analysis_hash')->count())->toBe(0);
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

test('the model field lists the OVHcloud text generation models and accepts another one', function () {
    $html = $this->actingAs($this->admin)->get('/config/parameters?tab=ai')->assertOk()->getContent();

    expect($html)->toContain('class="form-select select2 select2-free" name="model"');
    foreach (\App\Http\Controllers\AiConfigController::OVH_MODELS as $model) {
        expect($html)->toContain('<option value="' . $model . '"');
    }
    expect($html)->toContain('<option value="gpt-oss-120b" selected')
        // No speech, text-to-speech, embedding or image model
        ->not->toContain('value="whisper-large-v3"')
        ->not->toContain('value="nvr-tts-de-de"')
        ->not->toContain('value="bge-m3"')
        ->not->toContain('value="stable-diffusion-xl-base-v10"');
});

test('a model outside the list is saved and shown selected', function () {
    $this->actingAs($this->admin)->post('/config/ai/save', aiConfigPayload(['model' => 'llama3.1:8b']));

    $this->actingAs($this->admin)->get('/config/parameters?tab=ai')
        ->assertSee('<option value="llama3.1:8b" selected', false);
});

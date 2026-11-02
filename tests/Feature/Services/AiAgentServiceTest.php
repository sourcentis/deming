<?php

use App\Models\Action;
use App\Models\Configuration;
use App\Models\Control;
use App\Models\Measure;
use App\Models\Risk;
use App\Models\User;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiResponseGuard;
use App\Services\Ai\AiSubjects;
use App\Services\Ai\PersonalDataRedactor;
use App\Services\AiAgentService;
use Database\Seeders\RiskScoringConfigSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const AI_TYPES = ['control', 'measure', 'action', 'risk'];

beforeEach(function () {
    $this->seed(RiskScoringConfigSeeder::class);
    $this->admin = User::factory()->admin()->create(['language' => 'fr']);
    $this->actingAs($this->admin);
    Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    $this->service = app(AiAgentService::class);
});

// Body of the analysis request sent to the gateway
function aiSentRun(): Request
{
    $sent = Http::recorded(fn (Request $r) => $r->method() === 'POST')->first();
    expect($sent)->not->toBeNull();

    return $sent[0];
}

// -----------------------------------------------------------------------------
// Request sent to the gateway
// -----------------------------------------------------------------------------

test('the request only carries the language, the date and the masked context', function (string $type) {
    aiFakeGateway();
    User::factory()->user()->create(['name' => 'Bertrand Lemaire']);
    $object = aiCreateSubject($type);
    DB::table($object->getTable())->where('id', $object->id)->update(['name' => 'Voir jean.dupont@hopital.lu et Bertrand Lemaire']);
    $object = $object->fresh();

    $result = $this->service->analyze($type, $object);

    expect($result['cached'])->toBeFalse()
        ->and($result['data']['statut_evalue'])->toBe('partiellement_conforme')
        ->and($result['model'])->toBe('sourcentis-standard');

    $request = aiSentRun();
    $body = json_decode($request->body(), true);
    expect($request->method())->toBe('POST')
        ->and($request->url())->toBe("https://agent.example.com/v1/deming/{$type}")
        ->and(array_keys($body))->toEqualCanonicalizing(['language', 'date', 'context'])
        ->and($body['language'])->toBe('fr')
        ->and($body['date'])->toBe(now()->toDateString())
        ->and($body['context'])->toBe(AiSubjects::context($type, $object))
        ->and($request->body())->not->toContain('jean.dupont')
        ->not->toContain('Lemaire')
        ->toContain('[email]');
    foreach (['messages', 'model', 'response_format', 'schema', 'prompt', 'max_tokens', 'temperature', 'reasoning_effort'] as $key) {
        expect($body)->not->toHaveKey($key);
    }
})->with(AI_TYPES);

test('the language is the user language, English by default', function (?string $language, string $expected) {
    aiFakeGateway();
    $this->admin->language = $language;
    $this->admin->save();
    app()->setLocale('xx');

    $this->service->analyze('control', aiCreateSubject('control'));

    expect(aiSentRun()['language'])->toBe($expected);
})->with([
    'fr' => ['fr', 'fr'],
    'de' => ['de', 'de'],
    'en' => ['en', 'en'],
    'unsupported' => ['it', 'en'],
    'none' => [null, 'en'],
]);

test('no Authorization header is sent without an API key', function () {
    aiFakeGateway();

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('Authorization'));
});

test('the API key is sent as a bearer token', function () {
    aiFakeGateway();
    Configuration::set('ai.api_key', 'sk-test');

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer sk-test'));
});

test('the context contains no user name nor e-mail', function () {
    aiFakeGateway();
    $owner = User::factory()->user()->create(['name' => 'Gertrude Vanderbilt', 'login' => 'gvanderbilt', 'email' => 'gertrude@example.org']);

    $control = Control::factory()->create();
    $measure = Measure::factory()->create();
    $measure->controls()->sync([$control->id]);
    $measure->users()->sync([$owner->id]);

    $action = Action::factory()->create();
    $action->owners()->sync([$owner->id]);

    $risk = Risk::factory()->create(['owner_id' => $owner->id]);

    $this->service->analyze('control', $control);
    $this->service->analyze('measure', $measure);
    $this->service->analyze('action', $action);
    $this->service->analyze('risk', $risk);

    expect(aiRunCount())->toBe(4);
    Http::assertNotSent(function (Request $r) {
        $body = $r->body();

        return str_contains($body, 'Gertrude')
            || str_contains($body, 'Vanderbilt')
            || str_contains($body, 'gvanderbilt')
            || str_contains($body, 'gertrude@example.org')
            || str_contains($body, $this->admin->email);
    });
});

test('action context counts owners and risk context flags the owner', function () {
    $owner = User::factory()->user()->create();
    $action = Action::factory()->create();
    $action->owners()->sync([$owner->id, $this->admin->id]);
    $risk = Risk::factory()->create(['owner_id' => $owner->id]);

    expect(AiSubjects::context('action', $action)['nombre_responsables'])->toBe(2);
    expect(AiSubjects::context('risk', $risk)['a_un_proprietaire'])->toBeTrue();
});

test('documents are sent by name only', function () {
    $measure = aiCreateSubject('measure');
    DB::table('documents')->insert([
        'measure_id' => $measure->id, 'filename' => 'preuve.pdf', 'mimetype' => 'application/pdf',
        'size' => 10, 'hash' => str_repeat('0', 64),
    ]);

    expect(AiSubjects::context('measure', $measure)['documents'])
        ->toBe(['nombre' => 1, 'fichiers' => ['preuve.pdf']]);
});

test('only metadata is logged, with the task version and the model', function () {
    aiFakeGateway(aiAgentResponse(null, ['task_version' => 'abc123']));
    Log::spy();

    $this->service->analyze('control', aiCreateSubject('control'));

    Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context) => $message === 'AI analysis'
        && $context['task_version'] === 'abc123'
        && $context['model'] === 'sourcentis-standard'
        && $context['tokens'] === 1234
        && ! str_contains(json_encode($context), 'Synthèse'))->once();
});

// -----------------------------------------------------------------------------
// Storage and cache
// -----------------------------------------------------------------------------

test('the analysis is stored without touching updated_at nor the audit log', function (string $type) {
    aiFakeGateway(aiAgentResponse(null, ['task_version' => 'e04f8bdad356']));
    $object = aiCreateSubject($type);
    $table = $object->getTable();
    DB::table($table)->where('id', $object->id)->update(['updated_at' => '2020-01-01 00:00:00']);
    $logs = DB::table('audit_logs')->count();

    $this->service->analyze($type, $object);

    $row = DB::table($table)->where('id', $object->id)->first();
    expect($row->updated_at)->toBe('2020-01-01 00:00:00')
        ->and(json_decode($row->ai_analysis, true))->toBe(aiValidAnalysis())
        ->and($row->ai_analysis_model)->toBe('sourcentis-standard')
        ->and($row->ai_analysis_version)->toBe('e04f8bdad356')
        ->and($row->ai_analysis_hash)->toHaveLength(64)
        ->and($row->ai_analysis_at)->not->toBeNull()
        ->and(DB::table('audit_logs')->count())->toBe($logs);
})->with(AI_TYPES);

test('the action progress history is unchanged by an analysis', function () {
    aiFakeGateway();
    $action = Action::factory()->create(['progress' => 10]);
    $action->update(['progress' => 40]);
    $before = $action->history();

    $this->service->analyze('action', $action);

    expect(Action::find($action->id)->history())->toBe($before);
});

test('a second call on an unchanged object is served from the cache', function (string $type) {
    aiFakeGateway();
    $object = aiCreateSubject($type);

    $first = $this->service->analyze($type, $object);
    $second = $this->service->analyze($type, $object->fresh());

    expect($second['cached'])->toBeTrue()
        ->and($second['data'])->toBe($first['data'])
        ->and($second['model'])->toBe('sourcentis-standard')
        ->and(aiRunCount())->toBe(1);
})->with(AI_TYPES);

test('force runs a new analysis even when nothing changed', function (string $type) {
    aiFakeGateway();
    $object = aiCreateSubject($type);

    $this->service->analyze($type, $object);
    $result = $this->service->analyze($type, $object->fresh(), true);

    expect($result['cached'])->toBeFalse()
        ->and(aiRunCount())->toBe(2);
})->with(AI_TYPES);

test('changing an analysed field invalidates the cache', function (string $type, string $field, mixed $value) {
    aiFakeGateway(Http::sequence()
        ->pushResponse(aiAgentResponse())
        ->pushResponse(aiAgentResponse(aiValidAnalysis(['synthese' => 'Nouvelle']))));
    $object = aiCreateSubject($type);

    $this->service->analyze($type, $object);
    DB::table($object->getTable())->where('id', $object->id)->update([$field => $value]);
    $result = $this->service->analyze($type, $object->fresh());

    expect($result['cached'])->toBeFalse()
        ->and($result['data']['synthese'])->toBe('Nouvelle')
        ->and(aiRunCount())->toBe(2);
})->with([
    'control objective' => ['control', 'objective', 'Nouvel objectif'],
    'measure observations' => ['measure', 'observations', 'Nouvelles observations'],
    'action progress' => ['action', 'progress', 99],
    'risk probability' => ['risk', 'probability', 1],
]);

test('changing a related object invalidates the cache', function (string $case) {
    aiFakeGateway();
    $risk = Risk::factory()->create();
    $control = Control::factory()->create();
    $risk->controls()->sync([$control->id]);

    $this->service->analyze('risk', $risk);

    if ($case === 'new control on a linked measure') {
        $measure = Measure::factory()->done()->create(['score' => 1]);
        $measure->controls()->sync([$control->id]);
    } else {
        $risk->actions()->sync([Action::factory()->create()->id]);
    }

    expect($this->service->analyze('risk', $risk->fresh())['cached'])->toBeFalse()
        ->and(aiRunCount())->toBe(2);
})->with(['new control on a linked measure', 'action added to the risk']);

test('a new task version on the gateway invalidates the cache, the same version keeps it', function (string $type, string $version, bool $cached) {
    aiFakeGateway(aiAgentResponse(null, ['task_version' => 'v1']), aiTasksResponse($version));
    $object = aiCreateSubject($type);

    $this->service->analyze($type, $object);

    expect($this->service->analyze($type, $object->fresh())['cached'])->toBe($cached)
        ->and(aiRunCount())->toBe($cached ? 1 : 2);
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->url() === 'https://agent.example.com/v1/tasks');
})->with(AI_TYPES)->with([
    'new version' => ['v2', false],
    'same version' => ['v1', true],
]);

test('the catalog of the tasks is cached', function () {
    aiFakeGateway();
    $object = aiCreateSubject('control');
    $this->service->analyze('control', $object);

    $this->service->analyze('control', $object->fresh());
    $this->service->analyze('control', $object->fresh());

    expect(Http::recorded(fn (Request $r) => $r->method() === 'GET')->count())->toBe(1)
        ->and(Cache::has(AiGateway::TASKS_CACHE_KEY))->toBeTrue();
});

test('a cached catalog older than the returned task version is forgotten', function () {
    aiFakeGateway(aiAgentResponse(null, ['task_version' => 'v2']), aiTasksResponse('v2'));
    Cache::put(AiGateway::TASKS_CACHE_KEY, [['id' => 'deming/control', 'task_version' => 'v1']], 3600);
    $object = aiCreateSubject('control');

    $this->service->analyze('control', $object, true);

    // The stale catalog would have caused a new analysis at every opening
    expect(Cache::has(AiGateway::TASKS_CACHE_KEY))->toBeFalse()
        ->and($this->service->analyze('control', $object->fresh())['cached'])->toBeTrue();
});

test('a cached analysis is shown when the catalog cannot be fetched', function (Closure $tasks) {
    aiFakeGateway(aiAgentResponse(null, ['task_version' => 'v1']), $tasks);
    $object = aiCreateSubject('risk');
    $first = $this->service->analyze('risk', $object);

    $second = $this->service->analyze('risk', $object->fresh());

    expect($second['cached'])->toBeTrue()
        ->and($second['data'])->toBe($first['data'])
        ->and(aiRunCount())->toBe(1)
        ->and(Cache::has(AiGateway::TASKS_CACHE_KEY))->toBeFalse();
})->with([
    'connection refused' => [fn () => fn () => throw new ConnectionException('cURL error 7: Failed to connect')],
    'server error' => [fn () => aiGatewayError(503, 'gateway_not_configured')],
    'invalid catalog' => [fn () => Http::response(['data' => 'oops'])],
]);

test('changing the user language invalidates the cache', function () {
    aiFakeGateway();
    $object = aiCreateSubject('control');

    $this->service->analyze('control', $object);
    $this->admin->language = 'de';
    $this->admin->save();

    expect($this->service->analyze('control', $object->fresh())['cached'])->toBeFalse();
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['language'] === 'de');
});

// -----------------------------------------------------------------------------
// Response guard
// -----------------------------------------------------------------------------

test('an unknown enum value is kept', function () {
    $data = aiValidAnalysis([
        'statut_evalue' => 'excellent',
        'niveau_risque' => 'extreme',
        'confiance' => 'totale',
        'constats' => [['type' => 'nouveau_type', 'description' => 'D.']],
    ]);
    aiFakeGateway(aiAgentResponse($data));

    expect($this->service->analyze('control', aiCreateSubject('control'))['data'])->toBe($data);
});

test('only the essential fields are required', function () {
    $data = ['synthese' => 'S.', 'statut_evalue' => 'conforme'];
    aiFakeGateway(aiAgentResponse($data));

    expect($this->service->analyze('control', aiCreateSubject('control'))['data'])->toBe($data);
});

test('the guard drops ill-typed optional fields and fields the panel does not use', function () {
    $guard = new AiResponseGuard();

    $result = $guard->check(aiValidAnalysis([
        'extra' => '<script>',
        'niveau_risque' => ['eleve'],
        'constats' => [['type' => 'force', 'description' => 'Ok.', 'extra' => 'x'], ['type' => 'force'], 'oops'],
        'recommandations' => 'oops',
        'questions_ouvertes' => ['Q ?', 3],
        'points_de_vue' => ['ceo' => 'A.', 'dpo' => ['x'], 'autre' => 'Z.'],
    ]));

    expect($result)->not->toHaveKeys(['extra', 'niveau_risque', 'recommandations'])
        ->and($result['constats'])->toBe([['type' => 'force', 'description' => 'Ok.']])
        ->and($result['questions_ouvertes'])->toBe(['Q ?'])
        ->and($result['points_de_vue'])->toBe(['ceo' => 'A.']);
});

test('a missing or ill-typed essential field gives 502', function (mixed $data) {
    expectAiError(fn () => (new AiResponseGuard())->check($data), 'invalid_response', 502);
})->with([
    'no synthese' => [fn () => array_diff_key(aiValidAnalysis(), ['synthese' => 1])],
    'empty synthese' => [fn () => aiValidAnalysis(['synthese' => ' '])],
    'no statut_evalue' => [fn () => array_diff_key(aiValidAnalysis(), ['statut_evalue' => 1])],
    'array statut_evalue' => [fn () => aiValidAnalysis(['statut_evalue' => ['conforme']])],
    'list' => [fn () => [aiValidAnalysis()]],
    'string' => ['analyse'],
]);

test('another response format is logged without blocking', function () {
    aiFakeGateway(aiAgentResponse(null, ['schema' => 'analysis-v2']));
    Log::spy();

    $result = $this->service->analyze('control', aiCreateSubject('control'));

    expect($result['data'])->toBe(aiValidAnalysis());
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context['schema'] === 'analysis-v2')->once();
});

// -----------------------------------------------------------------------------
// Errors
// -----------------------------------------------------------------------------

function expectAiError(callable $call, string $reason, int $status): void
{
    try {
        $call();
    } catch (AiAgentException $e) {
        expect($e->reason)->toBe($reason)->and($e->status)->toBe($status)
            ->and($e->translatedMessage())->not->toContain('secret');

        return;
    }
    test()->fail('AiAgentException expected');
}

test('gateway errors are mapped and keep the previous analysis', function (string $reason, int $status, Closure $fake) {
    $object = aiCreateSubject('measure');
    // First call succeeds, the second one fails
    $calls = 0;
    aiFakeGateway(function () use (&$calls, $fake) {
        return $calls++ === 0 ? aiAgentResponse() : $fake();
    });
    $this->service->analyze('measure', $object);
    $previous = DB::table('measures')->where('id', $object->id)->first();

    expectAiError(fn () => $this->service->analyze('measure', $object->fresh(), true), $reason, $status);

    expect(DB::table('measures')->where('id', $object->id)->first())->toEqual($previous);
})->with([
    '401' => ['invalid_key', 502, fn () => aiGatewayError(401, 'key_blocked')],
    '403' => ['invalid_key', 502, fn () => aiGatewayError(403, 'model_not_allowed')],
    '402' => ['quota_exceeded', 502, fn () => aiGatewayError(402, 'budget_exceeded')],
    '413' => ['too_large', 502, fn () => aiGatewayError(413, 'context_too_large')],
    '429' => ['rate_limited', 429, fn () => aiGatewayError(429, 'rate_limit_exceeded')],
    '400' => ['incompatible', 502, fn () => aiGatewayError(400, 'unknown_field')],
    '404' => ['incompatible', 502, fn () => aiGatewayError(404, 'task_not_found')],
    '502' => ['invalid_response', 502, fn () => aiGatewayError(502, 'invalid_model_response')],
    '503' => ['unreachable', 502, fn () => aiGatewayError(503, 'gateway_not_configured')],
    '504' => ['timeout', 504, fn () => aiGatewayError(504, 'upstream_timeout')],
    '500' => ['unreachable', 502, fn () => Http::response(['error' => 'secret body'], 500)],
    'no data' => ['invalid_response', 502, fn () => Http::response(['task' => 'deming/measure'])],
    'not json' => ['invalid_response', 502, fn () => Http::response('secret not json')],
    'missing synthese' => ['invalid_response', 502, fn () => aiAgentResponse(array_diff_key(aiValidAnalysis(), ['synthese' => 1]))],
    'redirect' => ['unreachable', 502, fn () => Http::response('', 302, ['Location' => 'https://evil.example.com'])],
    'timeout' => ['timeout', 504, fn () => throw new ConnectionException('cURL error 28: Operation timed out')],
    'connection refused' => ['unreachable', 502, fn () => throw new ConnectionException('cURL error 7: Failed to connect')],
]);

test('a redirect is not followed', function () {
    Http::fake([
        'agent.example.com/*' => Http::response('', 302, ['Location' => 'https://evil.example.com/v1/deming/control']),
        'evil.example.com/*' => aiAgentResponse(),
    ]);

    expectAiError(fn () => $this->service->analyze('control', aiCreateSubject('control')), 'unreachable', 502);

    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'evil.example.com'));
});

test('a non local http base_url is refused at runtime without any call', function () {
    Http::fake();
    DB::table('configuration')->where('name', 'ai.base_url')->update(['value' => 'http://agent.example.com/v1']);
    Configuration::clearCache();

    expectAiError(fn () => $this->service->analyze('control', aiCreateSubject('control')), 'insecure_url', 502);
    Http::assertNothingSent();
});

test('without an agent URL nothing is sent', function () {
    Http::fake();
    Configuration::forget('ai.base_url');

    expectAiError(fn () => $this->service->analyze('control', aiCreateSubject('control')), 'not_configured', 502);
    Http::assertNothingSent();
});

test('a local http base_url is allowed', function (string $url) {
    aiFakeGateway();
    Configuration::set('ai.base_url', $url);

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => $r->url() === $url . '/deming/control');
})->with(['http://localhost:8080/v1', 'http://127.0.0.1:8000/v1']);

// -----------------------------------------------------------------------------
// Personal data protection
// -----------------------------------------------------------------------------

test('the redactor masks e-mails, phones, IBAN and IP addresses', function (string $text, string $expected) {
    expect((new PersonalDataRedactor())->redact($text))->toBe($expected);
})->with([
    'email' => ['Écrire à jean.dupont@hopital.lu.', 'Écrire à [email].'],
    'international' => ['Appeler +352 621 123 456', 'Appeler [téléphone]'],
    'LU mobile' => ['GSM 621 123 456', 'GSM [téléphone]'],
    'BE landline' => ['Tél 02 123 45 67', 'Tél [téléphone]'],
    'BE mobile' => ['Tél 0475 12 34 56', 'Tél [téléphone]'],
    'BE international' => ['Tél +32 475 12 34 56', 'Tél [téléphone]'],
    'FR' => ['Tél 01 23 45 67 89 ou 06.12.34.56.78', 'Tél [téléphone] ou [téléphone]'],
    'DE' => ['Tel 030 12345678, 0151/12345678, +49 (0)30 1234567', 'Tel [téléphone], [téléphone], [téléphone]'],
    'IBAN' => ['IBAN LU28 0019 4006 4475 0000 et FR7630006000011234567890189', 'IBAN [iban] et [iban]'],
    'IPv4' => ['Serveur 192.168.1.10 et réseau 10.0.0.0/8', 'Serveur [ip] et réseau [ip]'],
    'IPv6' => ['Hôte fe80::1 et 2001:0db8:85a3:0000:0000:8a2e:0370:7334', 'Hôte [ip] et [ip]'],
    'no false positive' => ['Le 2026-10-27 à 10:30:00, ISO 27002:2022 5.18, score 3, 100 postes', 'Le 2026-10-27 à 10:30:00, ISO 27002:2022 5.18, score 3, 100 postes'],
]);

test('the redactor masks Deming users as whole words only', function () {
    User::factory()->user()->create(['name' => 'Marie Curie', 'login' => 'mcurie', 'email' => 'marie@example.org']);
    User::factory()->user()->create(['name' => 'Al', 'login' => 'al', 'email' => 'al@example.org']);

    $redactor = new PersonalDataRedactor();

    expect($redactor->redact('Vu avec MARIE CURIE et mcurie.'))->toBe('Vu avec [utilisateur] et [utilisateur].')
        ->and($redactor->redact('Le compte mcuriex et le mot Alpha restent, Al aussi.'))
        ->toBe('Le compte mcuriex et le mot Alpha restent, Al aussi.');
});

test('free-text personal data is masked before being sent', function () {
    aiFakeGateway();
    User::factory()->user()->create(['name' => 'Bertrand Lemaire']);
    $measure = aiCreateSubject('measure');
    $measure->observations = 'Contact jean.dupont@hopital.lu, +352 621 123 456, vu avec Bertrand Lemaire.';
    $measure->save();

    $this->service->analyze('measure', $measure);

    $body = aiSentRun()->body();
    expect(json_decode($body, true)['context']['observations'])->toBe('Contact [email], [téléphone], vu avec [utilisateur].')
        ->and($body)->not->toContain('jean.dupont')
        ->not->toContain('621 123 456')
        ->not->toContain('Lemaire');
});

test('the hash is computed after masking', function () {
    aiFakeGateway();
    $measure = aiCreateSubject('measure');
    $measure->observations = 'Écrire à alice@example.org';
    $measure->save();
    $this->service->analyze('measure', $measure);
    $hash = DB::table('measures')->where('id', $measure->id)->value('ai_analysis_hash');

    // Another address masks to the same text: same inputs, cache kept
    $measure->observations = 'Écrire à bob@example.org';
    $measure->save();
    expect($this->service->analyze('measure', $measure->fresh())['cached'])->toBeTrue();

    // Adding an address changes the masked text: cache invalidated
    $measure->observations = 'Écrire à bob@example.org ou carol@example.org';
    $measure->save();
    expect($this->service->analyze('measure', $measure->fresh())['cached'])->toBeFalse();

    $context = json_encode(AiSubjects::context('measure', $measure->fresh()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    expect($context)->not->toContain('example.org')
        ->and(DB::table('measures')->where('id', $measure->id)->value('ai_analysis_hash'))->not->toBe($hash);
});

test('late flags are booleans', function () {
    $measure = Measure::factory()->create(['plan_date' => now()->subMonth()->format('Y-m-d'), 'realisation_date' => null, 'status' => 0]);
    $action = Action::factory()->create(['due_date' => now()->subDay()->format('Y-m-d'), 'status' => 0]);
    $risk = Risk::factory()->create(['next_review_at' => now()->subDay()->format('Y-m-d')]);

    expect(AiSubjects::context('measure', $measure)['en_retard'])->toBeTrue()
        ->and(AiSubjects::context('action', $action)['en_retard'])->toBeTrue()
        ->and(AiSubjects::context('risk', $risk)['revue_en_retard'])->toBeTrue();
});

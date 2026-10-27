<?php

use App\Models\Action;
use App\Models\Configuration;
use App\Models\Control;
use App\Models\Measure;
use App\Models\Risk;
use App\Models\User;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiSubjects;
use App\Services\Ai\PersonalDataRedactor;
use App\Services\AiAgentService;
use Database\Seeders\RiskScoringConfigSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

const AI_TYPES = ['control', 'measure', 'action', 'risk'];

beforeEach(function () {
    $this->seed(RiskScoringConfigSeeder::class);
    $this->admin = User::factory()->admin()->create(['language' => 'fr']);
    $this->actingAs($this->admin);
    $this->service = new AiAgentService();
});

// -----------------------------------------------------------------------------
// Request sent to the agent
// -----------------------------------------------------------------------------

test('the request contains the prompt, the schema and the tagged context', function (string $type) {
    Http::fake(['*' => aiAgentResponse()]);
    Configuration::set(AiSubjects::get($type)['prompt'], "PROMPT-{$type} en {{langue}}");
    $object = aiCreateSubject($type);

    $result = $this->service->analyze($type, $object);

    expect($result['cached'])->toBeFalse()
        ->and($result['data']['statut_evalue'])->toBe('partiellement_conforme')
        ->and($result['model'])->toBe('gpt-oss-120b');

    $tag = AiSubjects::get($type)['tag'];
    Http::assertSent(function (Request $request) use ($type, $tag) {
        $system = $request['messages'][0]['content'];
        $user = $request['messages'][1]['content'];

        return $request->url() === 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1/chat/completions'
            && $request['messages'][0]['role'] === 'system'
            && str_starts_with($system, "PROMPT-{$type} en français")
            && str_contains($system, '"points_de_vue"')
            && str_starts_with($user, 'Date du jour : ' . now()->toDateString())
            && str_contains($user, "<{$tag}>")
            && str_contains($user, "</{$tag}>")
            && $request['response_format'] === ['type' => 'json_object']
            && $request['max_tokens'] === 8000
            && $request['temperature'] === 0.2
            && $request['reasoning_effort'] === 'low';
    });
})->with(AI_TYPES);

test('reasoning_effort is only sent to gpt-oss models', function () {
    Http::fake(['*' => aiAgentResponse()]);
    Configuration::set('ai.model', 'llama3.1');

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => $r['model'] === 'llama3.1' && ! isset($r['reasoning_effort']));
});

test('no Authorization header is sent without an API key', function () {
    Http::fake(['*' => aiAgentResponse()]);

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('Authorization'));
});

test('the API key is sent as a bearer token', function () {
    Http::fake(['*' => aiAgentResponse()]);
    Configuration::set('ai.api_key', 'sk-test');

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer sk-test'));
});

test('the context contains no user name nor e-mail', function () {
    Http::fake(['*' => aiAgentResponse()]);
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

    Http::assertSentCount(4);
    Http::assertSent(function (Request $r) {
        $user = $r['messages'][1]['content'];

        return ! str_contains($user, 'Gertrude')
            && ! str_contains($user, 'Vanderbilt')
            && ! str_contains($user, 'gvanderbilt')
            && ! str_contains($user, 'gertrude@example.org')
            && ! str_contains($user, $this->admin->email);
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

// -----------------------------------------------------------------------------
// Storage and cache
// -----------------------------------------------------------------------------

test('the analysis is stored without touching updated_at nor the audit log', function (string $type) {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject($type);
    $table = $object->getTable();
    DB::table($table)->where('id', $object->id)->update(['updated_at' => '2020-01-01 00:00:00']);
    $logs = DB::table('audit_logs')->count();

    $this->service->analyze($type, $object);

    $row = DB::table($table)->where('id', $object->id)->first();
    expect($row->updated_at)->toBe('2020-01-01 00:00:00')
        ->and(json_decode($row->ai_analysis, true))->toBe(aiValidAnalysis())
        ->and($row->ai_analysis_model)->toBe('gpt-oss-120b')
        ->and($row->ai_analysis_hash)->toHaveLength(64)
        ->and($row->ai_analysis_at)->not->toBeNull()
        ->and(DB::table('audit_logs')->count())->toBe($logs);
})->with(AI_TYPES);

test('the action progress history is unchanged by an analysis', function () {
    Http::fake(['*' => aiAgentResponse()]);
    $action = Action::factory()->create(['progress' => 10]);
    $action->update(['progress' => 40]);
    $before = $action->history();

    $this->service->analyze('action', $action);

    expect(Action::find($action->id)->history())->toBe($before);
});

test('a second call on an unchanged object is served from the cache', function (string $type) {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject($type);

    $first = $this->service->analyze($type, $object);
    $second = $this->service->analyze($type, $object->fresh());

    expect($second['cached'])->toBeTrue()
        ->and($second['data'])->toBe($first['data']);
    Http::assertSentCount(1);
})->with(AI_TYPES);

test('force runs a new analysis even when nothing changed', function (string $type) {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject($type);

    $this->service->analyze($type, $object);
    $result = $this->service->analyze($type, $object->fresh(), true);

    expect($result['cached'])->toBeFalse();
    Http::assertSentCount(2);
})->with(AI_TYPES);

test('changing an analysed field invalidates the cache', function (string $type, string $field, mixed $value) {
    Http::fake(['*' => Http::sequence()
        ->pushResponse(aiAgentResponse())
        ->pushResponse(aiAgentResponse(aiValidAnalysis(['synthese' => 'Nouvelle'])))]);
    $object = aiCreateSubject($type);

    $this->service->analyze($type, $object);
    DB::table($object->getTable())->where('id', $object->id)->update([$field => $value]);
    $result = $this->service->analyze($type, $object->fresh());

    expect($result['cached'])->toBeFalse()
        ->and($result['data']['synthese'])->toBe('Nouvelle');
    Http::assertSentCount(2);
})->with([
    'control objective' => ['control', 'objective', 'Nouvel objectif'],
    'measure observations' => ['measure', 'observations', 'Nouvelles observations'],
    'action progress' => ['action', 'progress', 99],
    'risk probability' => ['risk', 'probability', 1],
]);

test('changing a related object invalidates the cache', function (string $case) {
    Http::fake(['*' => aiAgentResponse()]);
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

    expect($this->service->analyze('risk', $risk->fresh())['cached'])->toBeFalse();
    Http::assertSentCount(2);
})->with(['new control on a linked measure', 'action added to the risk']);

test('changing the prompt of the type or the model invalidates the cache', function (string $name, string $value) {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject('control');

    $this->service->analyze('control', $object);
    Configuration::set($name, $value);

    expect($this->service->analyze('control', $object->fresh())['cached'])->toBeFalse();
})->with([
    ['ai.prompt_control', 'Autre prompt {{langue}}'],
    ['ai.model', 'mistral-large'],
]);

test('changing the prompt of another type keeps the cache', function () {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject('control');

    $this->service->analyze('control', $object);
    Configuration::set('ai.prompt_risk', 'Autre prompt');

    expect($this->service->analyze('control', $object->fresh())['cached'])->toBeTrue();
});

test('changing the user language invalidates the cache', function () {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject('control');

    $this->service->analyze('control', $object);
    $this->admin->language = 'de';
    $this->admin->save();

    expect($this->service->analyze('control', $object->fresh())['cached'])->toBeFalse();
    Http::assertSent(fn (Request $r) => str_contains($r['messages'][0]['content'], 'Deutsch')
        || ! str_contains(Configuration::get('ai.prompt_control'), '{{langue}}'));
});

// -----------------------------------------------------------------------------
// Errors
// -----------------------------------------------------------------------------

function expectAiError(callable $call, string $reason, int $status): void
{
    try {
        $call();
    } catch (AiAgentException $e) {
        expect($e->reason)->toBe($reason)->and($e->status)->toBe($status);

        return;
    }
    test()->fail('AiAgentException expected');
}

test('agent errors are mapped and keep the previous analysis', function (string $reason, int $status, Closure $fake) {
    $object = aiCreateSubject('measure');
    // First call succeeds, the second one fails
    $calls = 0;
    Http::fake(function () use (&$calls, $fake) {
        return $calls++ === 0 ? aiAgentResponse() : $fake();
    });
    $this->service->analyze('measure', $object);
    $previous = DB::table('measures')->where('id', $object->id)->first();

    expectAiError(fn () => $this->service->analyze('measure', $object->fresh(), true), $reason, $status);

    expect(DB::table('measures')->where('id', $object->id)->first())->toEqual($previous);
})->with([
    'truncated' => ['truncated', 502, fn () => aiAgentResponse(null, 'length')],
    'empty content' => ['truncated', 502, fn () => aiAgentResponse('')],
    'invalid json' => ['invalid_response', 502, fn () => aiAgentResponse('pas du json')],
    'missing key' => ['invalid_response', 502, fn () => aiAgentResponse(array_replace(aiValidAnalysis(), ['points_de_vue' => ['ceo' => 'a', 'rssi' => 'b', 'auditeur' => 'c']]))],
    'bad enum' => ['invalid_response', 502, fn () => aiAgentResponse(aiValidAnalysis(['statut_evalue' => 'excellent']))],
    'http error' => ['unreachable', 502, fn () => Http::response(['error' => 'secret body'], 500)],
    'redirect' => ['unreachable', 502, fn () => Http::response('', 302, ['Location' => 'https://evil.example.com'])],
    'timeout' => ['timeout', 504, fn () => throw new ConnectionException('cURL error 28: Operation timed out')],
    'connection refused' => ['unreachable', 502, fn () => throw new ConnectionException('cURL error 7: Failed to connect')],
]);

test('a redirect is not followed', function () {
    Http::fake([
        'oai.endpoints.kepler.ai.cloud.ovh.net/*' => Http::response('', 302, ['Location' => 'https://evil.example.com/v1/chat/completions']),
        'evil.example.com/*' => aiAgentResponse(),
    ]);

    expectAiError(fn () => $this->service->analyze('control', aiCreateSubject('control')), 'unreachable', 502);

    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'evil.example.com'));
});

test('a JSON object surrounded by text is extracted', function () {
    Http::fake(['*' => aiAgentResponse("Voici l'analyse :\n" . json_encode(aiValidAnalysis()) . "\nFin.")]);

    expect($this->service->analyze('control', aiCreateSubject('control'))['data'])->toBe(aiValidAnalysis());
});

test('unknown properties of the answer are dropped', function () {
    Http::fake(['*' => aiAgentResponse(aiValidAnalysis(['extra' => '<script>']))]);

    expect($this->service->analyze('control', aiCreateSubject('control'))['data'])->not->toHaveKey('extra');
});

test('a non local http base_url is refused at runtime without any call', function () {
    Http::fake();
    DB::table('configuration')->insert(['name' => 'ai.base_url', 'value' => 'http://agent.example.com/v1']);
    Configuration::clearCache();

    expectAiError(fn () => $this->service->analyze('control', aiCreateSubject('control')), 'insecure_url', 502);
    Http::assertNothingSent();
});

test('a local http base_url is allowed', function (string $url) {
    Http::fake(['*' => aiAgentResponse()]);
    Configuration::set('ai.base_url', $url);

    $this->service->analyze('control', aiCreateSubject('control'));

    Http::assertSent(fn (Request $r) => str_starts_with($r->url(), $url));
})->with(['http://localhost:11434/v1', 'http://127.0.0.1:8000/v1']);

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
    Http::fake(['*' => aiAgentResponse()]);
    User::factory()->user()->create(['name' => 'Bertrand Lemaire']);
    $measure = aiCreateSubject('measure');
    $measure->observations = 'Contact jean.dupont@hopital.lu, +352 621 123 456, vu avec Bertrand Lemaire.';
    $measure->save();

    $this->service->analyze('measure', $measure);

    Http::assertSent(function (Request $r) {
        $user = $r['messages'][1]['content'];

        return str_contains($user, 'Contact [email], [téléphone], vu avec [utilisateur].')
            && ! str_contains($user, 'jean.dupont')
            && ! str_contains($user, '621 123 456')
            && ! str_contains($user, 'Lemaire');
    });
});

test('the hash is computed after masking', function () {
    Http::fake(['*' => aiAgentResponse()]);
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

<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Action;
use App\Models\Configuration;
use App\Models\Control;
use App\Models\Measure;
use App\Models\Risk;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\RiskScoringConfigSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

const AI_ENDPOINT_TYPES = ['control', 'measure', 'action', 'risk'];

beforeEach(function () {
    $this->seed(RiskScoringConfigSeeder::class);
    $this->admin = User::factory()->admin()->create();
    aiEnable();
});

function storeAnalysis(Illuminate\Database\Eloquent\Model $object): void
{
    DB::table($object->getTable())->where('id', $object->id)->update([
        'ai_analysis' => json_encode(aiValidAnalysis(['synthese' => 'CACHED-ANALYSIS'])),
        'ai_analysis_at' => now(),
        'ai_analysis_model' => 'gpt-oss-120b',
        'ai_analysis_hash' => str_repeat('0', 64),
    ]);
}

// -----------------------------------------------------------------------------
// Access control
// -----------------------------------------------------------------------------

test('AI disabled returns 404', function (string $type) {
    Configuration::set('ai.enabled', '0');
    Http::fake();
    $object = aiCreateSubject($type);

    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}")->assertNotFound();
    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}/preview")->assertNotFound();
    Http::assertNothingSent();
})->with(AI_ENDPOINT_TYPES);

test('API role gets 403', function (string $type) {
    Http::fake();
    $object = aiCreateSubject($type);

    $this->actingAs(User::factory()->apiUser()->create())->postJson("/ai/{$type}/{$object->id}")->assertForbidden();
    Http::assertNothingSent();
})->with(AI_ENDPOINT_TYPES);

test('auditor gets 403 even with access, a cached analysis or force', function (string $type) {
    Http::fake();
    $auditor = User::factory()->auditor()->create();
    $object = $type === 'risk' ? Risk::factory()->create(['owner_id' => $auditor->id]) : aiCreateSubject($type);
    storeAnalysis($object);

    $this->actingAs($auditor)->postJson("/ai/{$type}/{$object->id}")->assertForbidden()->assertDontSee('CACHED-ANALYSIS');
    $this->actingAs($auditor)->postJson("/ai/{$type}/{$object->id}", ['force' => 1])->assertForbidden();
    $this->actingAs($auditor)->postJson("/ai/{$type}/{$object->id}/preview")->assertForbidden();
    Http::assertNothingSent();
})->with(AI_ENDPOINT_TYPES);

test('the auditor can see an owned risk but not analyse it', function () {
    $auditor = User::factory()->auditor()->create();
    $risk = Risk::factory()->create(['owner_id' => $auditor->id]);

    $this->actingAs($auditor)->get("/risk/show/{$risk->id}")->assertOk();
    $this->actingAs($auditor)->postJson("/ai/risk/{$risk->id}")->assertForbidden();
});

test('admin gets the analysis', function (string $type) {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject($type);

    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}")
        ->assertOk()
        ->assertJson(['ok' => true, 'cached' => false, 'model' => 'gpt-oss-120b', 'data' => aiValidAnalysis()])
        ->assertJsonStructure(['ok', 'data' => ['synthese', 'points_de_vue' => ['ceo', 'rssi', 'dpo', 'auditeur']], 'cached', 'analyzed_at', 'model']);
})->with(AI_ENDPOINT_TYPES);

test('unknown type returns 404', function () {
    $this->actingAs($this->admin)->postJson('/ai/user/1')->assertNotFound();
    $this->actingAs($this->admin)->postJson('/ai/user/1/preview')->assertNotFound();
});

test('unknown id returns 404', function (string $type) {
    Http::fake();

    $this->actingAs($this->admin)->postJson("/ai/{$type}/999999")->assertNotFound();
    Http::assertNothingSent();
})->with(AI_ENDPOINT_TYPES);

test('a request without CSRF token is rejected', function () {
    Http::fake();
    $object = aiCreateSubject('control');
    // The CSRF middleware is skipped in unit tests: enforce it
    $this->app->instance(VerifyCsrfToken::class, new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken {
        protected function runningUnitTests()
        {
            return false;
        }
    });

    $this->actingAs($this->admin)->postJson("/ai/control/{$object->id}")->assertStatus(419);
    Http::assertNothingSent();
});

test('a user who cannot see the object gets 403 without any call', function (string $type) {
    Http::fake();
    $user = User::factory()->user()->create();
    $object = aiCreateSubject($type);
    storeAnalysis($object);

    // Group view: the user only sees what is assigned to him
    $this->actingAs($user)->withSession(['group_view' => false])
        ->postJson("/ai/{$type}/{$object->id}")->assertForbidden()->assertDontSee('CACHED-ANALYSIS');
    $this->actingAs($user)->withSession(['group_view' => false])
        ->postJson("/ai/{$type}/{$object->id}/preview")->assertForbidden();
    Http::assertNothingSent();
})->with(['control', 'measure']);

test('a user assigned to a control gets the analysis of its security measure', function () {
    Http::fake(['*' => aiAgentResponse()]);
    $user = User::factory()->user()->create();
    $control = Control::factory()->create();
    $measure = Measure::factory()->create();
    $measure->controls()->sync([$control->id]);
    $measure->users()->sync([$user->id]);

    $this->actingAs($user)->withSession(['group_view' => false])->postJson("/ai/control/{$control->id}")->assertOk();
    $this->actingAs($user)->withSession(['group_view' => false])->postJson("/ai/measure/{$measure->id}")->assertOk();
});

test('a user assigned through a group can analyse the control', function () {
    Http::fake(['*' => aiAgentResponse()]);
    $user = User::factory()->user()->create();
    $group = UserGroup::factory()->create();
    $group->users()->sync([$user->id]);
    $measure = aiCreateSubject('measure');
    $group->measures()->sync([$measure->id]);

    $this->actingAs($user)->withSession(['group_view' => false])->postJson("/ai/measure/{$measure->id}")->assertOk();
});

test('auditee can analyse an assigned control only, and no action plan', function () {
    Http::fake(['*' => aiAgentResponse()]);
    $auditee = User::factory()->auditee()->create();
    $assigned = aiCreateSubject('measure');
    $assigned->users()->sync([$auditee->id]);
    $other = aiCreateSubject('measure');
    $action = Action::factory()->create();

    $this->actingAs($auditee)->postJson("/ai/measure/{$assigned->id}")->assertOk();
    $this->actingAs($auditee)->postJson("/ai/measure/{$other->id}")->assertForbidden();
    $this->actingAs($auditee)->get("/action/show/{$action->id}")->assertForbidden();
    $this->actingAs($auditee)->postJson("/ai/action/{$action->id}")->assertForbidden();
});

test('risk analysis follows RiskController@show for every role', function (string $role) {
    Http::fake(['*' => aiAgentResponse()]);
    $user = User::factory()->{$role}()->create();
    $risk = Risk::factory()->create(['owner_id' => $this->admin->id]);

    $show = $this->actingAs($user)->get("/risk/show/{$risk->id}")->status();
    $ai = $this->actingAs($user)->postJson("/ai/risk/{$risk->id}")->status();

    expect($show)->toBeIn([200, 403])
        ->and($ai === 200)->toBe($show === 200);
})->with(['admin', 'user', 'auditee']);

// -----------------------------------------------------------------------------
// Agent answers
// -----------------------------------------------------------------------------

test('agent failures return 502 or 504 with a generic message', function (Closure $fake, int $status) {
    Http::fake(['*' => $fake]);
    $object = aiCreateSubject('risk');

    $response = $this->actingAs($this->admin)->postJson("/ai/risk/{$object->id}")->assertStatus($status);

    $response->assertJson(['ok' => false]);
    expect($response->json('error'))->toBeString()
        ->and($response->getContent())->not->toContain('secret body')
        ->not->toContain('oai.endpoints');
})->with([
    'truncated' => [fn () => aiAgentResponse(null, 'length'), 502],
    'invalid json' => [fn () => aiAgentResponse('{oops'), 502],
    'missing dpo' => [fn () => aiAgentResponse(array_replace(aiValidAnalysis(), ['points_de_vue' => ['ceo' => 'a', 'rssi' => 'b', 'auditeur' => 'c']])), 502],
    'http error with body' => [fn () => Http::response('secret body', 500), 502],
    'redirect' => [fn () => Http::response('secret body', 302, ['Location' => 'https://elsewhere.example.com']), 502],
    'timeout' => [fn () => throw new ConnectionException('cURL error 28: Operation timed out'), 504],
]);

test('a failure keeps the previous analysis', function (string $type) {
    $object = aiCreateSubject($type);
    storeAnalysis($object);
    $before = DB::table($object->getTable())->where('id', $object->id)->first();
    Http::fake(['*' => aiAgentResponse(null, 'length')]);

    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}", ['force' => 1])->assertStatus(502);

    expect(DB::table($object->getTable())->where('id', $object->id)->first())->toEqual($before);
})->with(AI_ENDPOINT_TYPES);

test('the second call is cached and force runs a new analysis', function (string $type) {
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject($type);

    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}")->assertJson(['cached' => false]);
    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}")->assertJson(['cached' => true]);
    Http::assertSentCount(1);

    $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}", ['force' => 1])->assertJson(['cached' => false]);
    Http::assertSentCount(2);
})->with(AI_ENDPOINT_TYPES);

test('the eleventh request in a minute is throttled', function () {
    // Rate limiting is disabled for Feature/Web tests: restore it here
    $this->app->forgetInstance(ThrottleRequests::class);
    Http::fake(['*' => aiAgentResponse()]);
    $object = aiCreateSubject('control');

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($this->admin)->postJson("/ai/control/{$object->id}")->assertOk();
    }
    $this->actingAs($this->admin)->postJson("/ai/control/{$object->id}")->assertStatus(429);
});

// -----------------------------------------------------------------------------
// Preview of the data sent
// -----------------------------------------------------------------------------

test('preview returns the masked context without calling the agent', function (string $type) {
    Http::fake();
    $object = aiCreateSubject($type);

    $response = $this->actingAs($this->admin)->postJson("/ai/{$type}/{$object->id}/preview")->assertOk();

    expect($response->json('ok'))->toBeTrue()
        ->and($response->json('context'))->toBeArray()->not->toBeEmpty();
    Http::assertNothingSent();
})->with(AI_ENDPOINT_TYPES);

test('preview masks personal data', function () {
    Http::fake();
    $measure = aiCreateSubject('measure');
    $measure->observations = 'Appeler jean.dupont@hopital.lu au +352 621 123 456';
    $measure->save();

    $response = $this->actingAs($this->admin)->postJson("/ai/measure/{$measure->id}/preview")->assertOk();

    expect($response->json('context.observations'))->toBe('Appeler [email] au [téléphone]');
    Http::assertNothingSent();
});

test('preview is refused to the API role', function () {
    $object = aiCreateSubject('risk');

    $this->actingAs(User::factory()->apiUser()->create())->postJson("/ai/risk/{$object->id}/preview")->assertForbidden();
});

// -----------------------------------------------------------------------------
// Icon and panel on the show pages
// -----------------------------------------------------------------------------

function aiShowUrl(string $type, int $id): string
{
    return [
        'control' => "/alice/show/{$id}",
        'measure' => "/bob/show/{$id}",
        'action' => "/action/show/{$id}",
        'risk' => "/risk/show/{$id}",
    ][$type];
}

test('no icon when AI is disabled', function (string $type) {
    Configuration::set('ai.enabled', '0');
    $object = aiCreateSubject($type);

    $this->actingAs($this->admin)->get(aiShowUrl($type, $object->id))
        ->assertOk()
        ->assertDontSee('data-ai-type', false)
        ->assertDontSee('id="ai-panel"', false);
})->with(AI_ENDPOINT_TYPES);

test('the icon and the panel are shown to an authorized user', function (string $type) {
    $object = aiCreateSubject($type);

    $this->actingAs($this->admin)->get(aiShowUrl($type, $object->id))
        ->assertOk()
        ->assertSee('data-ai-type="' . $type . '"', false)
        ->assertSee('data-ai-id="' . $object->id . '"', false)
        ->assertSee('bi-robot', false)
        ->assertSee('id="ai-panel"', false)
        ->assertSee('aria-label="' . trans('ai.button') . '"', false)
        ->assertSee('oai.endpoints.kepler.ai.cloud.ovh.net');
})->with(AI_ENDPOINT_TYPES);

test('the icon shows a dot when an analysis is saved', function (string $type) {
    $object = aiCreateSubject($type);
    storeAnalysis($object);

    $this->actingAs($this->admin)->get(aiShowUrl($type, $object->id))
        ->assertOk()
        ->assertSee('has-analysis"', false);
})->with(AI_ENDPOINT_TYPES);

test('the auditor sees the page but neither the icon nor the panel', function (string $type) {
    $auditor = User::factory()->auditor()->create();
    $object = $type === 'risk' ? Risk::factory()->create(['owner_id' => $auditor->id])->fresh() : aiCreateSubject($type);
    storeAnalysis($object);

    $this->actingAs($auditor)->get(aiShowUrl($type, $object->id))
        ->assertOk()
        ->assertDontSee('data-ai-type', false)
        ->assertDontSee('id="ai-panel"', false)
        ->assertDontSee('CACHED-ANALYSIS');
})->with(AI_ENDPOINT_TYPES);

test('the panel never injects AI output as HTML', function () {
    $source = file_get_contents(resource_path('views/partials/ai-panel.blade.php'));

    expect($source)->not->toContain('innerHTML')
        ->not->toContain('insertAdjacentHTML')
        ->not->toContain('outerHTML');
});

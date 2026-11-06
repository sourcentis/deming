<?php

use App\Models\Measure;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow('2026-10-17 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

// --- Access

test('guest is redirected to login', function () {
    $this->get('/radar/monitoring')->assertRedirect('/login');
});

test('admin, user and auditor can access the monitoring page', function (string $state) {
    $user = User::factory()->{$state}()->create();
    $this->actingAs($user)->get('/radar/monitoring')
        ->assertStatus(200)
        ->assertViewIs('radar.monitoring');
})->with(['admin', 'user', 'auditor']);

test('auditee and api users cannot access the monitoring page', function (string $state) {
    $user = User::factory()->{$state}()->create();
    $this->actingAs($user)->get('/radar/monitoring')
        ->assertStatus(403)
        ->assertDontSee('monitoring-value', false);
})->with(['auditee', 'apiUser']);

test('disabled users cannot access the monitoring page', function () {
    $user = User::factory()->create(['role' => User::ROLE_DISABLED]);
    $this->actingAs($user)->get('/radar/monitoring')
        ->assertStatus(403)
        ->assertDontSee('monitoring-value', false);
});

test('the route is named radar.monitoring', function () {
    expect(route('radar.monitoring', [], false))->toBe('/radar/monitoring');
});

test('auditor sees the monitoring menu entry, auditee does not', function () {
    $auditor = User::factory()->auditor()->create();
    $this->actingAs($auditor)->get('/radar/monitoring')
        ->assertSee('href="/radar/monitoring" class="active"', false);

    $auditee = User::factory()->auditee()->create();
    $this->actingAs($auditee)->get('/')->assertDontSee('/radar/monitoring', false);
});

test('the menu entry is translated', function (string $language) {
    $user = User::factory()->admin()->create(['language' => $language]);
    $this->actingAs($user)->get('/radar/monitoring')
        ->assertSee('<span class="menu-text">' . trans('menu.radar_monitoring', [], $language) . '</span>', false);
})->with(['fr', 'en', 'de']);

// --- Rendering

test('renders the computed values', function () {
    $security = UserGroup::factory()->security()->create(['name' => 'SecOps']);
    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-07'])->groups()->attach($security->id);
    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-12-01']);
    Measure::factory()->create(['status' => 2, 'plan_date' => '2026-09-01', 'realisation_date' => '2026-09-02']);

    $admin = User::factory()->admin()->create(['language' => 'en']);
    $this->actingAs($admin)->get('/radar/monitoring')
        ->assertStatus(200)
        ->assertViewHas('total', 3)
        ->assertViewHas('live', 2)
        ->assertViewHas('overdue', 1)
        ->assertSee('<div class="fs-2 fw-bold" id="tile-rate">50.0%</div>', false)
        ->assertSee('Security: 1 of 1 live / active measurements are overdue (100.0%).');
});

test('an empty database renders with zero rates', function () {
    $admin = User::factory()->admin()->create(['language' => 'en']);
    $this->actingAs($admin)->get('/radar/monitoring')
        ->assertStatus(200)
        ->assertViewHas('overdue_rate', 0.0)
        ->assertSee('<div class="fs-2 fw-bold" id="tile-rate">0.0%</div>', false);
});

test('a disabled user with a live measure is shown with a badge', function () {
    $disabled = User::factory()->create(['name' => 'Dormant User', 'role' => User::ROLE_DISABLED]);
    Measure::factory()->create(['status' => 0])->users()->attach($disabled->id);
    User::factory()->create(['name' => 'Gone User', 'role' => User::ROLE_DISABLED]);

    $admin = User::factory()->admin()->create(['language' => 'fr']);
    $this->actingAs($admin)->get('/radar/monitoring')
        ->assertSee('Dormant User')
        ->assertSee('Désactivé')
        ->assertDontSee('Gone User');
});

// --- Scope

test('group view off restricts every block to the user measures', function () {
    $user = User::factory()->user()->create(['name' => 'Scoped User']);
    $other = User::factory()->user()->create(['name' => 'Other User']);
    $group = UserGroup::factory()->create(['name' => 'Team']);
    $group->users()->attach($user->id);

    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-01'])->users()->attach($user->id);
    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-12-01'])->groups()->attach($group->id);
    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-01'])->users()->attach($other->id);
    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-01']);

    // Default: all data
    $this->actingAs($user)->get('/radar/monitoring')
        ->assertViewHas('total', 4)
        ->assertViewHas('overdue', 3);

    // Toggle to "my data"
    $this->actingAs($user)->get('/group/toggle');

    $this->actingAs($user)->get('/radar/monitoring')
        ->assertViewHas('total', 2)
        ->assertViewHas('live', 2)
        ->assertViewHas('overdue', 1)
        ->assertViewHas('ownership', fn ($o) => $o['total']['live'] === 2)
        ->assertViewHas('priorities', fn ($p) => array_sum(array_column($p, 'count')) === 2)
        ->assertViewHas('combinations', fn ($c) => array_column($c, 'label') === ['Scoped User', 'Team'])
        ->assertViewHas('individuals', fn ($rows) => collect($rows)->firstWhere('name', 'Other User')['live'] === 0
            && collect($rows)->firstWhere('name', 'Scoped User')['live'] === 2)
        ->assertViewHas('groups', fn ($rows) => collect($rows)->firstWhere('name', 'Team')['live'] === 1);
});

// --- Business value

const MONITORING_VALUE_KEYS = [
    'population', 'backlog_health', 'ownership_distribution', 'ownership_performance',
    'priorities', 'combinations', 'individuals', 'groups',
];

test('every block shows its business value text', function () {
    $admin = User::factory()->admin()->create(['language' => 'fr']);
    $response = $this->actingAs($admin)->get('/radar/monitoring')->assertStatus(200);

    foreach (MONITORING_VALUE_KEYS as $key) {
        $response->assertSeeText(trans('cruds.monitoring.value.' . $key, [], 'fr'));
    }
    $response->assertSeeText(trans('cruds.monitoring.value_label', [], 'fr'))
        ->assertSeeText(trans('cruds.monitoring.purpose_label', [], 'fr'));
});

test('the business value labels are translated', function (string $language, array $labels) {
    $admin = User::factory()->admin()->create(['language' => $language]);
    $this->actingAs($admin)->get('/radar/monitoring')->assertSeeText($labels);
})->with([
    'en' => ['en', ['Business value', 'Purpose']],
    'de' => ['de', ['Geschäftlicher Nutzen', 'Zweck']],
]);

test('each business value text is placed after its block', function () {
    UserGroup::factory()->security()->create(['name' => 'SecOps']);
    Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-07']);

    $admin = User::factory()->admin()->create(['language' => 'fr']);
    $value = fn (string $key) => e(trans('cruds.monitoring.value.' . $key, [], 'fr'));
    $title = fn (string $key) => e(trans('cruds.monitoring.' . $key, [], 'fr'));

    $this->actingAs($admin)->get('/radar/monitoring')->assertSeeInOrder([
        'id="tile-rate"',
        $value('population'),
        $value('backlog_health'),
        'id="owned-non-security"',
        $value('ownership_distribution'),
        'id="ownership-table"',
        $value('ownership_performance'),
        $title('meaning_title'),
        'id="priorities-table"',
        $value('priorities'),
        'id="combinations-table"',
        $value('combinations'),
        $title('individuals_title'),
        'id="individuals-table"',
        $value('individuals'),
        $title('groups_title'),
        'id="groups-table"',
        $value('groups'),
        $title('definitions_title'),
    ], false);
});

test('the combinations block uses the purpose label', function () {
    $admin = User::factory()->admin()->create(['language' => 'en']);
    $this->actingAs($admin)->get('/radar/monitoring')->assertSeeInOrder([
        'data-value="priorities"', 'Business value:',
        'data-value="combinations"', 'Purpose:',
        'data-value="individuals"', 'Business value:',
    ], false);
});

test('the business value texts are shown on an empty database', function () {
    $admin = User::factory()->admin()->create(['language' => 'en']);
    $response = $this->actingAs($admin)->get('/radar/monitoring')
        ->assertStatus(200)
        ->assertViewHas('total', 0);

    foreach (MONITORING_VALUE_KEYS as $key) {
        $response->assertSee('data-value="' . $key . '"', false)
            ->assertSeeText(trans('cruds.monitoring.value.' . $key, [], 'en'));
    }
});

test('the business value keys are identical in every language', function () {
    $keys = fn (string $language) => array_keys(trans('cruds.monitoring.value', [], $language));

    expect($keys('fr'))->toBe(MONITORING_VALUE_KEYS)
        ->and($keys('en'))->toBe(MONITORING_VALUE_KEYS)
        ->and($keys('de'))->toBe(MONITORING_VALUE_KEYS);
});

// --- Performance

test('the number of queries does not depend on the number of measures', function () {
    $admin = User::factory()->admin()->create();

    $countQueries = function () use ($admin) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get('/radar/monitoring')->assertStatus(200);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    $seed = function (int $n) {
        $group = UserGroup::factory()->create();
        $user = User::factory()->user()->create();
        $group->users()->attach($user->id);
        foreach (Measure::factory()->count($n)->create(['status' => 0]) as $measure) {
            $measure->users()->attach($user->id);
            $measure->groups()->attach($group->id);
        }
    };

    $seed(2);
    $small = $countQueries();
    $seed(30);
    $large = $countQueries();

    expect($large)->toBe($small);
});

// --- Links

test('admins get links to every user and group page', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->user()->create(['name' => 'Linked User']);
    $group = UserGroup::factory()->create(['name' => 'Linked Group']);

    $measure = Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-01']);
    $measure->users()->attach($user->id);
    $measure->groups()->attach($group->id);

    $this->actingAs($admin)->get('/radar/monitoring')
        ->assertSee('<a href="/users/' . $user->id . '">Linked User</a>', false)
        ->assertSee('<a href="/groups/' . $group->id . '">Linked Group</a>', false);
});

test('non-admins only get a link to their own user page', function () {
    $user = User::factory()->user()->create(['name' => 'Self User']);
    $other = User::factory()->user()->create(['name' => 'Other User']);
    $group = UserGroup::factory()->create(['name' => 'Some Group']);

    $measure = Measure::factory()->create(['status' => 0, 'plan_date' => '2026-10-01']);
    $measure->users()->attach([$user->id, $other->id]);
    $measure->groups()->attach($group->id);

    $this->actingAs($user)->get('/radar/monitoring')
        ->assertSee('<a href="/users/' . $user->id . '">Self User</a>', false)
        ->assertDontSee('href="/users/' . $other->id . '"', false)
        ->assertDontSee('href="/groups/' . $group->id . '"', false);
});

<?php

use App\Models\Control;
use App\Models\Domain;
use App\Models\Measure;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->user()->create();
    $this->other = User::factory()->user()->create();

    $this->ownedDomain = Domain::factory()->create(['title' => 'Owned Domain']);
    $ownedControl = Control::factory()->create(['domain_id' => $this->ownedDomain->id]);
    $ownedMeasure = Measure::factory()->create();
    $ownedMeasure->controls()->attach($ownedControl->id);
    $ownedMeasure->users()->attach($this->owner->id);

    $this->otherDomain = Domain::factory()->create(['title' => 'Other Domain']);
    $otherControl = Control::factory()->create(['domain_id' => $this->otherDomain->id]);
    $otherMeasure = Measure::factory()->create();
    $otherMeasure->controls()->attach($otherControl->id);
    $otherMeasure->users()->attach($this->other->id);
});

// In personal view every domain is listed; only the measure counter is scoped (#748)
function expectScopedCounts($response, int $ownedId, int $otherId): void
{
    $response->assertViewHas('domains', function ($domains) use ($ownedId, $otherId) {
        $counts = collect($domains)->pluck('measures_count', 'id');

        return (int) $counts[$ownedId] === 1 && (int) $counts[$otherId] === 0;
    });
}

test('admin sees all domains but only counts their own measures when group view is toggled off', function () {
    $admin = User::factory()->admin()->create();
    // Assign the admin to the "owned" measure instead of $this->owner
    Measure::whereHas('controls', fn ($q) => $q->where('domain_id', $this->ownedDomain->id))
        ->first()
        ->users()->attach($admin->id);

    // Default is "sees all data"; toggle it off
    $this->actingAs($admin)->get('/group/toggle');

    $response = $this->actingAs($admin)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertSee('Other Domain');
    expectScopedCounts($response, $this->ownedDomain->id, $this->otherDomain->id);
});

test('admin sees all domains by default (group view on)', function () {
    $admin = User::factory()->admin()->create();
    Measure::whereHas('controls', fn ($q) => $q->where('domain_id', $this->ownedDomain->id))
        ->first()
        ->users()->attach($admin->id);

    $response = $this->actingAs($admin)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertSee('Other Domain');
});

test('regular user sees all domains by default (group view on)', function () {
    $response = $this->actingAs($this->owner)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertSee('Other Domain');
});

test('regular user sees all domains but only counts their own measures when group view is toggled off', function () {
    $this->actingAs($this->owner)->get('/group/toggle');

    $response = $this->actingAs($this->owner)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertSee('Other Domain');
    expectScopedCounts($response, $this->ownedDomain->id, $this->otherDomain->id);
});

test('auditee stays filtered even with a forged group_view session value', function () {
    $auditee = User::factory()->auditee()->create();
    Measure::whereHas('controls', fn ($q) => $q->where('domain_id', $this->ownedDomain->id))
        ->first()
        ->users()->attach($auditee->id);

    session(['group_view' => true]);

    $response = $this->actingAs($auditee)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertSee('Other Domain');
    expectScopedCounts($response, $this->ownedDomain->id, $this->otherDomain->id);
});

test('mif-group icon is rendered for admins and users, not for auditees', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/domains')->assertSee('mif-group', false);

    $this->actingAs($this->owner)->get('/domains')->assertSee('mif-group', false);

    $auditee = User::factory()->auditee()->create();
    $this->actingAs($auditee)->get('/domains')->assertDontSee('mif-group', false);
});

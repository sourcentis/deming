<?php

use App\Models\RiskScoringConfig;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/risk/scoring')->assertRedirect('/login');
});

test('the former URL opens the classification tab of the settings page', function () {
    $this->actingAs($this->admin)->get('/risk/scoring')->assertRedirect('/config/parameters?tab=scoring');
});

test('admin can list scoring configs', function () {
    $this->actingAs($this->admin)->get('/config/parameters?tab=scoring')
        ->assertStatus(200)
        ->assertSee('/risk/scoring/create', false);
});

test('non-admin cannot list scoring configs', function () {
    $this->actingAs($this->user)->get('/config/parameters?tab=scoring')->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/risk/scoring/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/risk/scoring/create')->assertStatus(403);
});

test('admin can create a scoring config', function () {
    // RiskScoringConfigController validates arrays (not JSON strings)
    $payload = [
        'name'    => 'Custom ISO 27005',
        'formula' => 'probability_x_impact',
        'probability_levels' => [
            ['value' => 1, 'label' => 'Rare',   'description' => ''],
            ['value' => 2, 'label' => 'Likely',  'description' => ''],
        ],
        'impact_levels' => [
            ['value' => 1, 'label' => 'Low',  'description' => ''],
            ['value' => 2, 'label' => 'High', 'description' => ''],
        ],
        'risk_thresholds' => [
            ['level' => 'low',  'label' => 'Low',  'max' => 2,    'color' => '#27ae60'],
            ['level' => 'high', 'label' => 'High', 'max' => null, 'color' => '#e74c3c'],
        ],
    ];

    $this->actingAs($this->admin)
        ->post('/risk/scoring/store', $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('risk_scoring_configs', ['name' => 'Custom ISO 27005']);
});

test('admin can edit a scoring config', function () {
    $config = RiskScoringConfig::create([
        'name'               => 'Edit Test Config',
        'formula'            => 'probability_x_impact',
        'is_active'          => false,
        'probability_levels' => [['value' => 1, 'label' => 'Rare', 'description' => '']],
        'impact_levels'      => [['value' => 1, 'label' => 'Low',  'description' => '']],
        'risk_thresholds'    => [['level' => 'low', 'label' => 'Low', 'max' => null, 'color' => '#27ae60']],
    ]);
    $this->actingAs($this->admin)->get("/risk/scoring/{$config->id}/edit")->assertStatus(200);
});

test('admin can activate a scoring config', function () {
    $config = RiskScoringConfig::create([
        'name'               => 'Alternate Config',
        'formula'            => 'probability_x_impact',
        'is_active'          => false,
        'probability_levels' => [['value' => 1, 'label' => 'Rare', 'description' => '']],
        'impact_levels'      => [['value' => 1, 'label' => 'Low',  'description' => '']],
        'risk_thresholds'    => [['level' => 'low', 'label' => 'Low', 'max' => null, 'color' => '#27ae60']],
    ]);

    $this->actingAs($this->admin)
        ->post("/risk/scoring/{$config->id}/activate")
        ->assertRedirect();

    $this->assertDatabaseHas('risk_scoring_configs', ['id' => $config->id, 'is_active' => true]);
});

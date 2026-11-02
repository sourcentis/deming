<?php

use App\Models\AuditLog;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('the migration seeds ai.enabled to 0', function () {
    expect(DB::table('configuration')->where('name', 'ai.enabled')->value('value'))->toBe('0');
    expect(Configuration::bool('ai.enabled'))->toBeFalse();
});

test('an absent key returns the code default, then the given default', function () {
    DB::table('configuration')->where('name', 'ai.enabled')->delete();
    expect(Configuration::get('ai.enabled'))->toBe('0');
    // No default agent URL
    expect(Configuration::get('ai.base_url'))->toBeNull();
    expect(Configuration::get('ai.enabled', '1'))->toBe('0');
    expect(Configuration::get('unknown.key'))->toBeNull();
    expect(Configuration::get('unknown.key', 'fallback'))->toBe('fallback');
});

test('set then get returns the new value in the same request', function () {
    expect(Configuration::get('ai.base_url'))->toBeNull();

    Configuration::set('ai.base_url', 'https://a.example.com/v1');
    expect(Configuration::get('ai.base_url'))->toBe('https://a.example.com/v1');

    Configuration::set('ai.base_url', 'https://b.example.com/v1');
    expect(Configuration::get('ai.base_url'))->toBe('https://b.example.com/v1');
    expect(DB::table('configuration')->where('name', 'ai.base_url')->count())->toBe(1);
});

test('forget restores the code default', function () {
    Configuration::set('ai.enabled', '1');
    expect(Configuration::get('ai.enabled'))->toBe('1');

    Configuration::forget('ai.enabled');
    expect(Configuration::get('ai.enabled'))->toBe('0');
});

test('the prompts and the model are no longer settings', function () {
    expect(Configuration::get('ai.model'))->toBeNull()
        ->and(Configuration::get('ai.prompt_risk'))->toBeNull()
        ->and(file_exists(resource_path('ai')))->toBeFalse();
});

test('the migration removes the prompts and the model, logging custom prompt names only', function () {
    $migration = require database_path('migrations/2026_11_02_000002_remove_ai_prompts_and_model_from_configuration.php');
    $rows = [
        'ai.prompt_control' => 'SECRET custom prompt {{langue}}',
        'ai.prompt_measure' => "  \r\n ",
        'ai.prompt_risk' => 'Another SECRET prompt',
        'ai.model' => 'gpt-oss-120b',
        'ai.base_url' => 'https://agent.example.com/v1',
    ];
    foreach ($rows as $name => $value) {
        DB::table('configuration')->insert(['name' => $name, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
    }
    Log::spy();

    $migration->up();

    expect(DB::table('configuration')->where('name', 'like', 'ai.prompt_%')->exists())->toBeFalse()
        ->and(DB::table('configuration')->where('name', 'ai.model')->exists())->toBeFalse()
        ->and(DB::table('configuration')->where('name', 'ai.base_url')->value('value'))->toBe('https://agent.example.com/v1')
        ->and(DB::table('configuration')->where('name', 'ai.enabled')->exists())->toBeTrue();
    foreach (['ai.prompt_control', 'ai.prompt_risk'] as $name) {
        Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context === ['name' => $name]
            && ! str_contains($message, 'SECRET'))->once();
    }
    // Empty value: nothing customized
    Log::shouldHaveReceived('warning')->twice();

    // Nothing is recreated
    $migration->down();
    expect(DB::table('configuration')->where('name', 'like', 'ai.prompt_%')->exists())->toBeFalse();
});

test('secret values are encrypted at rest', function () {
    Configuration::set('ai.api_key', 'sk-secret-123');

    $raw = DB::table('configuration')->where('name', 'ai.api_key')->value('value');
    expect($raw)->not->toBe('sk-secret-123');
    expect(Configuration::get('ai.api_key'))->toBe('sk-secret-123');
});

test('a corrupted encrypted value returns null without exception', function () {
    DB::table('configuration')->insert(['name' => 'ai.api_key', 'value' => 'not-encrypted']);
    Configuration::clearCache();

    expect(Configuration::get('ai.api_key'))->toBeNull();
});

test('bool interprets common values', function () {
    $cases = ['1' => true, '0' => false, 'true' => true, 'false' => false];
    foreach ($cases as $value => $expected) {
        Configuration::set('ai.enabled', (string) $value);
        expect(Configuration::bool('ai.enabled'))->toBe($expected);
    }

    Configuration::set('ai.enabled', null);
    expect(Configuration::bool('ai.enabled'))->toBeFalse();
    expect(Configuration::bool('ai.enabled', true))->toBeTrue();
});

test('set and forget are audited with secrets masked', function () {
    Configuration::set('ai.api_key', 'sk-secret-123');
    Configuration::set('ai.base_url', 'https://llama3.example.com/v1');
    Configuration::forget('ai.base_url');

    $logs = AuditLog::where('subject_type', Configuration::class)->get();
    expect($logs)->toHaveCount(3);

    $json = $logs->pluck('properties')->toJson();
    expect($json)->toContain('***')
        ->and($json)->not->toContain('sk-secret-123')
        ->and($json)->toContain('llama3');
});

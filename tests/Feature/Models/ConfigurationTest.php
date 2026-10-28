<?php

use App\Models\AuditLog;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

test('the migration seeds ai.enabled to 0', function () {
    expect(DB::table('configuration')->where('name', 'ai.enabled')->value('value'))->toBe('0');
    expect(Configuration::bool('ai.enabled'))->toBeFalse();
});

test('an absent key returns the code default, then the given default', function () {
    expect(Configuration::get('ai.model'))->toBe('gpt-oss-120b');
    // No default agent URL
    expect(Configuration::get('ai.base_url'))->toBeNull();
    expect(Configuration::get('ai.model', 'other'))->toBe('gpt-oss-120b');
    expect(Configuration::get('unknown.key'))->toBeNull();
    expect(Configuration::get('unknown.key', 'fallback'))->toBe('fallback');
});

test('set then get returns the new value in the same request', function () {
    expect(Configuration::get('ai.model'))->toBe('gpt-oss-120b');

    Configuration::set('ai.model', 'llama3');
    expect(Configuration::get('ai.model'))->toBe('llama3');

    Configuration::set('ai.model', 'mistral');
    expect(Configuration::get('ai.model'))->toBe('mistral');
    expect(DB::table('configuration')->where('name', 'ai.model')->count())->toBe(1);
});

test('forget restores the default prompt file', function (string $type) {
    $file = resource_path("ai/prompt_{$type}.md");

    Configuration::set("ai.prompt_{$type}", 'custom prompt');
    expect(Configuration::get("ai.prompt_{$type}"))->toBe('custom prompt');

    Configuration::forget("ai.prompt_{$type}");
    expect(Configuration::get("ai.prompt_{$type}"))->toBe(file_get_contents($file));
})->with(['control', 'measure', 'action', 'risk']);

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
    Configuration::set('ai.model', 'llama3');
    Configuration::forget('ai.model');

    $logs = AuditLog::where('subject_type', Configuration::class)->get();
    expect($logs)->toHaveCount(3);

    $json = $logs->pluck('properties')->toJson();
    expect($json)->toContain('***')
        ->and($json)->not->toContain('sk-secret-123')
        ->and($json)->toContain('llama3');
});

<?php

use App\Models\Measure;
use App\Models\User;
use App\Models\UserGroup;

const XSS = '<script>alert(1)</script>';

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('the model and observations of a measure are escaped', function () {
    $measure = Measure::factory()->create(['model' => XSS, 'observations' => XSS]);

    $this->actingAs($this->admin)->get("/bob/show/{$measure->id}")
        ->assertStatus(200)
        ->assertDontSee(XSS, false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

test('markdown fields do not render raw html nor javascript links', function () {
    $measure = Measure::factory()->create([
        'objective' => '**bold** ' . XSS,
        'input' => '[link](javascript:alert(1))',
    ]);

    $this->actingAs($this->admin)->get("/bob/show/{$measure->id}")
        ->assertStatus(200)
        ->assertSee('<strong>bold</strong>', false)
        ->assertDontSee(XSS, false)
        ->assertDontSee('href="javascript:', false);
});

test('the description of a group is escaped', function () {
    $group = UserGroup::factory()->create(['description' => XSS]);

    $this->actingAs($this->admin)->get("/groups/{$group->id}")
        ->assertStatus(200)
        ->assertDontSee(XSS, false);
});

test('views only output raw html from trusted sources', function () {
    // {!! !!} autorisé : Markdown (Parsedown en mode sûr), traductions, chaînes constantes
    $allowed = '/\{!!\s*(\\\\Parsedown::instance\(\)->text\(|trans\(|\$measure->score ==|\(\(\$loop->first\))/';

    $offenders = [];
    foreach (\Illuminate\Support\Facades\File::allFiles(resource_path('views')) as $file) {
        foreach (explode("\n", $file->getContents()) as $n => $line) {
            if (preg_match_all('/\{!!.*?!!\}/', $line, $m)) {
                foreach ($m[0] as $raw) {
                    if (! preg_match($allowed, $raw)) {
                        $offenders[] = $file->getRelativePathname() . ':' . ($n + 1) . ' ' . trim($raw);
                    }
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

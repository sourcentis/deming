<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

function cspNonce(string $policy): ?string
{
    return preg_match("/'nonce-([^']+)'/", $policy, $m) ? $m[1] : null;
}

test('the report-only header is sent by default', function () {
    config(['csp.enabled' => true, 'csp.report_only' => true]);

    $response = $this->get('/login');

    $response->assertHeader('Content-Security-Policy-Report-Only');
    $response->assertHeaderMissing('Content-Security-Policy');
});

test('the enforced header is sent when report-only is disabled', function () {
    config(['csp.enabled' => true, 'csp.report_only' => false]);

    $response = $this->get('/login');

    $policy = $response->headers->get('Content-Security-Policy');
    expect($policy)
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("form-action 'self'")
        ->not->toContain('unsafe-eval');
    expect(cspNonce($policy))->not->toBeNull();
});

test('no header is sent when the policy is disabled', function () {
    config(['csp.enabled' => false]);

    $this->get('/login')
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

test('the nonce changes on every request and is set on inline scripts', function () {
    config(['csp.enabled' => true, 'csp.report_only' => false]);

    $first = $this->get('/login');
    $second = $this->get('/login');

    $nonce = cspNonce($first->headers->get('Content-Security-Policy'));
    expect($nonce)->not->toBe(cspNonce($second->headers->get('Content-Security-Policy')));

    $html = $first->getContent();
    preg_match_all('/<script\b[^>]*>/i', $html, $tags);
    expect($tags[0])->not->toBeEmpty();
    foreach ($tags[0] as $tag) {
        expect($tag)->toContain('nonce="' . $nonce . '"');
    }
});

test('the dashboard scripts carry the nonce', function () {
    config(['csp.enabled' => true, 'csp.report_only' => false]);

    $response = $this->actingAs(User::factory()->admin()->create())->get('/');

    $nonce = cspNonce($response->headers->get('Content-Security-Policy'));
    preg_match_all('/<script\b[^>]*>/i', $response->getContent(), $tags);
    foreach ($tags[0] as $tag) {
        expect($tag)->toContain('nonce="' . $nonce . '"');
    }
});

test('extra sources from the configuration are appended', function () {
    config([
        'csp.enabled' => true,
        'csp.report_only' => false,
        'csp.extra.img-src' => 'https://images.example.com',
        'csp.report_uri' => '/csp-report',
    ]);

    $policy = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($policy)
        ->toContain("img-src 'self' data: blob: https://images.example.com")
        ->toContain('report-uri /csp-report');
});

test('views contain no inline event handler nor script without nonce', function () {
    $offenders = [];
    foreach (File::allFiles(resource_path('views')) as $file) {
        $content = $file->getContents();
        if (preg_match('/\son[a-z]+\s*=\s*["\']/i', $content)) {
            $offenders[] = $file->getRelativePathname() . ' (inline handler)';
        }
        if (preg_match('/<script(?![^>]*(@nonce|\bsrc=))[^>]*>/i', $content)) {
            $offenders[] = $file->getRelativePathname() . ' (script without @nonce)';
        }
    }

    expect($offenders)->toBe([]);
});

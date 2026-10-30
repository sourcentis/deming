<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

const PARAMETER_TABS = ['documents', 'templates', 'scoring', 'ai', 'notifications'];

test('guest is redirected to login', function () {
    $this->get('/config/parameters')->assertRedirect('/login');
});

test('admin sees every tab on a single page', function () {
    $response = $this->actingAs($this->admin)->get('/config/parameters')->assertOk();

    $response->assertSee(trans('menu.configuration.parameters'));
    foreach (PARAMETER_TABS as $tab) {
        $response->assertSee('id="tab-' . $tab . '"', false);
    }
    // One form per tab, posting to its own controller
    $response->assertSee('action="/risk/scoring/', false)
        ->assertSee('action="/doc/config"', false)
        ->assertSee('action="/doc/template"', false)
        ->assertSee('action="/config/ai/save"', false)
        ->assertSee('action="/config/save"', false);
});

test('the tab given in the URL is the active one', function (string $tab) {
    $html = $this->actingAs($this->admin)->get("/config/parameters?tab={$tab}")->assertOk()->getContent();

    expect($html)->toContain('class="tab-pane fade show active" id="tab-' . $tab . '"');
})->with(PARAMETER_TABS);

test('an unknown tab opens the first one', function () {
    $html = $this->actingAs($this->admin)->get('/config/parameters?tab=evil')->assertOk()->getContent();

    expect($html)->toContain('class="tab-pane fade show active" id="tab-documents"');
});

test('non admins get 403', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())->get('/config/parameters')->assertForbidden();
})->with(['user', 'auditor', 'auditee', 'apiUser']);

test('former URLs open the matching tab', function (string $url, string $tab) {
    $this->actingAs($this->admin)->get($url)->assertRedirect("/config/parameters?tab={$tab}");
})->with([
    ['/risk/scoring', 'scoring'],
    ['/doc', 'documents'],
    ['/doc/config', 'documents'],
    ['/config/ai', 'ai'],
    ['/config', 'notifications'],
]);

test('saving the cleanup duration goes back to the documents tab', function () {
    $this->actingAs($this->admin)
        ->post('/doc/config', ['action' => 'test', 'duration' => '24'])
        ->assertRedirect('/config/parameters?tab=documents')
        ->assertSessionHas('messages');
});

test('the sidebar has a single Settings entry', function () {
    $html = $this->actingAs($this->admin)->get('/config/parameters')->getContent();

    expect($html)->toMatch('#<a href="/config/parameters" class="active">#')
        ->not->toContain('<a href="/risk/scoring"')
        ->not->toContain('<a href="/doc"')
        ->not->toContain('<a href="/config/ai"')
        ->not->toContain('<a href="/config"');
});

test('documents and templates are separate tabs', function () {
    $html = $this->actingAs($this->admin)->get('/config/parameters')->getContent();

    $documents = substr($html, strpos($html, 'id="tab-documents"'), strpos($html, 'id="tab-templates"') - strpos($html, 'id="tab-documents"'));
    $templates = substr($html, strpos($html, 'id="tab-templates"'), strpos($html, 'id="tab-scoring"') - strpos($html, 'id="tab-templates"'));

    expect($documents)->toContain('action="/doc/config"')->toContain('/doc/check')->not->toContain('action="/doc/template"')
        ->and($templates)->toContain('action="/doc/template"')->not->toContain('action="/doc/config"');
});

test('uploading a template goes back to the templates tab', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $file = \Illuminate\Http\UploadedFile::fake()->create('control.docx', 10);

    $this->actingAs($this->admin)
        ->post('/doc/template', ['template1' => $file])
        ->assertRedirect('/config/parameters?tab=templates');
});

test('tabs are ordered documents, templates, classification, AI, notifications', function () {
    $html = $this->actingAs($this->admin)->get('/config/parameters')->getContent();

    preg_match_all('/data-tab="([a-z]+)"/', $html, $matches);
    expect($matches[1])->toBe(PARAMETER_TABS);
});

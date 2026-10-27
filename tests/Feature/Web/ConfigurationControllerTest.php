<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/config')->assertRedirect('/login');
});

test('the former URL opens the notifications tab of the settings page', function () {
    $this->actingAs($this->admin)->get('/config')->assertRedirect('/config/parameters?tab=notifications');
});

test('admin can view configuration', function () {
    $this->actingAs($this->admin)->get('/config/parameters?tab=notifications')
        ->assertStatus(200)
        ->assertSee('action="/config/save"', false);
});

test('non-admin cannot view configuration', function () {
    $this->actingAs($this->user)->get('/config/parameters?tab=notifications')->assertStatus(403);
});

test('admin can save configuration and goes back to the notifications tab', function () {
    $this->actingAs($this->admin)
        ->post('/config/save', ['action' => 'save'])
        ->assertRedirect('/config/parameters?tab=notifications');
});

test('admin config save with cancel redirects to home', function () {
    $this->actingAs($this->admin)
        ->post('/config/save', ['action' => 'cancel'])
        ->assertRedirect('/');
});

test('non-admin cannot save configuration', function () {
    $this->actingAs($this->user)
        ->post('/config/save', [])
        ->assertStatus(403);
});

<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('the framework and scope fields can be cleared', function () {
    $html = $this->actingAs($this->admin)->get('/radar/domains')->assertOk()->getContent();

    // Select2 shows its clear button only with an empty option
    expect($html)->toMatch('#<select class="form-select select2" name="framework" id="framework">\s*<option value=""></option>#')
        ->toMatch('#<select class="form-select select2" name="scope" id="scope">\s*<option value=""></option>#')
        ->toContain("encodeURIComponent(this.value || 'none')");
});

test('clearing a field removes the filter kept in session', function (string $field) {
    $this->actingAs($this->admin)->get("/radar/domains?{$field}=ISO")->assertOk()->assertSessionHas($field, 'ISO');
    $this->actingAs($this->admin)->get("/radar/domains?{$field}=none")->assertOk()->assertSessionMissing($field);
})->with(['framework', 'scope']);

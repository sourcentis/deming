<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/users')->assertRedirect('/login');
});

test('admin can list users', function () {
    User::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/users')->assertStatus(200);
});

test('non-admin cannot list users', function () {
    $this->actingAs($this->user)->get('/users')->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/users/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/users/create')->assertStatus(403);
});

test('admin can create a user', function () {
    // UserController uses password1/password2 fields (not password/password_confirmation)
    $this->actingAs($this->admin)
        ->post('/users', [
            'login' => 'newuser',
            'name' => 'New User',
            'title' => 'Analyst',
            'email' => 'newuser@example.com',
            'password1' => 'Password123!',
            'password2' => 'Password123!',
            'role' => User::ROLE_AUDITOR,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['login' => 'newuser']);
});

test('non-admin cannot create a user', function () {
    $this->actingAs($this->user)
        ->post('/users', ['login' => 'test', 'name' => 'Test'])
        ->assertStatus(403);
});

test('admin can view any user', function () {
    $target = User::factory()->create();
    $this->actingAs($this->admin)->get("/users/{$target->id}")->assertStatus(200);
});

test('user can view their own profile', function () {
    $this->actingAs($this->user)->get("/users/{$this->user->id}")->assertStatus(200);
});

test('user cannot view another user', function () {
    $other = User::factory()->user()->create();
    $this->actingAs($this->user)->get("/users/{$other->id}")->assertStatus(403);
});

test('admin can edit a user', function () {
    $target = User::factory()->create();
    $this->actingAs($this->admin)->get("/users/{$target->id}/edit")->assertStatus(200);
});

test('user can edit their own profile', function () {
    $this->actingAs($this->user)->get("/users/{$this->user->id}/edit")->assertStatus(200);
});

test('admin can update a user', function () {
    $target = User::factory()->create();

    // update requires login, name, email, role and title (NOT NULL columns)
    $this->actingAs($this->admin)
        ->put("/users/{$target->id}", [
            'login' => $target->login,
            'name' => 'Updated Name',
            'email' => $target->email,
            'title' => 'Test Title',
            'role' => $target->role,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated Name']);
});

test('updating a user only changes pending measures and keeps performed ones', function () {
    $target = User::factory()->create();
    $done = \App\Models\Measure::factory()->done()->create();
    $kept = \App\Models\Measure::factory()->create();
    $removed = \App\Models\Measure::factory()->create();
    $added = \App\Models\Measure::factory()->create();
    $target->measures()->attach([$done->id, $kept->id, $removed->id]);

    $this->actingAs($this->admin)
        ->put("/users/{$target->id}", [
            'login' => $target->login,
            'name' => $target->name,
            'email' => $target->email,
            'title' => 'Test Title',
            'role' => $target->role,
            'controls' => [(string) $kept->id, (string) $added->id],
        ])
        ->assertRedirect();

    expect($target->measures()->pluck('measures.id')->sort()->values()->all())
        ->toBe(collect([$done->id, $kept->id, $added->id])->sort()->values()->all());
});

test('user page links each pending measure to itself', function () {
    // Offset the ids so that a measure id cannot match a control id by chance
    \App\Models\Measure::factory()->count(3)->create();
    $control = \App\Models\Control::factory()->create();
    $measure = \App\Models\Measure::factory()->create();
    $measure->controls()->attach($control->id);
    $measure->users()->attach($this->user->id);

    $this->actingAs($this->admin)
        ->get("/users/{$this->user->id}")
        ->assertStatus(200)
        ->assertSee("/alice/show/{$control->id}")
        ->assertSee("/bob/show/{$measure->id}")
        ->assertDontSee("/bob/show/{$control->id}\"", false);
});

test('user page accepts a measure without control', function () {
    $measure = \App\Models\Measure::factory()->create();
    $measure->users()->attach($this->user->id);

    $this->actingAs($this->admin)
        ->get("/users/{$this->user->id}")
        ->assertStatus(200)
        ->assertSee("/bob/show/{$measure->id}");
});

test('user page hides performed measures', function () {
    $done = \App\Models\Measure::factory()->done()->create(['name' => 'Performed measure']);
    $todo = \App\Models\Measure::factory()->create(['name' => 'Pending measure']);
    $this->user->measures()->attach([$done->id, $todo->id]);

    $this->actingAs($this->admin)
        ->get("/users/{$this->user->id}")
        ->assertSee('Pending measure')
        ->assertDontSee('Performed measure');
});

test('user page lists pending measures assigned through a group once', function () {
    $group = \App\Models\UserGroup::factory()->create(['name' => 'Audit team']);
    $group->users()->attach($this->user->id);

    $viaGroup = \App\Models\Measure::factory()->create(['name' => 'Group measure']);
    $both = \App\Models\Measure::factory()->create(['name' => 'Shared measure']);
    $doneViaGroup = \App\Models\Measure::factory()->done()->create(['name' => 'Performed group measure']);
    $group->measures()->attach([$viaGroup->id, $both->id, $doneViaGroup->id]);
    $this->user->measures()->attach($both->id);

    $assignments = $this->user->pendingMeasureAssignments();

    expect($assignments)->toHaveCount(2);
    expect($assignments->every(fn ($assignment) => collect($assignment['groups'])->pluck('id')->all() === [$group->id]))->toBeTrue();

    $this->actingAs($this->admin)
        ->get("/users/{$this->user->id}")
        ->assertSee('Group measure')
        ->assertSee('Shared measure')
        ->assertSee("/groups/{$group->id}")
        ->assertDontSee('Performed group measure');
});

test('control is viewable by an auditee assigned through a group', function () {
    $auditee = User::factory()->auditee()->create();
    $group = \App\Models\UserGroup::factory()->create();
    $group->users()->attach($auditee->id);
    $measure = \App\Models\Measure::factory()->create();
    $control = \App\Models\Control::factory()->create();
    $otherControl = \App\Models\Control::factory()->create();
    $measure->controls()->attach($control->id);
    $group->measures()->attach($measure->id);

    expect(\App\Models\Control::isViewableBy($auditee, $control->id))->toBeTrue();
    expect(\App\Models\Control::isViewableBy($auditee, $otherControl->id))->toBeFalse();
});

test('control is viewable by an auditee assigned directly', function () {
    $auditee = User::factory()->auditee()->create();
    $measure = \App\Models\Measure::factory()->create();
    $control = \App\Models\Control::factory()->create();
    $measure->controls()->attach($control->id);
    $measure->users()->attach($auditee->id);

    expect(\App\Models\Control::isViewableBy($auditee, $control->id))->toBeTrue();
});

test('admin can delete a user', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/users/{$target->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('admin cannot delete a user assigned to an action', function () {
    // Regression test: deleting a user still owning an action used to fail with
    // SQLSTATE[23000] Integrity constraint violation on the action_user pivot table.
    // The user must now be blocked from deleting with an explicit error message instead.
    $target = User::factory()->create();
    $action = \App\Models\Action::factory()->create();
    $action->owners()->attach($target->id);

    $this->actingAs($this->admin)
        ->from("/users/{$target->id}")
        ->delete("/users/{$target->id}")
        ->assertRedirect("/users/{$target->id}")
        ->assertSessionHasErrors('actions');

    $this->assertDatabaseHas('users', ['id' => $target->id]);
    $this->assertDatabaseHas('action_user', ['user_id' => $target->id]);
});

test('admin cannot delete a user belonging to a group', function () {
    $target = User::factory()->create();
    $group = \App\Models\UserGroup::factory()->create();
    $group->users()->attach($target->id);

    $this->actingAs($this->admin)
        ->from("/users/{$target->id}")
        ->delete("/users/{$target->id}")
        ->assertRedirect("/users/{$target->id}")
        ->assertSessionHasErrors('groups');

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('admin cannot delete a user assigned to a measure', function () {
    $target = User::factory()->create();
    $measure = \App\Models\Measure::factory()->create();
    $measure->users()->attach($target->id);

    $this->actingAs($this->admin)
        ->from("/users/{$target->id}")
        ->delete("/users/{$target->id}")
        ->assertRedirect("/users/{$target->id}")
        ->assertSessionHasErrors('measures');

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('admin sees one error message per type of link when deleting a user', function () {
    $target = User::factory()->create();

    $group = \App\Models\UserGroup::factory()->create();
    $group->users()->attach($target->id);

    $measure = \App\Models\Measure::factory()->create();
    $measure->users()->attach($target->id);

    $action = \App\Models\Action::factory()->create();
    $action->owners()->attach($target->id);

    $response = $this->actingAs($this->admin)
        ->from("/users/{$target->id}")
        ->delete("/users/{$target->id}");

    $response->assertSessionHasErrors(['groups', 'measures', 'actions']);
    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('non-admin cannot delete a user', function () {
    $target = User::factory()->create();
    $this->actingAs($this->user)->delete("/users/{$target->id}")->assertStatus(403);
});

test('admin can export users', function () {
    $this->actingAs($this->admin)->get('/export/users')->assertStatus(200);
});

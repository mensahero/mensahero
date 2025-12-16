<?php

use App\Actions\Teams\CreateRolePermission;
use App\Concerns\TeamSessionKeys;
use App\Mail\Team\TeamInvitationMail;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Mail::fake();
});

/**
 * Feature tests for Team Invitation Controller.
 */
pest()->group('feature', 'teams', 'invitations');

// Invite Via Email Tests
test('guests cannot send team invitations', function (): void {
    $this->post(route('teams.manage.invite'), [
        'email' => 'test@example.com',
        'role'  => 'some-role-id',
    ])->assertRedirect(route('login'));
});

test('user with permission can invite via email', function (): void {

    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    resolve(CreateRolePermission::class)->handle($owner->ownedTeams->first());

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $owner->ownedTeams->first()->id,
        ])
        ->post(route('teams.manage.invite'), [
            'email' => 'newmember@example.com',
            'role'  => $role->id,
        ])
        ->assertRedirect(route('teams.manage.index'));

    // Check invitation was created
    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email'   => 'newmember@example.com',
        'role_id' => $role->id,
    ]);

    // Check email was sent
    Mail::assertQueued(TeamInvitationMail::class, fn ($mail) => $mail->hasTo('newmember@example.com'));
});

test('invitation requires valid email', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'team_id' => $team->id,
        'name'    => 'member',
    ]);

    resolve(CreateRolePermission::class)->handle($owner->ownedTeams->first());

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $owner->ownedTeams->first()->id,
        ])->post(route('teams.manage.invite'), [
            'email' => 'invalid-email',
            'role'  => $role->id,
        ])->assertSessionHasErrors(['email']);
});

test('invitation requires valid role', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($owner->ownedTeams->first());

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $owner->ownedTeams->first()->id,
        ])->post(route('teams.manage.invite'), [
            'email' => 'test@example.com',
            'role'  => 'non-existent-role-id',
        ])->assertSessionHasErrors(['role']);
});

// Resend Invitation Tests
test('can resend invitation', function (): void {

    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'invited@example.com',
        'role_id' => $role->id,
    ]);

    resolve(CreateRolePermission::class)->handle($owner->ownedTeams->first());

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $owner->ownedTeams->first()->id,
        ])->post(route('teams.invitations.resend', $invitation->id), [
            'email' => 'invited@example.com',
            'role'  => $role->id,
        ])->assertRedirect();

    // Check email was sent
    Mail::assertQueued(TeamInvitationMail::class, fn ($mail) => $mail->hasTo('invited@example.com'));
});

test('resending invitation requires valid email', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'invited@example.com',
        'role_id' => $role->id,
    ]);

    resolve(CreateRolePermission::class)->handle($owner->ownedTeams->first());

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $owner->ownedTeams->first()->id,
        ])->post(route('teams.invitations.resend', $invitation->id), [
            'email' => 'invalid-email',
            'role'  => $role->id,
        ])->assertSessionHasErrors(['email']);
});

// Accept Invitation Tests
test('existing user can accept invitation and join team', function (): void {
    $owner = User::factory()->create();
    $invitedUser = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create([
            'email' => 'invited@example.com',
        ]);

    $team = $owner->ownedTeams()->create([
        'user_id' => $owner->id,
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'team_id' => $team->id,
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'team_id' => $team->id,
        'email'   => 'invited@example.com',
        'role_id' => $role->id,
    ]);

    $url = URL::temporarySignedRoute('teams.invitations.accept', now()->addMinutes(15), [
        'id' => $invitation->id,
    ]);

    resolve(CreateRolePermission::class)->handle($invitedUser->ownedTeams->first());

    actingAs($invitedUser)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $invitedUser->ownedTeams->first()->id,
        ])->get($url)
        ->assertRedirect(route('dashboard'));

    // Check user was added to team
    $this->assertDatabaseHas('team_user', [
        'team_id' => $team->id,
        'user_id' => $invitedUser->id,
        'role_id' => $role->id,
    ]);

    // Check invitation was deleted
    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('non-existent invitation returns error page', function (): void {
    $user = User::factory()->create();

    $team = $user->ownedTeams()->create([
        'user_id' => $user->id,
        'name'    => 'Test Team',
        'default' => true,
    ]);

    $url = URL::temporarySignedRoute('teams.invitations.accept', now()->addMinutes(15), [
        'id' => 'non-existent-id',
    ]);

    resolve(CreateRolePermission::class)->handle($user->ownedTeams->first());

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $user->ownedTeams->first()->id,
        ])->get($url)->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Exception')
            ->has('error')
        );
});

test('guest accepting invitation is redirected to create user page', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'user_id' => $owner->id,
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'team_id' => $team->id,
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'team_id' => $team->id,
        'email'   => 'newuser@example.com',
        'role_id' => $role->id,
    ]);

    $url = URL::temporarySignedRoute('teams.invitations.accept', now()->addMinutes(15), [
        'id' => $invitation->id,
    ]);

    resolve(CreateRolePermission::class)->handle($owner->ownedTeams->first());

    $response = $this->get($url);

    $response->assertRedirect();

    // Should redirect to create user page
    $this->assertTrue(str_contains((string) $response->headers->get('Location'), 'teams/user/invitation'));
});

test('logged in user with different email cannot accept invitation', function (): void {
    $owner = User::factory()->create();
    $wrongUser = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create([
            'email' => 'wrong@example.com',
        ]);

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'invited@example.com',
        'role_id' => $role->id,
    ]);

    $url = URL::temporarySignedRoute('teams.invitations.accept', now()->addMinutes(15), [
        'id' => $invitation->id,
    ]);

    resolve(CreateRolePermission::class)->handle($wrongUser->ownedTeams->first());

    $this->actingAs($wrongUser)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $wrongUser->ownedTeams->first()->id,
        ])->get($url)
        ->assertRedirect();
});

// Create User Page Tests
test('guest can view create user page from invitation', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'newuser@example.com',
        'role_id' => $role->id,
    ]);

    $url = URL::temporarySignedRoute('teams.invitations.create.user', now()->addMinutes(30), [
        'id' => $invitation->id,
    ]);

    $response = $this->get($url);

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('RegisterUserInvitation')
            ->has('email')
            ->has('invitationId')
            ->where('email', 'newuser@example.com')
            ->where('invitationId', $invitation->id)
        );
});

test('authenticated user cannot view create user page', function (): void {
    $user = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'team_id' => $team->id,
        'email'   => 'newuser@example.com',
        'role_id' => $role->id,
    ]);

    $url = URL::temporarySignedRoute('teams.invitations.create.user', now()->addMinutes(30), [
        'id' => $invitation->id,
    ]);

    resolve(CreateRolePermission::class)->handle($user->ownedTeams->first());

    $this->actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $user->ownedTeams->first()->id,
        ])->get($url)
        ->assertRedirect(route('dashboard'));
});

// Store User from Invitation Tests
test('guest can create account and accept invitation', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'newuser@example.com',
        'role_id' => $role->id,
    ]);

    $payload = [
        'name'                  => 'New User',
        'email'                 => 'newuser@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'team'                  => 'New User Team',
    ];

    $this->post(route('teams.invitations.store.user', $invitation->id), $payload)
        ->assertRedirect(route('dashboard'));

    // Check user was created
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'name'  => 'New User',
    ]);

    // Check user was added to the invited team
    $newUser = User::query()->where('email', 'newuser@example.com')->first();
    $this->assertDatabaseHas('team_user', [
        'team_id' => $team->id,
        'user_id' => $newUser->id,
        'role_id' => $role->id,
    ]);

    // Check personal team was created
    $this->assertDatabaseHas('teams', [
        'user_id' => $newUser->id,
        'name'    => 'New User Team',
        'default' => true,
    ]);

    // Check invitation was deleted
    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('authenticated user cannot create account from invitation', function (): void {
    $user = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'newuser@example.com',
        'role_id' => $role->id,
    ]);

    $payload = [
        'name'                  => 'New User',
        'email'                 => 'newuser@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'team'                  => 'New User Team',
    ];

    resolve(CreateRolePermission::class)->handle($user->ownedTeams->first());

    $this->actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $user->ownedTeams->first()->id,
        ])->post(route('teams.invitations.store.user', $invitation->id), $payload)
        ->assertRedirect(route('dashboard'));
});

test('creating user from invitation requires valid data', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'newuser@example.com',
        'role_id' => $role->id,
    ]);

    $payload = [
        'name'                  => '',
        'email'                 => 'invalid-email',
        'password'              => 'short',
        'password_confirmation' => 'different',
        'team'                  => '',
    ];

    $this->post(route('teams.invitations.store.user', $invitation->id), $payload)
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

test('unsigned invitation link is rejected', function (): void {
    $owner = User::factory()->create();

    $team = Team::query()->create([
        'user_id' => $owner->id,
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = Role::query()->create([
        'team_id' => $team->id,
        'name'    => 'member',
    ]);

    $invitation = TeamInvitation::query()->create([
        'team_id' => $team->id,
        'email'   => 'test@example.com',
        'role_id' => $role->id,
    ]);

    // Try to access without signature
    $this->get(route('teams.invitations.accept', ['id' => $invitation->id]))
        ->assertStatus(403);
});

test('expired invitation link is rejected', function (): void {
    $owner = User::factory()->create();

    $team = Team::query()->create([
        'user_id' => $owner->id,
        'name'    => 'Team',
        'default' => true,
    ]);

    $role = Role::query()->create([
        'team_id' => $team->id,
        'name'    => 'member',
    ]);

    $invitation = TeamInvitation::query()->create([
        'team_id' => $team->id,
        'email'   => 'test@example.com',
        'role_id' => $role->id,
    ]);

    // Create expired signed URL
    $url = URL::temporarySignedRoute('teams.invitations.accept', now()->subMinutes(1), [
        'id' => $invitation->id,
    ]);

    $this->get($url)
        ->assertStatus(403);
});

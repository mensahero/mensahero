<?php

use App\Actions\Teams\CreateRolePermission;
use App\Concerns\TeamSessionKeys;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

/**
 * Feature tests for Teams Controller.
 */
pest()->group('feature', 'teams');

// Index Tests
test('guests are redirected to login when visiting teams manage page', function (): void {
    $this->get(route('teams.manage.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view teams manage page with expected inertia props', function (): void {
    $user = User::factory()->create();

    // Create a team for the user
    $team = $user->ownedTeams()->create([
        'name'    => 'Test Team',
        'default' => true,
    ]);

    // Create roles for the team
    $team->role()->create([
        'name'    => 'admin',
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $response = $this->get(route('teams.manage.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teams')
            ->has('team')
            ->has('members')
            ->has('roles_permissions')
            ->has('deletePasswordRequired')
        );
});

// Create Team Tests
test('authenticated user can create a new team', function (): void {
    $user = User::factory()->create();

    // Create initial team
    $initialTeam = $user->ownedTeams()->create([
        'name'    => 'Initial Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($initialTeam);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $initialTeam->id,
        ]);

    $payload = [
        'name'    => 'New Team',
        'default' => false,
    ];

    $this->post(route('teams.manage.create.team'), $payload)
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('teams', [
        'user_id' => $user->id,
        'name'    => 'New Team',
        'default' => false,
    ]);
});

test('creating a team as default marks other teams as non-default', function (): void {
    $user = User::factory()->create();

    // Create initial default team
    $initialTeam = $user->ownedTeams()->create([
        'name'    => 'Initial Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($initialTeam);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $initialTeam->id,
        ]);

    $payload = [
        'name'    => 'New Default Team',
        'default' => true,
    ];

    $this->post(route('teams.manage.create.team'), $payload)
        ->assertRedirect(route('dashboard'));

    // Check that the initial team is no longer default
    $this->assertDatabaseHas('teams', [
        'id'      => $initialTeam->id,
        'default' => false,
    ]);

    // Check that the new team is default
    $this->assertDatabaseHas('teams', [
        'user_id' => $user->id,
        'name'    => 'New Default Team',
        'default' => true,
    ]);
});

// Update Team Name Tests
test('team owner can update team name', function (): void {
    $user = User::factory()->create();

    $team = $user->ownedTeams()->create([
        'name'    => 'Old Team Name',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $payload = [
        'name'    => 'Updated Team Name',
        'default' => true,
    ];

    $this->put(route('teams.manage.update.team.name', $team->id), $payload)
        ->assertRedirect(route('teams.manage.index'));

    $this->assertDatabaseHas('teams', [
        'id'   => $team->id,
        'name' => 'Updated Team Name',
    ]);
});

test('non-owner cannot update team name', function (): void {
    $owner = User::factory()->create();
    $nonOwner = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team Name',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($nonOwner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $payload = [
        'name'    => 'Hacked Team Name',
        'default' => true,
    ];

    $this->put(route('teams.manage.update.team.name', $team->id), $payload)
        ->assertRedirect(route('teams.manage.index'));

    // Team name should not be updated
    $this->assertDatabaseHas('teams', [
        'id'   => $team->id,
        'name' => 'Team Name',
    ]);
});

test('cannot remove default status from default team without making another default', function (): void {
    $user = User::factory()->create();

    $team = $user->ownedTeams()->create([
        'name'    => 'Default Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $payload = [
        'name'    => 'Default Team',
        'default' => false,
    ];

    $this->put(route('teams.manage.update.team.name', $team->id), $payload)
        ->assertRedirect(route('teams.manage.index'));

    // Team should still be default
    $this->assertDatabaseHas('teams', [
        'id'      => $team->id,
        'default' => true,
    ]);
});

// Delete Team Tests
test('team owner can delete team when they have multiple teams', function (): void {
    $user = User::factory()->create([
        'password' => null,
    ]);

    $team1 = $user->ownedTeams()->create([
        'name'    => 'Team 1',
        'default' => true,
    ]);

    $team2 = $user->ownedTeams()->create([
        'name'    => 'Team 2',
        'default' => false,
    ]);

    resolve(CreateRolePermission::class)->handle($team1);
    resolve(CreateRolePermission::class)->handle($team2);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team1->id,
        ]);

    $this->delete(route('teams.manage.destroy.team'))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('teams', [
        'id' => $team1->id,
    ]);
});

test('cannot delete team if user has only one owned team', function (): void {
    $user = User::factory()->create();

    $team = $user->ownedTeams()->create([
        'name'    => 'Only Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->delete(route('teams.manage.destroy.team'))
        ->assertRedirect(route('teams.manage.index'));

    // Team should still exist
    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
    ]);
});

test('non-owner cannot delete team', function (): void {
    $owner = User::factory()->create();
    $nonOwner = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($nonOwner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->delete(route('teams.manage.destroy.team'))
        ->assertRedirect(route('teams.manage.index'));

    // Team should still exist
    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
    ]);
});

test('deleting team requires password confirmation when user has password', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $team1 = $user->ownedTeams()->create([
        'name'    => 'Team 1',
        'default' => true,
    ]);

    $team2 = $user->ownedTeams()->create([
        'name'    => 'Team 2',
        'default' => false,
    ]);

    resolve(CreateRolePermission::class)->handle($team1);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team1->id,
        ]);

    // Without password
    $this->delete(route('teams.manage.destroy.team'))
        ->assertSessionHasErrors(['current_password']);

    // With correct password
    $this->delete(route('teams.manage.destroy.team'), [
        'current_password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('teams', [
        'id' => $team1->id,
    ]);
});

// Remove Team Member Tests
test('user with permission can remove team member', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    // Create admin role
    $role = $team->role()->create([
        'name'    => 'admin',
    ]);

    // Add member to team
    $team->users()->attach($member->id, ['role_id' => $role->id]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->delete(route('teams.manage.remove.team.member', $member->id), [
        'isMember' => true,
    ])->assertRedirect(route('teams.manage.index'));

    // Member should be removed
    $this->assertDatabaseMissing('team_user', [
        'team_id' => $team->id,
        'user_id' => $member->id,
    ]);
});

test('user cannot remove themselves from team', function (): void {
    $user = User::factory()->create();
    $member = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();

    $team = $user->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    $team->users()->attach($member->id, ['role_id' => $team->role()->first()->id]);

    actingAs($member)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->delete(route('teams.manage.remove.team.member', $member->id), [
        'isMember' => true,
    ])->assertRedirect();
});

test('can revoke team invitation', function (): void {
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

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->delete(route('teams.manage.remove.team.member', $invitation->id), [
        'isMember' => false,
    ])->assertRedirect(route('teams.manage.index'));

    // Invitation should be deleted
    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

// Update Team Member Role Tests
test('can update team member role', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()
        ->has(Team::factory()->state(fn (): array => ['default' => true]), 'ownedTeams')
        ->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $adminRole = $team->role()->create([
        'name'    => 'admin',
    ]);

    $memberRole = $team->role()->create([
        'team_id' => $team->id,
        'name'    => 'member',
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    // Add member to team with member role
    $team->users()->attach($member->id, ['role_id' => $memberRole->id]);

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->patch(route('teams.manage.update.team.member.role', $member->id), [
        'team_id'  => $team->id,
        'role'     => $adminRole->id,
        'isMember' => true,
    ])->assertRedirect(route('teams.manage.index'));

    // Member role should be updated
    $this->assertDatabaseHas('team_user', [
        'team_id' => $team->id,
        'user_id' => $member->id,
        'role_id' => $adminRole->id,
    ]);
});

test('can update invitation role', function (): void {
    $owner = User::factory()->create();

    $team = $owner->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    $adminRole = $team->role()->create([
        'name'    => 'admin',
    ]);

    $memberRole = $team->role()->create([
        'name'    => 'member',
    ]);

    $invitation = $team->teamInvitations()->create([
        'email'   => 'invited@example.com',
        'role_id' => $memberRole->id,
    ]);

    actingAs($owner)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $this->patch(route('teams.manage.update.team.member.role', $invitation->id), [
        'team_id'  => $team->id,
        'role'     => $adminRole->id,
        'isMember' => false,
    ])->assertRedirect(route('teams.manage.index'));

    // Invitation role should be updated
    $this->assertDatabaseHas('team_invitations', [
        'id'      => $invitation->id,
        'role_id' => $adminRole->id,
    ]);
});

// API Endpoint Tests
test('authenticated user can get team roles', function (): void {
    $user = User::factory()->create();

    $team = $user->ownedTeams()->create([
        'name'    => 'Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($user)
        ->withoutMiddleware(HandleInertiaRequests::class)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $response = $this->get(route('teams.getTeamRoles'));

    $response->assertOk()
        ->assertJsonStructure([
            'roles' => [
                '*' => ['uuid', 'label', 'description'],
            ],
        ]);
});

test('authenticated user can get all teams', function (): void {
    $user = User::factory()->create();

    $team = $user->ownedTeams()->create([
        'name'    => 'Team 1',
        'default' => true,
    ]);

    $user->ownedTeams()->create([
        'name'    => 'Team 2',
        'default' => false,
    ]);

    actingAs($user)
        ->withoutMiddleware(HandleInertiaRequests::class)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ]);

    $response = $this->get(route('teams.getAllTeams'));

    $response->assertOk()
        ->assertJsonStructure([
            'teams',
        ]);
});

test('authenticated user can get team menus', function (): void {
    $user = User::factory()->create();

    Team::query()->create([
        'user_id' => $user->id,
        'name'    => 'Team 1',
        'default' => true,
    ]);

    actingAs($user)
        ->withoutMiddleware(HandleInertiaRequests::class);

    $response = $this->get(route('teams.getTeamMenu'));

    $response->assertOk()
        ->assertJsonIsArray();
});

test('authenticated user can get current team', function (): void {
    $user = User::factory()->create();

    $team = Team::query()->create([
        'user_id' => $user->id,
        'name'    => 'Current Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($user->ownedTeams->first());

    session([TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id]);

    actingAs($user)
        ->withoutMiddleware(HandleInertiaRequests::class);

    $response = $this->get(route('teams.getCurrentTeam'));

    $response->assertOk()
        ->assertJsonStructure([
            'current_team',
        ]);
});

test('authenticated user can switch current team', function (): void {
    $user = User::factory()->create();

    $team1 = $user->ownedTeams()->create([
        'name'    => 'Team 1',
        'default' => true,
    ]);

    $team2 = $user->ownedTeams()->create([
        'name'    => 'Team 2',
        'default' => false,
    ]);

    resolve(CreateRolePermission::class)->handle($team1);
    resolve(CreateRolePermission::class)->handle($team2);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team1->id,
        ])
        ->withoutMiddleware(HandleInertiaRequests::class);

    $response = $this->post(route('teams.switchTeam'), [
        'team' => $team2->id,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Team switched successfully',
        ]);
});

test('cannot switch to non-existent team', function (): void {
    $user = User::factory()->create();

    $team = Team::query()->create([
        'user_id' => $user->id,
        'name'    => 'Team',
        'default' => true,
    ]);

    resolve(CreateRolePermission::class)->handle($team);

    actingAs($user)
        ->withSession([
            TeamSessionKeys::CURRENT_TEAM_ID->key() => $team->id,
        ])
        ->withoutMiddleware(HandleInertiaRequests::class);

    $response = $this->post(route('teams.switchTeam'), [
        'team' => 'non-existent-id',
    ]);

    $response->assertSessionHasErrors(['team']);
});

<?php

use App\Models\Contacts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Feature tests for Contacts module (Inertia controller + routes).
 */
pest()->group('feature');

test('guests are redirected to login when visiting contacts', function (): void {
    $this->get(route('contacts.create'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view contacts page and receives expected inertia props', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $response = $this->get(route('contacts.create'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contacts')
            ->has('contacts')
            ->has('contactsCount')
            ->has('sourceTypes')
            ->has('countryCodes')
        );
});

test('user can create a contact and mobile is stored in E164 format', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $payload = [
        'name'         => 'Juan Dela Cruz',
        // Local PH mobile; will be formatted to +63 E.164 by controller
        'mobile'       => '09123456789',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ];

    $this->post(route('contacts.store'), $payload)
        ->assertRedirect(route('contacts.create'));

    // Controller formats to E.164, ensure it is persisted and linked to the authenticated user
    $this->assertDatabaseHas('contacts', [
        'user_id'      => $user->id,
        'name'         => 'Juan Dela Cruz',
        'mobile'       => '+639123456789',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);
});

test('creating a duplicate mobile returns validation error', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    // Seed an existing contact with the formatted mobile
    Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Existing',
        'mobile'       => '+639123456789',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    $payload = [
        'name'         => 'Another',
        'mobile'       => '09123456789',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ];

    $this->post(route('contacts.store'), $payload)
        ->assertSessionHasErrors(['mobile']);
});

test('user can delete selected contacts', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $a = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Alpha',
        'mobile'       => '+639111111111',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);
    $b = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Beta',
        'mobile'       => '+639222222222',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    $this->post(route('contacts.destroy'), [
        'ids' => [$a->id, $b->id],
    ])->assertRedirect(route('contacts.create'));

    $this->assertDatabaseMissing('contacts', ['id' => $a->id]);
    $this->assertDatabaseMissing('contacts', ['id' => $b->id]);
});

test('contacts can be searched by name or mobile via query string', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    // Seed contacts for the authenticated user
    $alpha = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Alpha Contact',
        'mobile'       => '+639111111111',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);
    $beta = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Beta Person',
        'mobile'       => '+639222222222',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    // Same search term for another user to ensure global scope is respected
    $otherUser = User::factory()->create();
    Contacts::query()->create([
        'user_id'      => $otherUser->id,
        'name'         => 'Alpha Stranger',
        'mobile'       => '+639333333333',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    // Search by name should return only matching records for the signed-in user
    $this->get(route('contacts.create', ['search' => 'Alpha']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contacts')
            // Ensure contacts collection exists and only one result is returned
            ->has('contacts.data', 1)
            // Validate that the single result is the Alpha Contact of the authenticated user
            ->where('contacts.data.0.name', 'Alpha Contact')
        );

    // Search by a value that yields no matches should return empty data
    $this->get(route('contacts.create', ['search' => 'Nope']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contacts')
            ->has('contacts.data', 0)
        );

    // Search by mobile should also work
    $this->get(route('contacts.create', ['search' => substr($beta->mobile, -4)]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contacts')
            ->has('contacts.data', 1)
            ->where('contacts.data.0.mobile', $beta->mobile)
        );
});

test('user can update an existing contact', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    // Create an existing contact
    $contact = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Original Name',
        'mobile'       => '+639111111111',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    // Update the contact with new data
    $payload = [
        'name'         => 'Updated Name',
        'mobile'       => '+639555555555',
        'country_code' => 'PH',
        'source'       => 'CRM',
    ];

    $this->put(route('contacts.update', $contact->id), $payload)
        ->assertRedirect(route('contacts.create'));

    // Verify the contact was updated
    $this->assertDatabaseHas('contacts', [
        'id'           => $contact->id,
        'user_id'      => $user->id,
        'name'         => 'Updated Name',
        'mobile'       => '+639555555555',
        'country_code' => 'PH',
        'source'       => 'CRM',
    ]);

    // Verify old values are no longer present
    $this->assertDatabaseMissing('contacts', [
        'id'   => $contact->id,
        'name' => 'Original Name',
    ]);
});

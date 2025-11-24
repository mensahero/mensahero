<?php

use App\Models\Contacts;
use App\Models\User;

use function Pest\Laravel\actingAs;

pest()->group('browser');

test('contacts page shows empty state when there are no contacts', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    visit(route('contacts.create'))
        ->assertSee('Contacts')
        ->assertSee('No Contacts found')
        ->assertSee("It looks like you haven't added any contact. Create one to get started.")
        ->assertSee('Add Contact')
        ->assertSee('Refresh')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('contacts page renders table and search when contacts exist', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Alpha Contact',
        'mobile'       => '+639111111111',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    visit(route('contacts.create'))
        ->assertSee('Contacts')
        ->assertSee('Alpha Contact')
        ->assertDontSee('No Contacts found')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('contacts search input exists and can be interacted with', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Bravo Contact',
        'mobile'       => '+639222222222',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    visit(route('contacts.create'))
        ->type('#search-contacts', 'Bravo')
        ->assertSee('Bravo Contact')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('user can add a contact through the modal form', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    visit(route('contacts.create'))
        ->assertSee('Add Contact')
        ->click('Add Contact')
        ->assertSee('Add a new contact to your address book')
        ->fill('name', 'Charlie Contact')
        ->fill('mobile', '09333333333')
        ->click('Select your code')
        ->click('PH')
        ->click('Select source')
        ->click('Phone')
        ->click('@create-contact-button')
        ->assertSee('Charlie Contact')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Contacts::query()->where('user_id', $user->id)->where('name', 'Charlie Contact')->exists())->toBeTrue();
});

test('user can delete a contact', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Delta Contact',
        'mobile'       => '+639444444444',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    visit(route('contacts.create'))
        ->assertSee('Delta Contact')
        ->click('@contact-actions-dropdown-trigger')
        ->assertSee('Delete Contact')
        ->click('Delete Contact')
        ->assertSee('Are you sure?')
        ->click('@modal-delete-record-button')
        ->assertDontSee('Delta Contact')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Contacts::query()->where('id', $contact->id)->exists())->toBeFalse();
});

test('user can update a contact through the edit modal form', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contacts::query()->create([
        'user_id'      => $user->id,
        'name'         => 'Echo Contact',
        'mobile'       => '+639555555555',
        'country_code' => 'PH',
        'source'       => 'Phone',
    ]);

    visit(route('contacts.create'))
        ->assertSee('Echo Contact')
        ->click('@contact-actions-dropdown-trigger')
        ->assertSee('Edit Contact')
        ->click('Edit Contact')
        ->assertSee('Edit an existing contact in your address book')
        ->fill('name', 'Updated Echo Contact')
        ->fill('mobile', '09666666666')
        ->click('@update-contact-button')
        ->assertSee('Updated Echo Contact')
        ->assertNoJavaScriptErrors();

    $contact->refresh();
    expect($contact->name)->toBe('Updated Echo Contact')
        ->and($contact->mobile)->toBe('+639666666666');
});

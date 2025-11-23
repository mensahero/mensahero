<?php

namespace App\Http\Controllers\Modules;

use App\Actions\Modules\Contacts\CreateContacts;
use App\Actions\Modules\Contacts\DeleteContacts;
use App\Concerns\ContactSources;
use App\Concerns\MobileCountryCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Module\ContactsRequest;
use App\Http\Resources\Module\ContactResource;
use App\Models\Contacts;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class ContactsController extends Controller
{
    public function __construct(private readonly CreateContacts $createContacts, private readonly DeleteContacts $deleteContacts) {}

    public function create(Request $request): InertiaResponse
    {
        $perPage = $request->input('per_page', 25);

        if (! in_array($perPage, [5, 10, 25, 50, 100])) {
            $perPage = 25;
        }

        $contacts = Contacts::query()
            ->latest()
            ->searchByQueryString()
            ->filterByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Contacts', [
            'contacts'      => ContactResource::collection($contacts),
            'contactsCount' => Contacts::query()->count(),
            'sourceTypes'   => ContactSources::cases(),
            'countryCodes'  => MobileCountryCode::cases(),
        ]);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function store(ContactsRequest $request): RedirectResponse
    {

        $formattedMobile = new PhoneNumber($request->mobile, "{$request->country_code}")->formatE164();

        if (Contacts::query()->where('mobile', $formattedMobile)->exists()) {
            throw ValidationException::withMessages([
                'mobile' => 'The mobile number has already been taken.',
            ]);
        }

        $this->createContacts->handle([
            ...$request->validated(),
            'mobile'  => $formattedMobile,
            'user_id' => auth()->user()->id,
        ]);

        InertiaNotification::make()
            ->success()
            ->title('Contact created')
            ->message('The contact has been created successfully.')
            ->send();

        return to_route('contacts.create');
    }

    /**
     * @throws Exception
     */
    public function update(ContactsRequest $request, string $id): RedirectResponse
    {
        $contact = Contacts::query()->findOrFail($id);

        $contact->update($request->validated());

        $contact->save();

        InertiaNotification::make()
            ->success()
            ->title('Contact updated')
            ->message('The contact has been updated successfully.')
            ->send();

        return to_route('contacts.create');
    }

    /**
     * @throws Throwable
     */
    public function destroy(Request $request): RedirectResponse
    {

        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['required', 'exists:contacts,id'],
        ]);

        $this->deleteContacts->handle($request->ids);

        InertiaNotification::make()
            ->success()
            ->title('Contact deleted')
            ->message('The contact has been deleted successfully.')
            ->send();

        return to_route('contacts.create');

    }

    public function enums(): void
    {
        Inertia::merge([
            'sourceTypes'  => ContactSources::cases(),
            'countryCodes' => MobileCountryCode::cases(),
        ]);
    }
}

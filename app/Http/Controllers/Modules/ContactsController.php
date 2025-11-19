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
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class ContactsController extends Controller
{
    public function __construct(private readonly CreateContacts $createContacts, private readonly DeleteContacts $deleteContacts) {}

    public function create(Request $request): InertiaResponse
    {
        $perPage = $request->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $contacts = Contacts::query()
            ->searchByQueryString()
            ->filterByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Contacts', [
            'contacts'      => Inertia::optional(fn () => ContactResource::collection($contacts)),
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

        $this->createContacts->handle([
            ...$request->validated(),
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
}

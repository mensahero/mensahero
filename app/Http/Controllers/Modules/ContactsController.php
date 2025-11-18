<?php

namespace App\Http\Controllers\Modules;

use App\Concerns\ContactSources;
use App\Concerns\MobileCountryCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Module\ContactResource;
use App\Models\Contacts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ContactsController extends Controller
{
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

    public function store(): RedirectResponse
    {
        return to_route('contacts.create');
    }
}

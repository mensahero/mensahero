<?php

namespace App\Http\Controllers\Modules;

use App\Concerns\ContactSources;
use App\Concerns\MobileCountryCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Module\ContactResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ContactsController extends Controller
{
    public function create(): InertiaResponse
    {
        $perPage = request()->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $contacts = auth()->user()->contacts()
            ->searchByQueryString()
            ->filterByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Contacts', [
            'contacts'      => Inertia::optional(fn () => ContactResource::collection($contacts)),
            'contactsCount' => auth()->user()->contacts()->count(),
            'sourceTypes'   => ContactSources::cases(),
            'countryCodes'  => MobileCountryCode::cases(),
        ]);
    }

    public function store(): RedirectResponse
    {
        return to_route('contacts.create');
    }
}

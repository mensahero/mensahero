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
        return Inertia::render('Contacts', [
            'contacts'      => Inertia::optional(fn () => ContactResource::collection(auth()->user()->contacts()->paginate(10))),
            'sourceTypes'   => ContactSources::cases(),
            'countryCodes'  => MobileCountryCode::cases(),
        ]);
    }

    public function store(): RedirectResponse
    {
        return to_route('contacts.create');
    }
}

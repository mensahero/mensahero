<?php

namespace App\Actions\Modules\Contacts;

use App\Models\Contacts;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateContacts
{
    /**
     * @throws Throwable
     */
    public function handle(array $attributes): Contacts
    {
        return DB::transaction(fn () => Contacts::query()->create($attributes));
    }
}

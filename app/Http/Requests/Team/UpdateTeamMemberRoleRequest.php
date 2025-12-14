<?php

namespace App\Http\Requests\Team;

use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Services\InertiaNotification;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamMemberRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role'           => ['required', 'exists:roles,id'],
            'isMember'       => ['required', 'boolean:true,false'],
        ];
    }

    public function ensureIsNotOwnRecord(): bool
    {
        if ((int) $this->route('id') === auth()->user()->id) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You cannot update your own role. Please contact the team owner to update your role.')
                ->send();

            return false;
        }

        return true;
    }

    public function ensureHavePermission(): bool
    {
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
        if (! $this->user()->hasTeamPermission($team, 'team:update')) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You don\'t have permission to perform this action.')
                ->send();

            return false;
        }

        return true;
    }
}

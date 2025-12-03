@component('mail::message')
# Hi,

{{ __('You have been invited to join the :team team!', ['team' => $teamInvitation->team->name]) }}

{{ __('If you do not have an account, you may create one by clicking the button below and after creating an account, you will automatically accept the team invitation. ') }}


@component('mail::button', ['url' => $actionUrl ])
    {{ __('Accept Invitation') }}
@endcomponent

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent

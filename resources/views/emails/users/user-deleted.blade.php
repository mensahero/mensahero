@component('mail::message')
## Hi {{$user->name}},

This is to confirm that your **_{{config('app.name')}}_** account has been permanently deleted. All of your personal information, preferences, and associated data have been removed from our system in accordance with our data retention and privacy policies.

Please note that this action is irreversible, and your account cannot be recovered.
If you wish to use our services again in the future, you’ll need to create a new account.

Thank you for being part of our community.
We wish you all the best moving forward.

> **_"Hanggang sa muli, mula sa Mensahero."_**


Best regards,<br>
{{ config('app.name') }}
@endcomponent

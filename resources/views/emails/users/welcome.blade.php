@component('mail::message')
## Hello {{ $user->name  }},

We’re thrilled to have you on board! Mensahero helps you centralize and streamline your messaging — from OTPs to alerts — all in one reliable platform.

Here’s what you can do next:

- 📱 <strong>Install the Mensahero Mobile App</strong> — turn your Android phone into a reliable SMS gateway.
- 📤 Send and receive SMS messages effortlessly.
- 📊 Monitor message delivery, retries, and logs.
- ⚙️ Integrate Mensahero with your CMS, CRM apps or other clients.

@component('mail::button', ['url' => route('login')])
Go to Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;
use SocialiteProviders\Google\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class SocialSignOnServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('google', Provider::class);
        });

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('zoho', \SocialiteProviders\Zoho\Provider::class);
        });

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('zoom', \SocialiteProviders\Zoom\Provider::class);
        });
    }
}

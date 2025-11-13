<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can authenticate using Zoho Account', function (): void {

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);

    $abstractUser->shouldReceive('getId')
        ->andReturn(1234567890)
        ->shouldReceive('getEmail')
        ->andReturn('devfake@mail.com')
        ->shouldReceive('getNickname')
        ->andReturn('Pseudo')
        ->shouldReceive('getName')
        ->andReturn('Mensahero')
        ->shouldReceive('getAvatar')
        ->andReturn('https://en.gravatar.com/userimage');

    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')
        ->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('zoho')->andReturn($provider);

    $response = $this->get(route('sso.callback', 'zoho'));

    $response->assertRedirect(route('dashboard'));
});

test('user can authenticate using Zoom Account', function (): void {

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);

    $abstractUser->shouldReceive('getId')
        ->andReturn(1234567890)
        ->shouldReceive('getEmail')
        ->andReturn('devfake@mail.com')
        ->shouldReceive('getNickname')
        ->andReturn('Pseudo')
        ->shouldReceive('getName')
        ->andReturn('Mensahero')
        ->shouldReceive('getAvatar')
        ->andReturn('https://en.gravatar.com/userimage');

    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')
        ->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('zoom')->andReturn($provider);

    $response = $this->get(route('sso.callback', 'zoom'));

    $response->assertRedirect(route('dashboard'));
});

test('user can authenticate using Google Account', function (): void {

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);

    $abstractUser->shouldReceive('getId')
        ->andReturn(1234567890)
        ->shouldReceive('getEmail')
        ->andReturn('devfake@gmail.com')
        ->shouldReceive('getNickname')
        ->andReturn('Pseudo')
        ->shouldReceive('getName')
        ->andReturn('Mensahero')
        ->shouldReceive('getAvatar')
        ->andReturn('https://en.gravatar.com/userimage');

    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')
        ->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('sso.callback', 'google'));

    $response->assertRedirect(route('dashboard'));
});

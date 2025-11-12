<?php

pest()->group('browser');

test('welcome screen can be rendered', function (): void {
    visit('/')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors()
        ->assertSee('Mensahero is completely open-source and free to use.')
        ->assertSee('Login')
        ->assertSee('Get Started');
});

test('guests can browse to register page from welcome page', function (): void {
    visit(route('home'))
        ->click('Get Started')
        ->assertUrlIs(route('register'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors()
        ->assertSee('Create an account')
        ->assertSee('Enter your details below to create your account');
});

test('guests can browse to login page from welcome page', function (): void {
    visit(route('home'))
        ->click('Login')
        ->assertUrlIs(route('login'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors()
        ->assertSee('Login')
        ->assertSee('Enter your credentials to access your account.');
});

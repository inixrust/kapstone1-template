<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Filament\Auth\Pages\Login;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('seeds the three demo accounts', function () {
    foreach (UserSeeder::DEMO_USERS as $demoUser) {
        expect(User::where('email', $demoUser['email'])->exists())->toBeTrue();
    }
});

it('allows each demo account to log in', function (string $email) {
    livewire(Login::class)
        ->fillForm([
            'email' => $email,
            'password' => UserSeeder::DEMO_PASSWORD,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs(User::where('email', $email)->first());
})->with([
    'staff@demo.com',
    'manager@demo.com',
    'admin@demo.com',
]);

it('rejects an invalid password', function () {
    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@demo.com',
            'password' => 'salah-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

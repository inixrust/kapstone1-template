<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Filament\Auth\Pages\Login;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Livewire\livewire;

it('ignores mass assignment of current_stock on create', function () {
    $product = Product::factory()->create();

    // simulasikan payload jahat yang menyisipkan current_stock
    $product->fill(['current_stock' => 99999, 'name' => 'Diubah']);
    $product->save();

    expect($product->refresh()->name)->toBe('Diubah')
        ->and((int) $product->current_stock)->not->toBe(99999);
});

it('only changes current_stock through stock movements', function () {
    $product = Product::factory()->create(['current_stock' => 0]);

    // update biasa tidak boleh menyentuh current_stock
    $product->update(['name' => 'Nama Baru', 'current_stock' => 500]);
    expect((int) $product->refresh()->current_stock)->toBe(0);

    // hanya movement yang mengubahnya
    StockMovement::factory()->for($product)->in(25)->create();
    expect((int) $product->refresh()->current_stock)->toBe(25);
});

it('blocks staff from deleting products even when bypassing the UI', function () {
    $staff = userWithRole('staff');
    $product = Product::factory()->create();

    expect($staff->can('delete', $product))->toBeFalse()
        ->and($staff->can('forceDelete', $product))->toBeFalse();
});

it('blocks staff from creating an adjustment directly on the model', function () {
    $staff = userWithRole('staff');
    $this->actingAs($staff);
    $product = Product::factory()->create();

    expect(fn () => StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'qty' => 10,
    ]))->toThrow(AuthorizationException::class);
});

it('rate limits repeated failed login attempts', function () {
    $user = userWithRole('staff');

    for ($i = 0; $i < 5; $i++) {
        livewire(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
            ->call('authenticate');
    }

    // percobaan ke-6 harus terkena rate limit (notifikasi throttle)
    livewire(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
        ->call('authenticate')
        ->assertNotified();

    $this->assertGuest();
});

<?php

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;

use function Pest\Livewire\livewire;

it('assigns the expected permission tiers', function () {
    $staff = userWithRole('staff');
    $manager = userWithRole('manager');
    $admin = userWithRole('admin');

    // staff: boleh lihat & input, TANPA delete
    expect($staff->can('ViewAny:Product'))->toBeTrue()
        ->and($staff->can('Create:Product'))->toBeTrue()
        ->and($staff->can('Update:Product'))->toBeTrue()
        ->and($staff->can('Delete:Product'))->toBeFalse()
        ->and($staff->can('DeleteAny:Product'))->toBeFalse();

    // manager: kelola penuh KECUALI hapus permanen & kelola role
    expect($manager->can('Delete:Product'))->toBeTrue()
        ->and($manager->can('ForceDelete:Product'))->toBeFalse()
        ->and($manager->can('ViewAny:Role'))->toBeFalse();

    // admin: super admin, bypass semua
    expect($admin->can('ForceDelete:Product'))->toBeTrue()
        ->and($admin->can('ViewAny:Role'))->toBeTrue();
});

it('blocks staff from deleting a product but allows manager (policy level = 403)', function () {
    $staff = userWithRole('staff');
    $manager = userWithRole('manager');
    $product = Product::factory()->create();

    expect($staff->can('delete', $product))->toBeFalse()
        ->and($manager->can('delete', $product))->toBeTrue();
});

it('hides the bulk delete action from staff', function () {
    $staff = userWithRole('staff');
    Product::factory()->count(2)->create();

    $this->actingAs($staff);

    livewire(ListProducts::class)
        ->assertTableBulkActionHidden('delete');
});

it('shows the bulk delete action to manager', function () {
    $manager = userWithRole('manager');
    Product::factory()->count(2)->create();

    $this->actingAs($manager);

    livewire(ListProducts::class)
        ->assertTableBulkActionVisible('delete');
});

it('prevents staff from viewing the product list without permission is not the case (staff can view)', function () {
    $staff = userWithRole('staff');
    Product::factory()->count(2)->create();

    $this->actingAs($staff);

    livewire(ListProducts::class)
        ->assertSuccessful();
});

it('allows only admin to manage roles', function () {
    $staff = userWithRole('staff');
    $manager = userWithRole('manager');
    $admin = userWithRole('admin');

    expect($staff->can('ViewAny:Role'))->toBeFalse()
        ->and($manager->can('ViewAny:Role'))->toBeFalse()
        ->and($admin->can('ViewAny:Role'))->toBeTrue();
});

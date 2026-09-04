<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists categories on the index page', function () {
    $categories = Category::factory()->count(3)->create();

    livewire(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

it('creates a category', function () {
    livewire(CreateCategory::class)
        ->fillForm([
            'name' => 'Elektronik',
            'description' => 'Perangkat elektronik',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::where('name', 'Elektronik')->exists())->toBeTrue();
});

it('requires a name and unique name', function () {
    Category::factory()->create(['name' => 'Duplikat']);

    livewire(CreateCategory::class)
        ->fillForm(['name' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);

    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Duplikat'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

it('edits a category', function () {
    $category = Category::factory()->create(['name' => 'Lama']);

    livewire(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['name' => 'Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('Baru');
});

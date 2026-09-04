<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * `current_stock` sengaja TIDAK di-fillable: nilainya hanya boleh berubah
     * lewat StockMovementObserver, bukan mass assignment dari form.
     */
    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'supplier_id',
        'harga_jual',
        'harga_modal',
        'reorder_point',
        'unit',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'harga_jual' => 'decimal:2',
            'harga_modal' => 'decimal:2',
            'reorder_point' => 'integer',
            'current_stock' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Status stok relatif terhadap reorder point.
     * critical: <= reorder_point; low: <= 1.5x reorder_point; safe: di atas itu.
     */
    public function stockStatus(): string
    {
        if ($this->current_stock <= $this->reorder_point) {
            return 'critical';
        }

        if ($this->current_stock <= (int) round($this->reorder_point * 1.5)) {
            return 'low';
        }

        return 'safe';
    }
}

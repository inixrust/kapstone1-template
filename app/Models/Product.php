<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\Models\Activity;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** Stok <= reorder_point × pengali ini (dibulatkan) dianggap rendah. */
    public const LowStockMultiplier = 1.5;

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
     * Activity log dari seluruh pergerakan stok produk ini (untuk tab audit).
     *
     * @return HasManyThrough<Activity, StockMovement, $this>
     */
    public function stockActivities(): HasManyThrough
    {
        return $this->hasManyThrough(
            Activity::class,
            StockMovement::class,
            'product_id',   // FK di stock_movements → products.id
            'subject_id',   // FK di activity_log → stock_movements.id
            'id',           // local key di products
            'id',           // local key di stock_movements
        )->where('activity_log.subject_type', StockMovement::class)
            ->latest('activity_log.created_at');
    }

    /**
     * Status stok relatif terhadap reorder point.
     * critical: <= reorder_point; low: <= round(reorder_point × LowStockMultiplier); safe: di atas itu.
     */
    public function stockStatus(): string
    {
        if ($this->current_stock <= $this->reorder_point) {
            return 'critical';
        }

        if ($this->current_stock <= (int) round($this->reorder_point * self::LowStockMultiplier)) {
            return 'low';
        }

        return 'safe';
    }

    /**
     * Padanan query dari stockStatus(). Pengali disisipkan sebagai literal (bukan binding)
     * agar MySQL menghitung ROUND secara eksak (half away from zero) seperti round() PHP.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withStockStatus(Builder $query, string $status): void
    {
        $lowStockThreshold = 'ROUND(reorder_point * '.self::LowStockMultiplier.')';

        match ($status) {
            'critical' => $query->whereColumn('current_stock', '<=', 'reorder_point'),
            'low' => $query
                ->whereColumn('current_stock', '>', 'reorder_point')
                ->whereRaw("current_stock <= {$lowStockThreshold}"),
            'safe' => $query
                ->whereColumn('current_stock', '>', 'reorder_point')
                ->whereRaw("current_stock > {$lowStockThreshold}"),
        };
    }
}

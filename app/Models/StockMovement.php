<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Observers\StockMovementObserver;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(StockMovementObserver::class)]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stock_movement')
            ->logOnly(['product_id', 'type', 'qty', 'note', 'user_id', 'purchase_order_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Deskripsi audit; penyesuaian (adjustment) diberi deskripsi khusus.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        if ($this->type === StockMovementType::Adjustment) {
            return "Penyesuaian stok (adjustment) {$eventName} — qty akhir {$this->qty}";
        }

        return "Pergerakan stok {$this->type->value} {$eventName} — qty {$this->qty}";
    }

    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'note',
        'user_id',
        'purchase_order_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'qty' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Movement hasil penerimaan PO dikunci agar jejak ke PO tetap utuh.
     */
    public function isFromPurchaseOrder(): bool
    {
        return $this->getRawOriginal('purchase_order_id') !== null;
    }
}

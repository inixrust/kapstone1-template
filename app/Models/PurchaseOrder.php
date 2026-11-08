<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Observers\PurchaseOrderObserver;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * `number`, `status`, dan `created_by` sengaja tidak fillable: nilainya diisi
 * PurchaseOrderObserver dan status hanya berubah lewat method transisi.
 */
#[ObservedBy(PurchaseOrderObserver::class)]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'supplier_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('purchase_order')
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Purchase order {$this->number} {$eventName} — status {$this->status->getLabel()}";
    }

    /**
     * Salin perubahan status ke `properties` (`attributes`/`old`) agar jejak
     * transisi PO terbaca di satu tempat, selain `attribute_changes` bawaan v5.
     */
    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $activity->properties = collect($activity->properties ?? [])
            ->merge($activity->attribute_changes ?? []);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PurchaseOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseOrderStatus::Draft;
    }

    public function total(): float
    {
        return (float) $this->items->sum(fn (PurchaseOrderItem $item): float => $item->subtotal());
    }

    /**
     * Ajukan draft (draft → diajukan). Ditegakkan di domain, bukan hanya Policy,
     * karena admin melewati semua Policy lewat Shield `intercept_gate = before`.
     *
     * @throws AuthorizationException
     */
    public function submit(): void
    {
        if (! $this->isDraft()) {
            throw new AuthorizationException('Hanya purchase order berstatus draft yang dapat diajukan.');
        }

        if (Auth::check() && Auth::user()->cannot('submit', $this)) {
            throw new AuthorizationException('Anda tidak berwenang mengajukan purchase order.');
        }

        if (! $this->items()->exists()) {
            throw new AuthorizationException('Purchase order tanpa item tidak dapat diajukan.');
        }

        $this->status = PurchaseOrderStatus::Submitted;
        $this->save();
    }
}

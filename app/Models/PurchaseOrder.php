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
 * `number`, `status`, `created_by`, dan kolom keputusan (`decided_*`,
 * `decision_note`) sengaja tidak fillable: nilainya diisi PurchaseOrderObserver
 * atau method transisi.
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
            'decided_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
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

    /**
     * Setujui PO yang diajukan (diajukan → disetujui).
     *
     * @throws AuthorizationException
     */
    public function approve(?string $note = null): void
    {
        $this->ensureCanBeDecidedBy(Auth::user(), 'approve');

        $this->decide(PurchaseOrderStatus::Approved, $note);
    }

    /**
     * Tolak PO yang diajukan (diajukan → ditolak); catatan wajib.
     *
     * @throws AuthorizationException
     */
    public function reject(string $note): void
    {
        $this->ensureCanBeDecidedBy(Auth::user(), 'reject');

        if (blank($note)) {
            throw new AuthorizationException('Catatan wajib diisi saat menolak purchase order.');
        }

        $this->decide(PurchaseOrderStatus::Rejected, $note);
    }

    /**
     * Larangan pembuat memutuskan PO sendiri dicek langsung di sini, bukan lewat
     * Policy, karena admin melewati semua Policy (Shield `intercept_gate = before`).
     *
     * @throws AuthorizationException
     */
    private function ensureCanBeDecidedBy(?User $user, string $ability): void
    {
        if ($this->status !== PurchaseOrderStatus::Submitted) {
            throw new AuthorizationException('Hanya purchase order berstatus diajukan yang dapat diputuskan.');
        }

        if ($user === null) {
            throw new AuthorizationException('Keputusan purchase order memerlukan user yang login.');
        }

        if ($this->created_by === $user->id) {
            throw new AuthorizationException('Pembuat purchase order tidak dapat memutuskan purchase order sendiri.');
        }

        if ($user->cannot($ability, $this)) {
            throw new AuthorizationException('Hanya admin yang dapat menyetujui atau menolak purchase order.');
        }
    }

    private function decide(PurchaseOrderStatus $status, ?string $note): void
    {
        $this->status = $status;
        $this->decided_by = Auth::id();
        $this->decided_at = now();
        $this->decision_note = blank($note) ? null : $note;
        $this->save();
    }
}

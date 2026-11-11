<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, UsesUuid;

    public const PRINT = 'print';
    public const DIGITAL = 'digital';

    protected $fillable = [
        'kind',
        'collection_id',
        'stripe_checkout_session_id',
        'pricing_breakdown',
        'delivered_at',
        'user_id',
        'customer_email',
        'customer_name',
        'customer_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_county',
        'shipping_postcode',
        'shipping_country_code',
        'total',
        'platform_fee',
        'print_cost',
        'photographer_amount',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'status',
        'paid_at',
        'prodigi_order_id',
        'prodigi_status',
        'prodigi_charge',
        'prodigi_submitted_at',
        'prodigi_shipped_at',
        'prodigi_tracking_number',
        'prodigi_error_message',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'photographer_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
        'pricing_breakdown' => 'array',
        'customer_phone' => 'encrypted',
        'shipping_address_line1' => 'encrypted',
        'shipping_address_line2' => 'encrypted',
        'shipping_city' => 'encrypted',
        'shipping_county' => 'encrypted',
        'shipping_postcode' => 'encrypted',
        'prodigi_charge' => 'decimal:2',
        'prodigi_submitted_at' => 'datetime',
        'prodigi_shipped_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function isDigital(): bool
    {
        return $this->kind === self::DIGITAL;
    }

    /** When the buyer's download link stops working. */
    public function downloadsExpireAt(): ?\Illuminate\Support\Carbon
    {
        return $this->paid_at?->copy()->addDays((int) config('downloads.link_days'));
    }

    /** The signed link to a paid digital order's download page. */
    public function downloadUrl(): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute('downloads.show', $this->downloadsExpireAt() ?? now()->addDays((int) config('downloads.link_days')), ['order' => $this->id]);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function hasPrintItems(): bool
    {
        return $this->items()
            ->whereHas('product', function ($query) {
                $query->where('type', 'like', 'print%')
                    ->orWhereNotNull('prodigi_sku');
            })
            ->exists();
    }

    public function isProdigiSubmitted(): bool
    {
        return !is_null($this->prodigi_order_id);
    }

    public function isProdigiShipped(): bool
    {
        return $this->prodigi_status === 'shipped';
    }
}


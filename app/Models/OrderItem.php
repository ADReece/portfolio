<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'prodigi_attributes',
        'template_id',
        'template_data',
        'print_asset_path',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'prodigi_attributes' => 'array',
        'template_data' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The photographer's template this print is personalised with, if any. */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function isPersonalised(): bool
    {
        return $this->template_id !== null;
    }

    public function getSubtotal(): float
    {
        return $this->quantity * $this->price;
    }
}


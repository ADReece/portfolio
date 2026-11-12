<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Set extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'collection_id',
        'name',
        'hide_from_portfolio'
    ];

    protected $casts = [
        'hide_from_portfolio' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function($model){
            if (is_null($model->sort_order)) {
                $max = Set::where('collection_id',$model->collection_id)->max('sort_order');
                $model->sort_order = is_null($max) ? 1 : $max + 1;
            }
        });
    }

    public function collection() : BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function photos() : HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function videos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Video::class)->orderBy('sort_order')->orderBy('created_at');
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'set_templates')->withTimestamps();
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\UsesUuid;
use App\Models\ProdigiProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Carbon; // for date checks
use Illuminate\Support\Facades\Cache; // add cache import

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UsesUuid, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'phone',
        'twitter',
        'instagram',
        'facebook',
        'portfolio_display_mode',
        'masonry_columns',
        'photos_per_page',
        'watermark_text',
        'subscription_plan_id',
        'stripe_connect_id',
        'stripe_connect_enabled',
        'copy_protection',
        'digital_photo_price',
        'digital_bundles',
        'notify_favourites',
        'feature_override',
        'feature_override_expires_at',
        'is_admin',
        'portfolio_font',
        'portfolio_accent_color',
        'portfolio_theme',
        'portfolio_background_color',
        'portfolio_text_color',
        'portfolio_heading_color',
        'logo_path',
        'logo_thumb_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'stripe_connect_enabled' => 'boolean',
        'digital_photo_price' => 'decimal:2',
        'digital_bundles' => 'array',
        'copy_protection' => 'boolean',
        'feature_override' => 'boolean',
        'attribution' => 'array',
        'feature_override_expires_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_demo' => 'boolean',
        'notify_favourites' => 'boolean',
    ];

    protected $with = ['subscriptionPlan'];

    /**
     * Get all of the settings for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'user_id', 'id');
    }

    public function collections() : HasMany
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Get all of the photos for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function publicPhotos() : HasMany
    {
        return $this->hasMany(Photo::class)->where('private', false);
    }

    /**
     * Get all of the tags for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'user_id', 'id');
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function prodigiProducts(): BelongsToMany
    {
        return $this->belongsToMany(ProdigiProduct::class, 'prodigi_product_user')
            ->withPivot('is_enabled', 'retail_price')
            ->withTimestamps();
    }

    public function enabledProdigiProducts(): BelongsToMany
    {
        return $this->prodigiProducts()->wherePivot('is_enabled', true)->where('is_active', true);
    }

    // Feature checking methods
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function fonts(): HasMany
    {
        return $this->hasMany(Font::class);
    }


    public function hasFeature(string $feature): bool
    {
        if ($this->is_admin) {
            return true; // admins can do everything
        }
        if ($this->isFeatureOverrideActive()) {
            return true; // grant all features
        }
        if (!$this->subscriptionPlan) {
            return false;
        }
        return $this->subscriptionPlan->hasFeature($feature);
    }

    public function customDomains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomDomain::class);
    }

    /**
     * How many custom domains this account may have (a domain's www counts
     * with it). Set per plan in config/custom_domains.php; admins and feature
     * overrides get the largest plan allowance.
     */
    public function customDomainLimit(): int
    {
        $plans = config('custom_domains.plans', []);
        if ($this->is_admin || $this->isFeatureOverrideActive()) {
            return max(1, ...array_values($plans ?: [1]));
        }

        return (int) ($plans[$this->subscriptionPlan?->slug] ?? 0);
    }

    /**
     * Video upload limits in bytes, or null when the plan has no video.
     *
     * @return array{max_file: int, max_total: int}|null
     */
    public function videoLimits(): ?array
    {
        if (! $this->hasFeature('video_upload')) {
            return null;
        }
        $limits = ($this->is_admin || $this->isFeatureOverrideActive())
            ? config('videos.unlimited')
            : (config('videos.plans.'.$this->subscriptionPlan?->slug) ?? config('videos.plans.videographer'));

        return ['max_file' => $limits['max_file_mb'] * 1048576, 'max_total' => $limits['max_total_mb'] * 1048576];
    }

    public function videos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Video::class);
    }

    /** The public demo photographer: can't log in, never charged, checkouts and emails off. */
    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    public function isFeatureOverrideActive(): bool
    {
        if (!$this->feature_override) return false;
        if (!$this->feature_override_expires_at) return true;
        return now()->lt($this->feature_override_expires_at);
    }

    public function canUploadPhotos(): bool
    {
        if ($this->isFeatureOverrideActive()) {
            return true;
        }
        if (!$this->subscriptionPlan) {
            return false;
        }
        if ($this->subscriptionPlan->photo_limit === null) {
            return true; // Unlimited
        }
        return $this->photos()->count() < $this->subscriptionPlan->photo_limit;
    }

    public function canCreateCollections(): bool
    {
        if ($this->isFeatureOverrideActive()) {
            return true;
        }
        if (!$this->subscriptionPlan) {
            return false;
        }
        if ($this->subscriptionPlan->collection_limit === null) {
            return true; // Unlimited
        }
        return $this->collections()->count() < $this->subscriptionPlan->collection_limit;
    }

    public function getRemainingPhotos(): ?int
    {
        if ($this->isFeatureOverrideActive()) {
            return null; // Unlimited
        }
        if (!$this->subscriptionPlan || $this->subscriptionPlan->photo_limit === null) {
            return null; // Unlimited
        }
        return max(0, $this->subscriptionPlan->photo_limit - $this->photos()->count());
    }

    public function getRemainingCollections(): ?int
    {
        if ($this->isFeatureOverrideActive()) {
            return null; // Unlimited
        }
        if (!$this->subscriptionPlan || $this->subscriptionPlan->collection_limit === null) {
            return null; // Unlimited
        }
        return max(0, $this->subscriptionPlan->collection_limit - $this->collections()->count());
    }

    /**
     * Get current disk usage for User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function totalPhotoSize(): float
    {
        return $this->photos->sum('size') / 1024000; //(Size is store in Bytes.  /1024k for mb.)
    }
    public function logoUrl(): ?string
    {
        if (!$this->logo_path && !$this->logo_thumb_path) return null;
        $key = $this->logo_thumb_path ?: $this->logo_path;
        return Cache::remember('logo_url_'.$this->id.'_'.$key, 540, function() use ($key) { // cache ~9 minutes
            try {
                return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($key, now()->addMinutes(10));
            } catch (\Throwable $e) {
                return asset('favicon.ico');
            }
        });
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->is_admin || $this->isFeatureOverrideActive()) {
            return true;
        }

        if (!$this->subscriptionPlan) {
            return false;
        }

        // Free plan is always "active" (no expiry)
        if ($this->subscriptionPlan->isFree()) {
            return true;
        }

        // Check if user has an active Stripe subscription
        return $this->subscribed('default');
    }

    public function canUseCustomizations(): bool
    {
        // Grant if override, admin, or plan exposes either custom_templates or upload_logo
        if ($this->is_admin || $this->isFeatureOverrideActive()) {
            return true;
        }
        if (!$this->subscriptionPlan) {
            return false;
        }
        return $this->subscriptionPlan->hasFeature('custom_templates') || $this->subscriptionPlan->hasFeature('upload_logo');
    }

    /**
     * The bio as safe HTML for display. Sanitised on output too, so bios
     * saved before sanitising was tightened are safe.
     */
    public function bioHtml(): string
    {
        return \App\Support\HtmlSanitizer::clean($this->bio);
    }

    /**
     * Metadata on this user's Stripe customer. The Stripe account is shared
     * with other Ascend Digital apps, so customers are tagged app=portfolio.
     */
    public function stripeMetadata()
    {
        return ['app' => 'portfolio', 'user_id' => (string) $this->getKey()];
    }
}

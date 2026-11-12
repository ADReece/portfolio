<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A photographer's own domain. One row covers hostname and, when
 * include_www, www.hostname; primary_www says which is canonical.
 */
class CustomDomain extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const FAILED = 'failed';

    protected $fillable = ['hostname', 'include_www', 'primary_www'];

    protected $casts = [
        'include_www' => 'boolean',
        'ownership_trusted' => 'boolean',
        'primary_www' => 'boolean',
        'verified_at' => 'datetime',
        'secured_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'failing_since' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomDomain $domain) {
            $domain->verification_token ??= 'pv-'.Str::lower(Str::random(32));
            $domain->status ??= self::PENDING;
        });
        static::saved(fn () => app(\App\CustomDomains\DomainRegistry::class)->forget());
        static::deleted(fn () => app(\App\CustomDomains\DomainRegistry::class)->forget());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->status === self::VERIFIED;
    }

    /** @return list<string> every hostname this domain serves */
    public function hostnames(): array
    {
        return $this->include_www ? [$this->hostname, 'www.'.$this->hostname] : [$this->hostname];
    }

    /** The canonical hostname: the one the others redirect to. */
    public function primaryHostname(): string
    {
        return $this->include_www && $this->primary_www ? 'www.'.$this->hostname : $this->hostname;
    }

    /** Hostname for people to read (Unicode for IDNs). */
    public function displayHostname(?string $host = null): string
    {
        $host ??= $this->hostname;

        return function_exists('idn_to_utf8') ? (idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host) : $host;
    }

    public function txtRecordName(): string
    {
        return config('custom_domains.txt_prefix').'.'.$this->hostname;
    }

    /** Whether this is a bare registered domain (example.com) rather than a subdomain. */
    public function isApex(): bool
    {
        return substr_count($this->hostname, '.') === 1
            || (bool) preg_match('/\.(co|org|me|ltd|plc|net|ac|gov)\.uk$|\.com\.au$|\.co\.nz$/', $this->hostname)
                && substr_count($this->hostname, '.') === 2;
    }
}

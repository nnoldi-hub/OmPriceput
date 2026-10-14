<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'In asteptare',
        self::STATUS_SUBMITTED => 'De moderat',
        self::STATUS_APPROVED => 'Publicata',
        self::STATUS_REJECTED => 'Respinsa',
    ];

    protected $fillable = [
        'installation_id',
        'client_id',
        'offer_id',
        'token',
        'status',
        'rating',
        'comment',
        'author_name',
        'city',
        'service_label',
        'consent_to_publish',
        'requested_at',
        'submitted_at',
        'approved_at',
        'approved_by',
        'ip',
    ];

    protected $casts = [
        'rating' => 'integer',
        'consent_to_publish' => 'boolean',
        'requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $hidden = ['token'];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /** Recenziile care pot apărea public: aprobate și cu acord de publicare. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED)->where('consent_to_publish', true);
    }

    public static function generateToken(): string
    {
        return Str::random(24);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Numele afișat pe site: prenume + initiala, ca să nu expunem date complete. */
    public function displayName(): string
    {
        $name = trim((string) ($this->author_name ?: $this->client?->name));

        if ($name === '') {
            return 'Client Om Priceput';
        }

        $parts = preg_split('/\s+/', $name);
        $first = array_shift($parts);

        if ($parts === []) {
            return $first;
        }

        return $first.' '.mb_strtoupper(mb_substr($parts[0], 0, 1)).'.';
    }
}

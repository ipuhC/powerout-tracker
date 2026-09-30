<?php

namespace App\Models;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use Carbon\CarbonInterface;
use Database\Factories\OutageReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OutageReport extends Model
{
    /** @use HasFactory<OutageReportFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outage_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'radius_km',
        'duration_type',
        'duration_hours',
        'description',
        'status',
        'closed_at',
        'expires_at',
    ];

    /**
     * Attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'location',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'latitude',
        'longitude',
        'remaining_seconds',
    ];

    /**
     * Temporary holding variables for coordinates before location sync.
     */
    public ?float $pendingLat = null;

    public ?float $pendingLng = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OutageStatus::class,
            'duration_type' => DurationType::class,
            'duration_hours' => 'integer',
            'radius_km' => 'float',
            'closed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Boot model events to enforce safety invariants.
     */
    protected static function booted(): void
    {
        static::creating(function (OutageReport $report) {
            // Guarantee location attribute is set if latitude/longitude are pending
            if ($report->pendingLat !== null && $report->pendingLng !== null) {
                $report->attributes['location'] = DB::raw(
                    "ST_SetSRID(ST_MakePoint({$report->pendingLng}, {$report->pendingLat}), 4326)::geography"
                );
            }

            // Enforce hard expiration safety calculation if not explicitly provided
            if ($report->expires_at === null) {
                $durationType = $report->duration_type instanceof DurationType
                    ? $report->duration_type
                    : DurationType::from($report->duration_type);

                $report->expires_at = static::calculateExpiresAt(
                    $durationType,
                    $report->duration_hours,
                    $report->created_at ?? now()
                );
            }
        });

        static::updating(function (OutageReport $report) {
            if ($report->pendingLat !== null && $report->pendingLng !== null) {
                $report->attributes['location'] = DB::raw(
                    "ST_SetSRID(ST_MakePoint({$report->pendingLng}, {$report->pendingLat}), 4326)::geography"
                );
            }
        });
    }

    /**
     * Owner of this outage report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate strict expiration timestamp adhering to business rules:
     * - Fixed: created_at + min(72, duration_hours)
     * - Until Closed: created_at + 72 hours (hard safety cap)
     */
    public static function calculateExpiresAt(
        DurationType $durationType,
        ?int $durationHours = null,
        ?CarbonInterface $from = null
    ): CarbonInterface {
        $base = $from ? $from->copy() : now();

        if ($durationType === DurationType::UNTIL_CLOSED) {
            return $base->addHours(72);
        }

        $hours = max(1, min(72, (int) $durationHours));

        return $base->addHours($hours);
    }

    /**
     * Set geographic coordinates explicitly via PostGIS geography.
     */
    public function setCoordinates(float $latitude, float $longitude): self
    {
        $this->pendingLat = $latitude;
        $this->pendingLng = $longitude;
        $this->attributes['location'] = DB::raw(
            "ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)::geography"
        );

        return $this;
    }

    /**
     * Accessor & Mutator for latitude.
     */
    protected function latitude(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (isset($attributes['latitude'])) {
                    return (float) $attributes['latitude'];
                }
                if ($this->pendingLat !== null) {
                    return $this->pendingLat;
                }
                if (! empty($attributes['location'])) {
                    $coords = $this->parseLocation($attributes['location']);

                    return $coords ? $coords['latitude'] : null;
                }

                return null;
            },
            set: function ($value) {
                $this->pendingLat = (float) $value;
                if ($this->pendingLng !== null) {
                    return [
                        'location' => DB::raw(
                            "ST_SetSRID(ST_MakePoint({$this->pendingLng}, {$this->pendingLat}), 4326)::geography"
                        ),
                    ];
                }

                return [];
            }
        );
    }

    /**
     * Accessor & Mutator for longitude.
     */
    protected function longitude(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (isset($attributes['longitude'])) {
                    return (float) $attributes['longitude'];
                }
                if ($this->pendingLng !== null) {
                    return $this->pendingLng;
                }
                if (! empty($attributes['location'])) {
                    $coords = $this->parseLocation($attributes['location']);

                    return $coords ? $coords['longitude'] : null;
                }

                return null;
            },
            set: function ($value) {
                $this->pendingLng = (float) $value;
                if ($this->pendingLat !== null) {
                    return [
                        'location' => DB::raw(
                            "ST_SetSRID(ST_MakePoint({$this->pendingLng}, {$this->pendingLat}), 4326)::geography"
                        ),
                    ];
                }

                return [];
            }
        );
    }

    /**
     * Accessor for remaining seconds until expiration.
     */
    protected function remainingSeconds(): Attribute
    {
        return Attribute::make(
            get: function () {
                $status = $this->status instanceof OutageStatus
                    ? $this->status
                    : OutageStatus::tryFrom((string) $this->status);

                if ($status !== OutageStatus::ACTIVE || $this->expires_at === null) {
                    return 0;
                }

                $diff = now()->diffInSeconds($this->expires_at, false);

                return max(0, (int) $diff);
            }
        );
    }

    /**
     * Parse PostGIS EWKB binary/hex into [latitude, longitude].
     */
    protected function parseLocation(mixed $raw): ?array
    {
        if (is_resource($raw)) {
            $raw = stream_get_contents($raw);
        }

        if (is_string($raw) && ctype_xdigit($raw)) {
            $bin = hex2bin($raw);
        } elseif (is_string($raw)) {
            $bin = $raw;
        } else {
            return null;
        }

        $len = strlen($bin);
        if ($len === 25) { // EWKB with SRID: 1 byte endian + 4 bytes type + 4 bytes SRID + 8 bytes X + 8 bytes Y
            $x = unpack('d', substr($bin, 9, 8))[1];
            $y = unpack('d', substr($bin, 17, 8))[1];

            return ['longitude' => round($x, 6), 'latitude' => round($y, 6)];
        } elseif ($len === 21) { // Standard WKB: 1 byte endian + 4 bytes type + 8 bytes X + 8 bytes Y
            $x = unpack('d', substr($bin, 5, 8))[1];
            $y = unpack('d', substr($bin, 13, 8))[1];

            return ['longitude' => round($x, 6), 'latitude' => round($y, 6)];
        }

        return null;
    }

    /**
     * Scope: Tier-1 Zero-Stale-Data Active Guarantee (ADR-003).
     * Enforces status = active AND expires_at > NOW() at the database engine level.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OutageStatus::ACTIVE->value)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope: Explicitly select PostGIS coordinates with ST_Y and ST_X.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->addSelect([
            'latitude' => DB::raw('ST_Y(location::geometry)'),
            'longitude' => DB::raw('ST_X(location::geometry)'),
        ]);
    }

    /**
     * Scope: Spatial Bounding Box Filter with GiST Index (ADR-002).
     * Checks if outage circle (center + radius_km) intersects the visible viewport envelope.
     */
    public function scopeWithinBoundingBox(
        Builder $query,
        float $minLat,
        float $minLng,
        float $maxLat,
        float $maxLng
    ): Builder {
        return $query->whereRaw(
            'ST_DWithin(
                location,
                ST_MakeEnvelope(?, ?, ?, ?, 4326)::geography,
                radius_km * 1000
            )',
            [$minLng, $minLat, $maxLng, $maxLat]
        );
    }
}

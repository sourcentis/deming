<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlMapping extends Model
{
    use HasFactory;

    public const MAPPING_TYPES = [
        'equivalent',
        'covers',
        'covered_by',
        'partial',
        'supports',
        'related',
    ];

    public const COVERAGE_LEVELS = [
        'full',
        'high',
        'medium',
        'low',
        'none',
    ];

    protected $fillable = [
        'source_control_id',
        'target_control_id',
        'mapping_type',
        'coverage',
        'confidence',
        'rationale',
        'source_reference',
        'source_url',
        'validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Control, $this>
     */
    public function sourceControl(): BelongsTo
    {
        return $this->belongsTo(Control::class, 'source_control_id');
    }

    /**
     * @return BelongsTo<Control, $this>
     */
    public function targetControl(): BelongsTo
    {
        return $this->belongsTo(Control::class, 'target_control_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public static function inverseMappingType(string $mappingType): string
    {
        return match ($mappingType) {
            'covers' => 'covered_by',
            'covered_by' => 'covers',
            default => $mappingType,
        };
    }
}

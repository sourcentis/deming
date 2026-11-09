<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasAiAnalysis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Control extends Model
{
    use HasFactory, Auditable, HasAiAnalysis;

    public static $searchable = [
        'name',
        'clause',
        'objective',
        'input',
        'attributes',
        'model',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = self::AI_COLUMNS;

    protected $fillable = [
        'domain_id',
        'clause',
        'name',
        'objective',
        'attributes',
        'input',
        'model',
        'indicator',
        'action_plan',
    ];

    /**
     * Access rule of ControlController@show (security measure page):
     * no API account, and unless the user sees all data, at least one
     * control (Measure) of this security measure assigned to the user,
     * directly or through one of their groups.
     */
    public static function isViewableBy(User $user, int $id): bool
    {
        if ($user->isAPI()) {
            return false;
        }

        if ($user->seesAllData()) {
            return true;
        }

        $measureIds = DB::table('control_measure')->where('control_id', $id)->select('measure_id');

        return DB::table('measure_user')
            ->where('user_id', $user->id)
            ->whereIn('measure_id', $measureIds)
            ->exists()
            || DB::table('measure_user_group')
                ->join('user_user_group', 'measure_user_group.user_group_id', '=', 'user_user_group.user_group_id')
                ->where('user_user_group.user_id', $user->id)
                ->whereIn('measure_user_group.measure_id', $measureIds)
                ->exists();
    }

    /** @return BelongsToMany<Risk, $this> */
    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'control_risk');
    }

    // Return the domain associated to this control
    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * @return HasMany<ControlMapping, $this>
     */
    public function outgoingMappings(): HasMany
    {
        return $this->hasMany(ControlMapping::class, 'source_control_id');
    }

    /**
     * @return HasMany<ControlMapping, $this>
     */
    public function incomingMappings(): HasMany
    {
        return $this->hasMany(ControlMapping::class, 'target_control_id');
    }

    // Return the completed measures associated to this control
    public function measures(): BelongsToMany
    {
        return $this->belongsToMany(Measure::class)
            ->whereNotNull('realisation_date')->orderBy('realisation_date');
    }

    // Return all measures associated to this control (including pending ones, for API sync)
    public function allMeasures(): BelongsToMany
    {
        return $this->belongsToMany(Measure::class);
    }

    // Check if there is an active (pending) measure associated with this control
    public function isActive(): bool
    {
        return DB::table('control_measure')
            ->where('control_id', $this->id)
            ->join('measures', 'measures.id', '=', 'control_measure.measure_id')
            ->whereNull('measures.realisation_date')
            ->exists();
    }

    // Check if all associated measures are done (none pending)
    public function isDisabled(): bool
    {
        return DB::table('control_measure')
            ->where('control_id', $this->id)
            ->exists()
        &&
            ! DB::table('control_measure')
                ->where('control_id', $this->id)
                ->join('measures', 'measures.id', '=', 'control_measure.measure_id')
                ->whereNull('measures.realisation_date')
                ->exists();
    }
}

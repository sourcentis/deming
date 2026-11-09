<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    public const TYPE_BUSINESS = 'business';
    public const TYPE_SECURITY = 'security';

    public const TYPES = [
        self::TYPE_BUSINESS,
        self::TYPE_SECURITY,
    ];

    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->orderBy('name');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Measure, $this> */
    public function measures()
    {
        return $this->belongsToMany(Measure::class, 'measure_user_group', 'user_group_id', 'measure_id')->whereNull('realisation_date')->orderBy('plan_date');
    }
}

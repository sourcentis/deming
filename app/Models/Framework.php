<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Framework extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'version',
        'publisher',
        'jurisdiction',
        'source_url',
        'status',
        'publication_date',
        'effective_date',
        'notes',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'effective_date' => 'date',
    ];

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'framework', 'code');
    }

    /**
     * @return HasManyThrough<Control, Domain, $this>
     */
    public function controls(): HasManyThrough
    {
        return $this->hasManyThrough(
            Control::class,
            Domain::class,
            'framework',
            'domain_id',
            'code',
            'id'
        );
    }

    public static function ensureForDomainCode(?string $code): ?self
    {
        $code = (string) $code;

        if (trim($code) === '') {
            return null;
        }

        return self::firstOrCreate(
            ['code' => $code],
            [
                'name' => $code,
                'version' => '',
                'publisher' => '',
            ]
        );
    }
}

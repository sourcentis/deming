<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Domain extends Model
{
    use HasFactory;

    public static $searchable = [
        'title',
        'framework',
        'description',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'title',
        'framework',
        'description',
    ];

    protected static function booted(): void
    {
        static::saved(function (Domain $domain): void {
            if (Schema::hasTable('frameworks')) {
                Framework::ensureForDomainCode($domain->framework);
            }
        });
    }

    /**
     * @return BelongsTo<Framework, $this>
     */
    public function frameworkMetadata(): BelongsTo
    {
        return $this->belongsTo(Framework::class, 'framework', 'code');
    }
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasAiAnalysis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Action extends Model
{
    use HasFactory, Auditable, HasAiAnalysis;

    public static $searchable = [
        'reference',
        'type',
        'scope',
        'cause',
        'remediation',
        'justification',
    ];

    protected $dates = [
        'creation_date',
        'due_date',
        'close_date',
    ];

    protected $hidden = self::AI_COLUMNS;

    protected $fillable = [
        'reference',
        'type',
        'progress',
        'criticity',
        'scope',
        'cause',
        'remediation',
        'creation_date',
        'due_date',
        'close_date',
        'justification',
    ];

    // Control status :
    // O - Open
    // 1 - Closed
    // 2 - Rejected

    // Type
    // 1 - Major - red
    // 2 - Minor - orange
    // 3 - Observation - yellow
    // 4 - Opportunity - green

    /**
     * Access rule of ActionController@show: administrators, users and auditors.
     */
    public static function isViewableBy(User $user, int $id): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_USER, User::ROLE_AUDITOR], true);
    }

    public function owners() : BelongsToMany
    {
        return $this->belongsToMany(User::class, 'action_user', 'action_id')->orderBy('name');
    }

    public function controls() : BelongsToMany
    {
        return $this->belongsToMany(Control::class, 'action_control', 'action_id', 'control_id');
    }

    public function risks() : BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'action_risk')->orderBy('name');
    }

    // Computer progress history of an action
    public function history()
    {
        $logs = AuditLog::where('subject_type', Action::class)
            ->where('subject_id', $this->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $history = [];
        $lastProgressDate = null;

        foreach ($logs as $log) {
            $properties = json_decode($log->properties[0], true);

            if (! isset($properties['progress'])) {
                continue;
            }

            $progress = (int) $properties['progress'];
            $progressDate = $log->created_at->toDateString();

            // Same day ?
            if ($progressDate === $lastProgressDate) {
                // replace last value
                $lastKey = array_key_last($history);
                if ($lastKey !== null) {
                    $history[$lastKey]['progress'] = $progress;
                }
            } else {                // add history
                $history[] = [
                    'date' => $progressDate,
                    'progress' => $progress,
                ];
                // save last date
                $lastProgressDate = $progressDate;
            }
        }

        return $history;
    }
}

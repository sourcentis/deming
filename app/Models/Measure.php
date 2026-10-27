<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasAiAnalysis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Measure extends Model
{
    use HasFactory, Auditable, HasAiAnalysis;

    public static $searchable = [
        'name',
        'objective',
        'observations',
        'input',
        'attributes',
        'model',
        'action_plan',
        'plan_date',
        'scope'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = self::AI_COLUMNS;

    protected $fillable = [
        'name',
        'objective',
        'observations',
        'input',
        'score',
        'attributes',
        'model',
        'action_plan',
        'realisation_date',
        'plan_date',
        'periodicity',
    ];

    private $groups = null;
    private $users = null;

    // Measure status:
    // 0 - Todo      => realisation_date null
    // 1 - Proposed  => realisation_date not null (auditee proposed)
    // 2 - Done      => realisation_date not null

    /** @return BelongsToMany<Control, $this> */
    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class)->orderBy('clause');
    }

    /** @return HasMany<Action, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class, 'measure_id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'measure_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        if ($this->users === null) {
            $this->users = $this->belongsToMany(User::class, 'measure_user', 'measure_id')->orderBy('name');
        }
        return $this->users;
    }

    /** @return BelongsToMany<UserGroup, $this> */
    public function groups()
    {
        if ($this->groups === null) {
            $this->groups = $this->belongsToMany(UserGroup::class, 'measure_user_group', 'measure_id')->orderBy('name');
        }
        return $this->groups;
    }

    public function canMake(): bool
    {
        if ($this->status !== 0) {
            return false;
        }

        $user = Auth::user();

        if ($this->isAdminOrUser($user)) {
            return true;
        }

        if ($this->isAuditorOrAuditeeWithAccess($user)) {
            return true;
        }

        return false;
    }

    public function canValidate(): bool
    {
        if ($this->status !== 1) {
            return false;
        }

        $user = Auth::user();

        if ($this->isAdminOrUser($user)) {
            return true;
        }

        return false;
    }

    public function clauses(int $id): Collection
    {
        return DB::table('controls')
            ->select('control_id', 'clause')
            ->join('control_measure', 'control_measure.measure_id', strval($id))
            ->get();
    }

    public static function cleanup(string $startDate, bool $dryRun): array
    {
        $documentCount = 0;
        $measureCount = 0;
        $logCount = 0;

        $logCount = AuditLog::where('created_at', '<', $startDate)->count();
        if (! $dryRun) {
            AuditLog::where('created_at', '<', $startDate)->delete();
        }

        $oldMeasures = Measure::whereNotNull('realisation_date')
            ->where('realisation_date', '<', $startDate)
            ->get();

        foreach ($oldMeasures as $measure) {
            DB::transaction(function () use ($dryRun, $measure, &$documentCount, &$measureCount) {
                $documents = Document::where('measure_id', $measure->id)->get();

                foreach ($documents as $document) {
                    if (! $dryRun) {
                        $filePath = storage_path('docs/' . $document->id);
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                        $document->delete();
                    }
                    $documentCount++;
                }

                if (! $dryRun) {
                    DB::table('control_measure')->where('measure_id', $measure->id)->delete();
                    DB::table('actions')->where('measure_id', $measure->id)->update(['measure_id' => null]);
                    Measure::where('next_id', $measure->id)->update(['next_id' => null]);
                    $measure->delete();
                }
                $measureCount++;
            });
        }

        return [
            'documentCount' => $documentCount,
            'measureCount'  => $measureCount,
            'logCount'      => $logCount,
        ];
    }

    private function isAdminOrUser($user): bool
    {
        return in_array($user->role, [1, 2]);
    }

    private function isAuditorOrAuditeeWithAccess($user): bool
    {
        if (! in_array($user->role, [3, 5])) {
            return false;
        }

        return $this->isDirectlyAssignedToUser($user) || $this->isAssignedViaGroup($user);
    }

    private function isDirectlyAssignedToUser($user): bool
    {
        return DB::table('measure_user')
            ->where('measure_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function isAssignedViaGroup($user): bool
    {
        return DB::table('measure_user_group')
            ->join('user_user_group', 'measure_user_group.user_group_id', '=', 'user_user_group.user_group_id')
            ->where('measure_user_group.measure_id', $this->id)
            ->where('user_user_group.user_id', $user->id)
            ->exists();
    }
}

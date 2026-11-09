<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    /**
     * User role constants
     */
    public const ROLE_DISABLED = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_USER = 2;
    public const ROLE_AUDITOR = 3;
    public const ROLE_API = 4;
    public const ROLE_AUDITEE = 5;

    /**
     * Available roles with their labels
     *
     * @var array<int, string>
     */
    public const ROLES = [
        self::ROLE_DISABLED => 'Disabled',
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_USER => 'User',
        self::ROLE_AUDITOR => 'Auditor',
        self::ROLE_API => 'API',
        self::ROLE_AUDITEE => 'Auditee',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'login', 'name', 'email', 'password', 'title', 'role', 'language',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password1', 'password2', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relations
     *
     * @return BelongsToMany<UserGroup, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class)->orderBy('name');
    }

    public function measures(): BelongsToMany
    {
        return $this->belongsToMany(Measure::class, 'measure_user', 'user_id', 'measure_id')->orderBy('name');
    }

    /** @return BelongsToMany<Measure, $this> */
    public function lastMeasures(): BelongsToMany
    {
        return $this->belongsToMany(Measure::class, 'measure_user', 'user_id', 'measure_id')->whereNull('realisation_date')->orderBy('name');
    }

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(Action::class, 'action_user', 'user_id', 'action_id');
    }

    /**
     * Helpers
     */

    /**
     * Pending measures assigned to the user, directly or through their groups.
     * Each measure appears once, with the groups that assign it to the user
     * (empty when the assignment is only direct).
     *
     * @return Collection<int, array{measure: Measure, groups: list<UserGroup>}>
     */
    public function pendingMeasureAssignments(): Collection
    {
        $this->loadMissing(['lastMeasures.controls', 'groups.measures.controls']);

        $assignments = [];

        foreach ($this->lastMeasures as $measure) {
            $assignments[$measure->id] = ['measure' => $measure, 'groups' => []];
        }

        foreach ($this->groups as $group) {
            foreach ($group->measures as $measure) {
                $assignments[$measure->id] ??= ['measure' => $measure, 'groups' => []];
                $assignments[$measure->id]['groups'][] = $group;
            }
        }

        return collect(array_values($assignments));
    }

    /**
     * Get user initials from their name
     * - Single word: returns first two letters
     * - Multiple words: returns first letter of first and second word
     */
    public function initiales(): string
    {
        // Remove extra spaces at beginning/end and replace multiple internal spaces with a single one
        $nom = trim(preg_replace('/\s+/', ' ', $this->name));

        // Split the string into words
        $mots = explode(' ', $nom);

        if (count($mots) === 1) {
            // Single word: return the first two letters
            return strtoupper(substr($mots[0], 0, 2));
        }
        // Two or more words: return the first letter of the first and second word
        return strtoupper(substr($mots[0], 0, 1) . substr($mots[1], 0, 1));
    }

    /**
     * Get the role label for the user
     */
    public function getRoleLabel(): string
    {
        return self::ROLES[$this->role] ?? 'Unknown';
    }

    /**
     * Role checks
     */
    public function isDisabled(): bool
    {
        return $this->role === self::ROLE_DISABLED;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isAuditor(): bool
    {
        return $this->role === self::ROLE_AUDITOR;
    }

    public function isAPI(): bool
    {
        return $this->role === self::ROLE_API;
    }

    public function isAuditee(): bool
    {
        return $this->role === self::ROLE_AUDITEE;
    }

    /**
     * Whether this user sees data across all users, or only their own.
     * Sole authority for this decision: the admin/user's session preference is
     * only honored after re-checking the role here, never trusted alone.
     */
    public function seesAllData(): bool
    {
        if ($this->isAdmin() || $this->isUser()) {
            return (bool) session('group_view', true);
        }

        return $this->isAuditor() || $this->isAPI();
    }

}
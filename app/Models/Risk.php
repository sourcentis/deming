<?php

namespace App\Models;

use App\Services\RiskScoringService;
use App\Traits\Auditable;
use App\Traits\HasAiAnalysis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Risque de sécurité de l'information.
 *
 * Le calcul du score est entièrement délégué au RiskScoringService
 * afin de rester indépendant de la formule active.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $owner_id
 * @property int $probability         Utilisé par formules : probability_x_impact, additive, max_pi
 * @property string|null $probability_comment
 * @property int $impact
 * @property string|null $impact_comment
 * @property int|null $exposure            Utilisé par formule : likelihood_x_impact
 * @property int|null $vulnerability       Utilisé par formule : likelihood_x_impact
 * @property int|null $residual_risk       Risque résiduel saisi manuellement
 * @property string $status
 * @property string|null $status_comment
 * @property int $review_frequency    (mois)
 * @property \Carbon\Carbon|null $next_review_at
 *
 * Accesseurs calculés (via RiskScoringService) :
 * @property-read int $risk_score
 * @property-read int|null $risk_likelihood
 * @property-read string $risk_level       'low'|'medium'|'high'|'critical'
 * @property-read string $risk_level_label
 * @property-read string $risk_level_color
 * @property-read bool $is_overdue
 */
class Risk extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasAiAnalysis;

    // -------------------------------------------------------------------------
    // Constantes de statut
    // -------------------------------------------------------------------------

    const STATUS_NOT_EVALUATED = 'not_evaluated';
    const STATUS_NOT_ACCEPTED = 'not_accepted';
    const STATUS_TEMPORARILY_ACCEPTED = 'temporarily_accepted';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_MITIGATED = 'mitigated';
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_AVOIDED = 'avoided';

    const STATUS_LABELS = [
        self::STATUS_NOT_EVALUATED => 'cruds.risk.status.not_evaluated',
        self::STATUS_NOT_ACCEPTED => 'cruds.risk.status.not_accepted',
        self::STATUS_TEMPORARILY_ACCEPTED => 'cruds.risk.status.temporarily_accepted',
        self::STATUS_ACCEPTED => 'cruds.risk.status.accepted',
        self::STATUS_MITIGATED => 'cruds.risk.status.mitigated',
        self::STATUS_TRANSFERRED => 'cruds.risk.status.transferred',
        self::STATUS_AVOIDED => 'cruds.risk.status.avoided',
    ];

    const STATUS_COLORS = [
        self::STATUS_NOT_EVALUATED => 'secondary',
        self::STATUS_NOT_ACCEPTED => 'danger',
        self::STATUS_TEMPORARILY_ACCEPTED => 'warning',
        self::STATUS_ACCEPTED => 'success',
        self::STATUS_MITIGATED => 'info',
        self::STATUS_TRANSFERRED => 'light',
        self::STATUS_AVOIDED => 'dark',
    ];

    /**
     * Traitements MONARC : réutilisent les clés de Risk::status existantes,
     * seuls les libellés changent (temporarily_accepted et avoided n'ont pas
     * d'équivalent MONARC et sont donc exclus de ce mapping).
     */
    const STATUS_MONARC = [
        self::STATUS_NOT_EVALUATED => 'cruds.risk.status_monarc.not_treated',
        self::STATUS_MITIGATED => 'cruds.risk.status_monarc.reduction',
        self::STATUS_NOT_ACCEPTED => 'cruds.risk.status_monarc.denied',
        self::STATUS_ACCEPTED => 'cruds.risk.status_monarc.accepted',
        self::STATUS_TRANSFERRED => 'cruds.risk.status_monarc.shared',
    ];

    // -------------------------------------------------------------------------
    // Fillable / Casts
    // -------------------------------------------------------------------------

    protected $hidden = self::AI_COLUMNS;

    protected $fillable = [
        'name', 'description', 'owner_id',
        'probability', 'probability_comment',
        'impact', 'impact_comment',
        'exposure', 'vulnerability', 'residual_risk',
        'status', 'status_comment',
        'review_frequency', 'next_review_at',
    ];

    protected $casts = [
        'next_review_at' => 'date',
        'probability' => 'integer',
        'impact' => 'integer',
        'exposure' => 'integer',
        'vulnerability' => 'integer',
        'residual_risk' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class, 'control_risk');
    }

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(Action::class, 'action_risk');
    }

    // -------------------------------------------------------------------------
    // Accesseurs calculés — délèguent au RiskScoringService
    //
    // Le service est résolu via le conteneur Laravel (singleton enregistré
    // dans AppServiceProvider). Le résultat est mis en cache sur l'instance
    // pour éviter des appels répétés dans une même requête.
    // -------------------------------------------------------------------------

    /** @var array|null Cache du résultat de scoring pour cet objet */
    private ?array $scoringResult = null;

    private function scoringResult(): array
    {
        if ($this->scoringResult === null) {
            $this->scoringResult = app(RiskScoringService::class)->score($this);
        }

        return $this->scoringResult;
    }

    /** Invalide le cache de scoring (utile après modification des attributs) */
    public function invalidateScoringCache(): void
    {
        $this->scoringResult = null;
    }

    /** Score brut calculé selon la formule active */
    public function getRiskScoreAttribute(): int
    {
        return $this->scoringResult()['score'];
    }

    /**
     * Vraisemblance intermédiaire (Exposition + Vulnérabilité).
     * Retourne null si la formule active ne l'utilise pas.
     */
    public function getRiskLikelihoodAttribute(): ?int
    {
        return $this->scoringResult()['likelihood'];
    }

    /** Niveau de risque : 'low'|'medium'|'high'|'critical' */
    public function getRiskLevelAttribute(): string
    {
        return $this->scoringResult()['level'];
    }

    /** Label localisé du niveau (ex. "Élevé") */
    public function getRiskLevelLabelAttribute(): string
    {
        return $this->scoringResult()['label'];
    }

    /** Classe CSS MetroUI pour le badge du niveau (ex. "danger") */
    public function getRiskLevelColorAttribute(): string
    {
        return $this->scoringResult()['color'];
    }

    /** Indique si la prochaine revue est dépassée */
    public function getIsOverdueAttribute(): bool
    {
        return $this->next_review_at !== null
            && $this->next_review_at->isPast();
    }

    /** Libellé traduit du statut courant (cf. Exception::getStatusLabelAttribute) */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabel($this->status);
    }

    // -------------------------------------------------------------------------
    // Mode MONARC — Risk.status réutilisé comme traitement MONARC
    // -------------------------------------------------------------------------

    /**
     * Indique si la configuration de scoring active utilise la formule MONARC.
     * Accès null-safe : ne lève jamais d'exception si aucune configuration
     * n'est active (contrairement à RiskScoringConfig::active()).
     */
    public static function monarcMode(): bool
    {
        return RiskScoringConfig::where('is_active', true)->value('formula') === 'monarc';
    }

    /** Statuts à proposer au sélecteur : les 5 traitements MONARC si actif, sinon les 7 statuts Deming. */
    public static function availableStatuses(): array
    {
        return self::monarcMode() ? self::STATUS_MONARC : self::STATUS_LABELS;
    }

    /** Libellé traduit d'un statut, adapté au mode MONARC si actif (fallback Deming sinon). */
    public static function statusLabel(string $status): string
    {
        if (self::monarcMode() && array_key_exists($status, self::STATUS_MONARC)) {
            return trans(self::STATUS_MONARC[$status]);
        }

        return trans(self::STATUS_LABELS[$status] ?? $status);
    }

    // -------------------------------------------------------------------------
    // Helpers métier
    // -------------------------------------------------------------------------

    public function requiresControls(): bool
    {
        return $this->status === self::STATUS_MITIGATED;
    }

    public function requiresActions(): bool
    {
        return $this->status === self::STATUS_NOT_ACCEPTED;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('next_review_at')
            ->where('next_review_at', '<', now());
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('owner_id', $userId);
    }

    /**
     * Calcule le score brut selon la configuration de scoring active.
     *
     * @param  RiskScoringConfig  $config
     * @return int
     */
    public function computedScore(RiskScoringConfig $config): int
    {
        if ($config->usesMonarc()) {
            return $this->impact * ($this->probability ?? 0) * ($this->vulnerability ?? 0);
        }

        if ($config->usesLikelihood()) {
            $likelihood = ($this->exposure ?? 0) + ($this->vulnerability ?? 0);
            return $likelihood * $this->impact;
        }

        return $this->probability * $this->impact;
    }

}

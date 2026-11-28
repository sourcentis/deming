<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Configuration du moteur de scoring des risques.
 *
 * @property int    $id
 * @property string $name
 * @property string $formula
 * @property bool   $is_active
 * @property array  $probability_levels
 * @property array  $impact_levels
 * @property array|null $exposure_levels
 * @property array|null $vulnerability_levels
 * @property array  $risk_thresholds
 */
class RiskScoringConfig extends Model
{
    protected $fillable = [
        'name', 'formula', 'is_active',
        'probability_levels', 'impact_levels',
        'exposure_levels', 'vulnerability_levels',
        'risk_thresholds',
        'value',
        'label',
        'description',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'probability_levels'   => 'array',
        'impact_levels'        => 'array',
        'exposure_levels'      => 'array',
        'vulnerability_levels' => 'array',
        'risk_thresholds'      => 'array',
    ];

    // -------------------------------------------------------------------------
    // Récupération de la config active (avec cache requête)
    // -------------------------------------------------------------------------

    private static ?self $activeInstance = null;

    /**
     * Retourne la configuration de scoring active.
     * Met en cache l'instance pour la durée de la requête.
     */
    public static function active(): self
    {
        if (self::$activeInstance === null) {
            self::$activeInstance = static::where('is_active', true)->firstOrFail();
        }

        return self::$activeInstance;
    }

    /** Invalide le cache (à appeler après activation d'une nouvelle config) */
    public static function clearCache(): void
    {
        self::$activeInstance = null;
    }

    // -------------------------------------------------------------------------
    // Activation
    // -------------------------------------------------------------------------

    /**
     * Active cette configuration et désactive toutes les autres.
     * Opération atomique via transaction.
     */
    public function activate(): void
    {
        \DB::transaction(function () {
            self::query()->update(['is_active' => false]);
            $this->update(['is_active' => true]);
            self::clearCache();
        });
    }

    // -------------------------------------------------------------------------
    // Helpers sur les niveaux
    // -------------------------------------------------------------------------

    /**
     * Retourne le label d'un niveau pour un champ donné.
     *
     * @param string $field   'probability'|'impact'|'exposure'|'vulnerability'
     * @param int    $value   Valeur numérique du niveau
     */
    public function levelLabel(string $field, int $value): string
    {
        $levels = $this->{$field . '_levels'} ?? [];

        foreach ($levels as $level) {
            if ((int) $level['value'] === $value) {
                return $level['label'];
            }
        }

        return (string) $value;
    }

    /**
     * Retourne la description d'un niveau pour un champ donné (chaîne vide si absente).
     *
     * @param string $field   'probability'|'impact'|'exposure'|'vulnerability'
     * @param int    $value   Valeur numérique du niveau
     */
    public function levelDescription(string $field, int $value): string
    {
        foreach ($this->{$field . '_levels'} ?? [] as $level) {
            if ((int) $level['value'] === $value) {
                return (string) ($level['description'] ?? '');
            }
        }

        return '';
    }

    /**
     * Retourne toutes les valeurs possibles pour un champ de niveau.
     *
     * @return int[]
     */
    public function levelValues(string $field): array
    {
        return array_column($this->{$field . '_levels'} ?? [], 'value');
    }

    /**
     * Indique si cette formule utilise exposition + vulnérabilité
     * (au lieu de probabilité directe).
     */
    public function usesLikelihood(): bool
    {
        return $this->formula === 'likelihood_x_impact';
    }

    /**
     * Indique si la formule active est MONARC (Impact × Menace × Vulnérabilité).
     */
    public function usesMonarc(): bool
    {
        return $this->formula === 'monarc';
    }

    // -------------------------------------------------------------------------
    // Helpers sur les seuils
    // -------------------------------------------------------------------------

    /**
     * Retourne le seuil correspondant à un score donné.
     *
     * @return array{level: string, label: string, color: string}
     */
    public function thresholdFor(int $score): array
    {
        foreach ($this->risk_thresholds as $threshold) {
            if ($threshold['max'] === null || $score <= $threshold['max']) {
                return $threshold;
            }
        }

        // Fallback sur le dernier seuil défini
        return end($this->risk_thresholds);
    }

    /**
     * Score maximum théorique pour cette configuration.
     * Utile pour normaliser l'affichage de la matrice.
     */
    public function maxScore(): int
    {
        return match ($this->formula) {
            'likelihood_x_impact' => ($this->maxLevelValue('exposure') + $this->maxLevelValue('vulnerability'))
                * $this->maxLevelValue('impact'),
            'additive'   => $this->maxLevelValue('probability') + $this->maxLevelValue('impact'),
            'max_pi'     => max($this->maxLevelValue('probability'), $this->maxLevelValue('impact')),
            'monarc'     => $this->maxLevelValue('impact') * $this->maxLevelValue('probability') * $this->maxLevelValue('vulnerability'),
            default      => $this->maxLevelValue('probability') * $this->maxLevelValue('impact'),
        };
    }

    private function maxLevelValue(string $field): int
    {
        $values = $this->levelValues($field);
        return $values ? max($values) : 0;
    }

    public function thresholdIndexFor(int $score): int
    {
        foreach ($this->risk_thresholds as $i => $threshold) {
            if ($threshold['max'] === null || $score <= $threshold['max']) {
                return $i;
            }
        }
        return count($this->risk_thresholds) - 1;
    }

}
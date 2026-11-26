<?php

namespace App\Services;

use App\Models\Risk;
use App\Models\RiskScoringConfig;

/**
 * Moteur de scoring des risques.
 *
 * Ce service centralise toute la logique de calcul afin que :
 *  - le modèle Risk reste un simple conteneur de données,
 *  - les controllers et vues n'aient aucune connaissance de la formule active,
 *  - l'ajout d'une nouvelle formule ne nécessite qu'une méthode ici + une entrée dans FORMULAS.
 *
 * Utilisation :
 *   $service = app(RiskScoringService::class);
 *   $result  = $service->score($risk);
 *   // => ['score' => 12, 'level' => 'high', 'label' => 'Élevé', 'color' => 'danger']
 *
 * Enregistrement dans AppServiceProvider :
 *   $this->app->singleton(RiskScoringService::class);
 */
class RiskScoringService
{
    // -------------------------------------------------------------------------
    // Catalogue des formules disponibles
    // -------------------------------------------------------------------------

    /**
     * Liste des formules proposées dans l'interface de configuration.
     *
     * Clé = valeur stockée en base.
     * Valeur = [label affiché, description, champs requis sur le risque]
     */
    public const FORMULAS = [
        'probability_x_impact' => [
            'label'                  => 'Probabilité × Impact',
            'description'            => 'Formule classique ISO 27005 / ISO 27001. Score = P × I. Matrice 5×5 standard.',
            'requires'               => ['probability', 'impact'],
            'requires_exposure'      => false,
            'requires_vulnerability' => false,
        ],
        'likelihood_x_impact' => [
            'label'                  => 'Vraisemblance × Impact (BSI 200-3)',
            'description'            => 'Méthode ISACA / BSI IT-Grundschutz. Vraisemblance = Exposition + Vulnérabilité. Score = V × I.',
            'requires'               => ['exposure', 'vulnerability', 'impact'],
            'requires_exposure'      => true,
            'requires_vulnerability' => false,
        ],
        'additive' => [
            'label'                  => 'Probabilité + Impact',
            'description'            => 'Méthode additive simplifiée. Score = P + I. Appropriée pour un premier triage rapide.',
            'requires'               => ['probability', 'impact'],
            'requires_exposure'      => false,
            'requires_vulnerability' => false,
        ],
        'max_pi' => [
            'label'                  => 'max(Probabilité, Impact)',
            'description'            => 'Approche conservatrice : le score est dominé par la dimension la plus défavorable.',
            'requires'               => ['probability', 'impact'],
            'requires_exposure'      => false,
            'requires_vulnerability' => false,
        ],
        'monarc' => [
            'label'                  => 'Impact × Menace × Vulnérabilité',
            'description'            => 'Méthode MONARC. Score = Impact × Menace × Vulnérabilité. Max = 4 × 4 × 5 = 80.',
            'requires'               => ['probability', 'vulnerability', 'impact'],
            'requires_exposure'      => false,
            'requires_vulnerability' => true,
        ],
    ];

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    private ?RiskScoringConfig $config = null;

    public function __construct()
    {
        // config loaded lazily on first use
    }

    // -------------------------------------------------------------------------
    // API principale
    // -------------------------------------------------------------------------

    /**
     * Calcule le score et le niveau de risque pour un risque donné.
     *
     * @return array{
     *   score: int,
     *   likelihood: int|null,
     *   level: string,
     *   label: string,
     *   color: string,
     *   max_score: int,
     * }
     */
    public function score(Risk $risk): array
    {
        [$score, $likelihood] = $this->calculate($risk);
        $threshold = $this->config()->thresholdFor($score);

        return [
            'score'      => $score,
            'likelihood' => $likelihood,           // null si formule sans exposition
            'level'      => $threshold['level'],
            'label'      => $threshold['label'],
            'color'      => $threshold['color'],
            'max_score'  => $this->config()->maxScore(),
        ];
    }

    /**
     * Expose la configuration active (pour les vues de formulaire).
     */
    public function config(): RiskScoringConfig
    {
        return $this->config ??= RiskScoringConfig::active();
    }

    /**
     * Expose le catalogue des formules disponibles.
     */
    public function availableFormulas(): array
    {
        return self::FORMULAS;
    }

    /**
     * Génère les données pour la matrice de risque.
     *
     * Retourne une structure [score][statut] => nombre de risques,
     * adaptée à la formule active (axes variables).
     *
     * @param  \Illuminate\Support\Collection $risks
     * @return array
     */
    public function buildMatrix(\Illuminate\Support\Collection $risks): array
    {
        $matrix = [];

        foreach ($risks as $risk) {
            $result = $this->score($risk);
            $x = $this->xAxisValue($risk);   // axe horizontal (impact)
            $y = $this->yAxisValue($risk);   // axe vertical (prob. ou vraisemblance)

            $matrix[$y][$x][] = [
                'id'     => $risk->id,
                'name'   => $risk->name,
                'score'  => $result['score'],
                'level'  => $result['level'],
                'color'  => $result['color'],
                'status' => $risk->status,
            ];
        }

        return $matrix;
    }

    /**
     * Labels et valeurs pour l'axe X de la matrice.
     * - Formules classiques : niveaux d'impact
     * - MONARC : produits distincts Menace × Vulnérabilité (14 valeurs possibles)
     */
    public function matrixXAxis(): array
    {
        if ($this->config()->usesMonarc()) {
            return $this->monarcThreatVulnProducts();
        }

        return $this->config()->impact_levels ?? [];
    }

    /**
     * Labels et valeurs pour l'axe Y de la matrice.
     * - Formule likelihood : combinaisons expo + vuln
     * - MONARC : niveaux d'impact
     * - Autres : niveaux de probabilité
     */
    public function matrixYAxis(): array
    {
        if ($this->config()->usesMonarc()) {
            return $this->config()->impact_levels ?? [];
        }

        if ($this->config()->usesLikelihood()) {
            // Générer les valeurs de vraisemblance = toutes combinaisons exposition + vulnérabilité
            $exposures       = array_column($this->config()->exposure_levels ?? [], 'value');
            $vulnerabilities = array_column($this->config()->vulnerability_levels ?? [], 'value');
            $likelihoods     = [];

            foreach ($exposures as $e) {
                foreach ($vulnerabilities as $v) {
                    $likelihoods[$e + $v] = $e + $v;
                }
            }
            ksort($likelihoods);

            return array_map(
                fn($l) => ['value' => $l, 'label' => "Vraisemblance $l"],
                array_values($likelihoods)
            );
        }

        return $this->config()->probability_levels ?? [];
    }

    /**
     * Retourne toutes les valeurs entières de min à max du produit Menace × Vulnérabilité.
     * Avec les échelles par défaut MONARC (menace 0-4, vuln 0-5) :
     * [0,1,2,...,20] — 21 colonnes.
     */
    private function monarcThreatVulnProducts(): array
    {
        $threats = array_column($this->config()->probability_levels ?? [], 'value');
        $vulns   = array_column($this->config()->vulnerability_levels ?? [], 'value');
        $products = [];

        foreach ($threats as $t) {
            foreach ($vulns as $v) {
                $products[] = $t * $v;
            }
        }

        $min = count($products) ? min($products) : 0;
        $max = count($products) ? max($products) : 0;

        return array_map(
            fn($p) => ['value' => $p, 'label' => "M×V=$p"],
            range($min, $max)
        );
    }

    // -------------------------------------------------------------------------
    // Calcul interne par formule
    // -------------------------------------------------------------------------

    /**
     * @return array{int, int|null}  [score, likelihood|null]
     */
    private function calculate(Risk $risk): array
    {
        return match ($this->config()->formula) {
            'probability_x_impact' => $this->formulaProbabilityXImpact($risk),
            'likelihood_x_impact'  => $this->formulaLikelihoodXImpact($risk),
            'additive'             => $this->formulaAdditive($risk),
            'max_pi'               => $this->formulaMaxPI($risk),
            'monarc'               => $this->formulaMonarc($risk),
            default                => $this->formulaProbabilityXImpact($risk),
        };
    }

    /** Score = Probabilité × Impact */
    private function formulaProbabilityXImpact(Risk $risk): array
    {
        return [$risk->probability * $risk->impact, null];
    }

    /**
     * Vraisemblance = Exposition + Vulnérabilité
     * Score = Vraisemblance × Impact
     */
    private function formulaLikelihoodXImpact(Risk $risk): array
    {
        $likelihood = ($risk->exposure ?? 0) + ($risk->vulnerability ?? 0);
        return [$likelihood * $risk->impact, $likelihood];
    }

    /** Score = Probabilité + Impact */
    private function formulaAdditive(Risk $risk): array
    {
        return [$risk->probability + $risk->impact, null];
    }

    /** Score = max(Probabilité, Impact) */
    private function formulaMaxPI(Risk $risk): array
    {
        return [max($risk->probability, $risk->impact), null];
    }

    /** Score = Impact × Menace × Vulnérabilité (MONARC) */
    private function formulaMonarc(Risk $risk): array
    {
        return [$risk->impact * ($risk->probability ?? 0) * ($risk->vulnerability ?? 0), null];
    }

    // -------------------------------------------------------------------------
    // Axes matrice
    // -------------------------------------------------------------------------

    private function xAxisValue(Risk $risk): int
    {
        if ($this->config()->usesMonarc()) {
            return ($risk->probability ?? 0) * ($risk->vulnerability ?? 0);
        }

        return $risk->impact ?? 1;
    }

    private function yAxisValue(Risk $risk): int
    {
        if ($this->config()->usesMonarc()) {
            return $risk->impact ?? 0;
        }

        if ($this->config()->usesLikelihood()) {
            return ($risk->exposure ?? 0) + ($risk->vulnerability ?? 0);
        }

        return $risk->probability ?? 1;
    }

    // -------------------------------------------------------------------------
    // Valeurs par défaut par formule
    // -------------------------------------------------------------------------

    /**
     * Retourne les niveaux et seuils par défaut pour une formule donnée.
     * Source unique de vérité pour la création et le formulaire JS.
     */
    public static function defaultsForFormula(string $formula): array
    {
        $prob = self::defaultProbabilityLevels();
        $imp  = self::defaultImpactLevels();
        $thr  = self::defaultThresholdLabels();

        return match ($formula) {

            'monarc' => [
                'probability_levels'   => self::defaultMonarcThreatLevels(),
                'exposure_levels'      => null,
                'vulnerability_levels' => self::defaultMonarcVulnerabilityLevels(),
                'impact_levels'        => self::defaultMonarcImpactLevels(),
                'risk_thresholds'      => [
                    // Échelle 0–80 (4 × 4 × 5)
                    ['level' => 'low',      'label' => $thr['low'],      'max' => 16,   'color' => '#3AB87A'],
                    ['level' => 'medium',   'label' => $thr['medium'],   'max' => 36,   'color' => '#E09B1A'],
                    ['level' => 'high',     'label' => $thr['high'],     'max' => 56,   'color' => '#E8731A'],
                    ['level' => 'critical', 'label' => $thr['critical'], 'max' => null, 'color' => '#D94F45'],
                ],
            ],

            'likelihood_x_impact' => [
                'probability_levels'   => null,
                'exposure_levels'      => self::defaultExposureLevels(),
                'vulnerability_levels' => self::defaultVulnerabilityLevels(),
                'impact_levels'        => $imp,
                'risk_thresholds'      => [
                    // Échelle 0–30 ((E+V) × I : max (2+4)×5)
                    ['level' => 'low',      'label' => $thr['low'],      'max' => 6,    'color' => '#27ae60'],
                    ['level' => 'medium',   'label' => $thr['medium'],   'max' => 12,   'color' => '#f39c12'],
                    ['level' => 'high',     'label' => $thr['high'],     'max' => 20,   'color' => '#e74c3c'],
                    ['level' => 'critical', 'label' => $thr['critical'], 'max' => null, 'color' => '#c0392b'],
                ],
            ],

            'additive' => [
                'probability_levels'   => $prob,
                'exposure_levels'      => null,
                'vulnerability_levels' => null,
                'impact_levels'        => $imp,
                'risk_thresholds'      => [
                    // Échelle 2–10 (P + I)
                    ['level' => 'low',      'label' => $thr['low'],      'max' => 4,    'color' => '#27ae60'],
                    ['level' => 'medium',   'label' => $thr['medium'],   'max' => 6,    'color' => '#f39c12'],
                    ['level' => 'high',     'label' => $thr['high'],     'max' => 8,    'color' => '#e74c3c'],
                    ['level' => 'critical', 'label' => $thr['critical'], 'max' => null, 'color' => '#c0392b'],
                ],
            ],

            'max_pi' => [
                'probability_levels'   => $prob,
                'exposure_levels'      => null,
                'vulnerability_levels' => null,
                'impact_levels'        => $imp,
                'risk_thresholds'      => [
                    // Échelle 1–5 (max(P, I))
                    ['level' => 'low',      'label' => $thr['low'],      'max' => 2,    'color' => '#27ae60'],
                    ['level' => 'medium',   'label' => $thr['medium'],   'max' => 3,    'color' => '#f39c12'],
                    ['level' => 'high',     'label' => $thr['high'],     'max' => 4,    'color' => '#e74c3c'],
                    ['level' => 'critical', 'label' => $thr['critical'], 'max' => null, 'color' => '#c0392b'],
                ],
            ],

            default => [ // probability_x_impact
                'probability_levels'   => $prob,
                'exposure_levels'      => null,
                'vulnerability_levels' => null,
                'impact_levels'        => $imp,
                'risk_thresholds'      => [
                    // Échelle 1–25 (P × I)
                    ['level' => 'low',      'label' => $thr['low'],      'max' => 4,    'color' => '#27ae60'],
                    ['level' => 'medium',   'label' => $thr['medium'],   'max' => 9,    'color' => '#f39c12'],
                    ['level' => 'high',     'label' => $thr['high'],     'max' => 16,   'color' => '#e74c3c'],
                    ['level' => 'critical', 'label' => $thr['critical'], 'max' => null, 'color' => '#c0392b'],
                ],
            ],
        };
    }

    /** Retourne les défauts de toutes les formules (pour injection JS dans le formulaire). */
    public static function allFormulaDefaults(): array
    {
        $result = [];
        foreach (array_keys(self::FORMULAS) as $formula) {
            $result[$formula] = self::defaultsForFormula($formula);
        }
        return $result;
    }

    private static function defaultThresholdLabels(): array
    {
        return [
            'low'      => __('cruds.risk_scoring.defaults.risk_thresholds.low'),
            'medium'   => __('cruds.risk_scoring.defaults.risk_thresholds.medium'),
            'high'     => __('cruds.risk_scoring.defaults.risk_thresholds.high'),
            'critical' => __('cruds.risk_scoring.defaults.risk_thresholds.critical'),
        ];
    }

    private static function defaultProbabilityLevels(): array
    {
        return [
            ['value' => 1, 'label' => __('cruds.risk_scoring.defaults.probability_levels.rare'),        'description' => ''],
            ['value' => 2, 'label' => __('cruds.risk_scoring.defaults.probability_levels.unlikely'),    'description' => ''],
            ['value' => 3, 'label' => __('cruds.risk_scoring.defaults.probability_levels.possible'),    'description' => ''],
            ['value' => 4, 'label' => __('cruds.risk_scoring.defaults.probability_levels.likely'),      'description' => ''],
            ['value' => 5, 'label' => __('cruds.risk_scoring.defaults.probability_levels.very_likely'), 'description' => ''],
        ];
    }

    private static function defaultImpactLevels(): array
    {
        return [
            ['value' => 1, 'label' => __('cruds.risk_scoring.defaults.impact_levels.negligible'), 'description' => ''],
            ['value' => 2, 'label' => __('cruds.risk_scoring.defaults.impact_levels.low'),        'description' => ''],
            ['value' => 3, 'label' => __('cruds.risk_scoring.defaults.impact_levels.moderate'),   'description' => ''],
            ['value' => 4, 'label' => __('cruds.risk_scoring.defaults.impact_levels.high'),       'description' => ''],
            ['value' => 5, 'label' => __('cruds.risk_scoring.defaults.impact_levels.critical'),   'description' => ''],
        ];
    }

    private static function defaultExposureLevels(): array
    {
        return [
            ['value' => 0, 'label' => __('cruds.risk_scoring.defaults.exposure_levels.offline'),  'description' => ''],
            ['value' => 1, 'label' => __('cruds.risk_scoring.defaults.exposure_levels.internal'), 'description' => ''],
            ['value' => 2, 'label' => __('cruds.risk_scoring.defaults.exposure_levels.internet'), 'description' => ''],
        ];
    }

    private static function defaultVulnerabilityLevels(): array
    {
        return [
            ['value' => 1, 'label' => __('cruds.risk_scoring.defaults.vulnerability_levels.none'),            'description' => ''],
            ['value' => 2, 'label' => __('cruds.risk_scoring.defaults.vulnerability_levels.known'),           'description' => ''],
            ['value' => 3, 'label' => __('cruds.risk_scoring.defaults.vulnerability_levels.exploitable_int'), 'description' => ''],
            ['value' => 4, 'label' => __('cruds.risk_scoring.defaults.vulnerability_levels.exploitable_ext'), 'description' => ''],
        ];
    }

    private static function defaultMonarcThreatLevels(): array
    {
        return array_map(
            fn ($i) => [
                'value'       => $i,
                'label'       => __("cruds.risk_scoring.defaults.monarc_threat_levels.$i.label"),
                'description' => __("cruds.risk_scoring.defaults.monarc_threat_levels.$i.description"),
            ],
            range(0, 4)
        );
    }

    private static function defaultMonarcVulnerabilityLevels(): array
    {
        return array_map(
            fn ($i) => [
                'value'       => $i,
                'label'       => __("cruds.risk_scoring.defaults.monarc_vulnerability_levels.$i.label"),
                'description' => __("cruds.risk_scoring.defaults.monarc_vulnerability_levels.$i.description"),
            ],
            range(0, 5)
        );
    }

    private static function defaultMonarcImpactLevels(): array
    {
        return array_map(
            fn ($i) => [
                'value'       => $i,
                'label'       => __("cruds.risk_scoring.defaults.monarc_impact_levels.$i.label"),
                'description' => __("cruds.risk_scoring.defaults.monarc_impact_levels.$i.description"),
            ],
            range(0, 4)
        );
    }
}
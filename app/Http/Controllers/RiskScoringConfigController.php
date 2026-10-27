<?php

namespace App\Http\Controllers;

use App\Models\RiskScoringConfig;
use App\Services\RiskScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Configuration du moteur de scoring des risques.
 * Pattern URL calqué sur Deming (/risk/scoring/store, /risk/scoring/{id}/save, etc.)
 */
class RiskScoringConfigController extends Controller
{

    public function __construct(private readonly RiskScoringService $scoringService)
    {
    }

    private function checkAdmin(): void
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }
    }

    // Data of the "Classification" tab of the settings page
    public function tabData(): array
    {
        return [
            'configs' => RiskScoringConfig::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'formulas' => $this->scoringService->availableFormulas(),
        ];
    }

    public function create(): View
    {
        $this->checkAdmin();

        $formulas       = $this->scoringService->availableFormulas();
        $defaultFormula = 'probability_x_impact';
        $defaults       = RiskScoringService::defaultsForFormula($defaultFormula);

        $config = new RiskScoringConfig(array_merge(['formula' => $defaultFormula], $defaults));

        $probLevels      = $config->probability_levels   ?? [];
        $impLevels       = $config->impact_levels        ?? [];
        $expLevels       = $config->exposure_levels      ?? [];
        $vulnLevels      = $config->vulnerability_levels ?? [];
        $thresholds      = $config->risk_thresholds      ?? [];
        $formulaDefaults = RiskScoringService::allFormulaDefaults();

        return view('risks.scoring.form', compact('config', 'formulas', 'probLevels', 'impLevels', 'expLevels', 'vulnLevels', 'thresholds', 'formulaDefaults'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkAdmin();

        $validated = $this->validateConfig($request);
        $validated['is_active'] = false;

        RiskScoringConfig::create($validated);

        return redirect('/config/parameters?tab=scoring')
            ->with('success', __('Configuration créée. Activez-la pour l\'appliquer.'));
    }

    public function edit(int $id): View
    {
        $this->checkAdmin();

        $config   = RiskScoringConfig::findOrFail($id);
        $formulas = $this->scoringService->availableFormulas();

        $probLevels      = $config->probability_levels   ?? [];
        $impLevels       = $config->impact_levels        ?? [];
        $expLevels       = $config->exposure_levels      ?? [];
        $vulnLevels      = $config->vulnerability_levels ?? [];
        $thresholds      = $config->risk_thresholds      ?? [];
        $formulaDefaults = RiskScoringService::allFormulaDefaults();

        return view('risks.scoring.form', compact('config', 'formulas', 'probLevels', 'impLevels', 'expLevels', 'vulnLevels', 'thresholds', 'formulaDefaults'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkAdmin();

        $config    = RiskScoringConfig::findOrFail($id);
        $validated = $this->validateConfig($request);
        $config->update($validated);

        if ($config->is_active) {
            RiskScoringConfig::clearCache();
        }

        return redirect('/config/parameters?tab=scoring')
            ->with('success', __('Configuration mise à jour.'));
    }

    public function activate(int $id): RedirectResponse
    {
        $this->checkAdmin();

        $config = RiskScoringConfig::findOrFail($id);
        $config->activate();

        return redirect('/config/parameters?tab=scoring')
            ->with('messages', [__('Configuration "' . $config->name . '" activée.')]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAdmin();

        $config = RiskScoringConfig::findOrFail($id);

        if ($config->is_active) {
            return back()->with('errors', [__('Impossible de supprimer la configuration active.')]);
        }

        $config->delete();

        return redirect('/config/parameters?tab=scoring')
            ->with('messages', [__('Configuration supprimée.')]);
    }

    private function validateConfig(Request $request): array
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'formula' => ['required', 'in:' . implode(',', array_keys(RiskScoringService::FORMULAS))],

            'probability_levels'              => ['nullable', 'array'],
            'probability_levels.*.value'      => ['required_with:probability_levels', 'integer'],
            'probability_levels.*.label'      => ['required_with:probability_levels', 'string', 'max:100'],
            'probability_levels.*.description'=> ['nullable', 'string', 'max:255'],

            'impact_levels'              => ['required', 'array', 'min:2'],
            'impact_levels.*.value'      => ['required', 'integer', 'min:0', 'max:9'],
            'impact_levels.*.label'      => ['required', 'string', 'max:100'],
            'impact_levels.*.description'=> ['nullable', 'string', 'max:255'],

            'exposure_levels'              => ['nullable', 'array'],
            'exposure_levels.*.value'      => ['nullable', 'integer', 'min:0', 'max:9'],
            'exposure_levels.*.label'      => ['nullable', 'string', 'max:100'],
            'exposure_levels.*.description'=> ['nullable', 'string', 'max:255'],

            'vulnerability_levels'              => ['nullable', 'array'],
            'vulnerability_levels.*.value'      => ['nullable', 'integer', 'min:0', 'max:9'],
            'vulnerability_levels.*.label'      => ['nullable', 'string', 'max:100'],
            'vulnerability_levels.*.description'=> ['nullable', 'string', 'max:255'],

            'risk_thresholds'        => ['required', 'array', 'min:2'],
            'risk_thresholds.*.level'=> ['required', 'string'],
            'risk_thresholds.*.label'=> ['required', 'string', 'max:100'],
            'risk_thresholds.*.max'  => ['nullable', 'integer', 'min:1'],
            'risk_thresholds.*.color'=> ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $formula            = RiskScoringService::FORMULAS[$data['formula']];
        $needsExposure      = $formula['requires_exposure'];
        $needsVulnerability = $formula['requires_vulnerability'];

        if (! $needsExposure) {
            $data['probability_levels'] = $data['probability_levels'] ?? [];
            $data['exposure_levels']    = null;
            if (! $needsVulnerability) {
                $data['vulnerability_levels'] = null;
            }
        }

        // Le dernier seuil n'a pas de borne supérieure — réindexer pour éviter les trous d'indices
        $data['risk_thresholds'] = array_values($data['risk_thresholds']);
        $data['risk_thresholds'][count($data['risk_thresholds']) - 1]['max'] = null;

        return $data;
    }

// -------------------------------------------------------------------------
// Helpers couleurs : migration legacy (noms de classes MetroUI) → hex
// -------------------------------------------------------------------------

    /*
        private const COLOR_MAP = [
            'success'   => '#27ae60',
            'warning'   => '#f39c12',
            'danger'    => '#e74c3c',
            'alert'     => '#c0392b',
            'info'      => '#2980b9',
            'secondary' => '#7f8c8d',
        ];
    */
}
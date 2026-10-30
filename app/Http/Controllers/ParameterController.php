<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * "Settings" page (administrators only): one tab per settings screen.
 * Each tab keeps its own controller for its data and its forms.
 */
class ParameterController extends Controller
{
    public const TABS = ['documents', 'templates', 'scoring', 'ai', 'notifications'];

    public function index(Request $request): View
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tabs = self::tabs();
        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : $tabs[0];

        return view('config.parameters', [
            'tab' => $tab,
            'visible' => $tabs,
            'scoring' => app(RiskScoringConfigController::class)->tabData(),
            'documents' => app(DocumentController::class)->tabData(),
            'ai' => Configuration::aiActivated() ? app(AiConfigController::class)->tabData() : [],
            'notifications' => app(ConfigurationController::class)->tabData(),
        ]);
    }

    // Tabs shown: the AI tab only when AI_ACTIVATED=true
    public static function tabs(): array
    {
        return Configuration::aiActivated() ? self::TABS : array_values(array_diff(self::TABS, ['ai']));
    }

    // Former URLs of the settings screens open the matching tab
    public function legacy(string $tab): RedirectResponse
    {
        return redirect('/config/parameters?tab=' . $tab);
    }
}

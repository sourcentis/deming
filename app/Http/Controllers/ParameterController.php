<?php

namespace App\Http\Controllers;

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

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : self::TABS[0];

        return view('config.parameters', [
            'tab' => $tab,
            'scoring' => app(RiskScoringConfigController::class)->tabData(),
            'documents' => app(DocumentController::class)->tabData(),
            'ai' => app(AiConfigController::class)->tabData(),
            'notifications' => app(ConfigurationController::class)->tabData(),
        ]);
    }

    // Former URLs of the settings screens open the matching tab
    public function legacy(string $tab): RedirectResponse
    {
        return redirect('/config/parameters?tab=' . $tab);
    }
}

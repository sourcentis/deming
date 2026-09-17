<?php

namespace App\Http\Controllers;

use App\Exports\ControlMappingsExport;
use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Framework;
use App\Services\CrosswalkService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ControlMappingController extends Controller
{
    public function __construct(private readonly CrosswalkService $crosswalkService)
    {
        parent::__construct();
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $mappings = $this->crosswalkService
            ->filteredMappings($filters)
            ->orderBy('source_control_id')
            ->orderBy('target_control_id')
            ->paginate(50)
            ->withQueryString();

        return view('crosswalk.index', [
            'mappings' => $mappings,
            'frameworks' => Framework::query()->orderBy('code')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->requireAdmin();

        return view('crosswalk.create', [
            'mapping' => new ControlMapping,
            'controls' => $this->selectableControls(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireAdmin();

        $mapping = new ControlMapping;
        $this->persist($request, $mapping);

        return redirect()->route('crosswalk.show', $mapping);
    }

    public function show(ControlMapping $controlMapping): View
    {
        $controlMapping->load([
            'sourceControl.domain',
            'targetControl.domain',
            'validator',
        ]);

        return view('crosswalk.show', ['mapping' => $controlMapping]);
    }

    public function edit(ControlMapping $controlMapping): View
    {
        $this->requireAdmin();
        $controlMapping->load(['sourceControl.domain', 'targetControl.domain']);

        return view('crosswalk.edit', [
            'mapping' => $controlMapping,
            'controls' => $this->selectableControls(),
        ]);
    }

    public function update(Request $request, ControlMapping $controlMapping): RedirectResponse
    {
        $this->requireAdmin();
        $this->persist($request, $controlMapping);

        return redirect()->route('crosswalk.show', $controlMapping);
    }

    public function destroy(ControlMapping $controlMapping): RedirectResponse
    {
        $this->requireAdmin();
        $controlMapping->delete();

        return redirect()->route('crosswalk.index');
    }

    public function matrix(Request $request): View
    {
        $filters = $this->filters($request, true);
        $sourceFramework = $filters['source_framework'] ?? null;
        $targetFramework = $filters['target_framework'] ?? null;
        $matrix = null;

        if ($sourceFramework && $targetFramework && $sourceFramework !== $targetFramework) {
            $matrix = $this->crosswalkService->matrix(
                $sourceFramework,
                $targetFramework,
                $filters
            );
        }

        return view('crosswalk.matrix', [
            'frameworks' => Framework::query()->orderBy('code')->get(),
            'filters' => $filters,
            'matrix' => $matrix,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $directional = $request->boolean('directional');
        $filters = $this->filters($request, $directional);
        $filters['directional'] = $directional;
        $rows = $this->crosswalkService->exportRows($filters);

        return Excel::download(
            new ControlMappingsExport($rows),
            'control-mappings-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function persist(Request $request, ControlMapping $mapping): void
    {
        $validated = $request->validate([
            'source_control_id' => [
                'required',
                'integer',
                'exists:controls,id',
                'different:target_control_id',
            ],
            'target_control_id' => [
                'required',
                'integer',
                'exists:controls,id',
                Rule::unique('control_mappings', 'target_control_id')
                    ->where(fn ($query) => $query->where(
                        'source_control_id',
                        $request->input('source_control_id')
                    ))
                    ->ignore($mapping->id),
            ],
            'mapping_type' => ['required', Rule::in(ControlMapping::MAPPING_TYPES)],
            'coverage' => ['nullable', Rule::in(ControlMapping::COVERAGE_LEVELS)],
            'confidence' => ['nullable', 'numeric', 'between:0,100'],
            'rationale' => ['nullable', 'string'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'validated' => ['nullable', 'boolean'],
        ]);

        $isValidated = $request->boolean('validated');
        $validated['validated'] = $isValidated;
        $validated['validated_by'] = $isValidated ? $request->user()->id : null;
        $validated['validated_at'] = $isValidated ? now() : null;

        $mapping->fill($validated);
        $mapping->save();
    }

    private function filters(Request $request, bool $directional = false): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'source_framework' => ['nullable', 'string', 'exists:frameworks,code'],
            'target_framework' => ['nullable', 'string', 'exists:frameworks,code'],
            'mapping_type' => [
                'nullable',
                Rule::in($directional
                    ? ControlMapping::DIRECTIONAL_MAPPING_TYPES
                    : ControlMapping::MAPPING_TYPES),
            ],
            'coverage' => ['nullable', Rule::in(ControlMapping::COVERAGE_LEVELS)],
            'unmapped' => ['nullable', 'boolean'],
            'directional' => ['nullable', 'boolean'],
        ]);
    }

    private function selectableControls(): Collection
    {
        return Control::query()
            ->with('domain')
            ->join('domains', 'domains.id', '=', 'controls.domain_id')
            ->select('controls.*')
            ->orderBy('domains.framework')
            ->orderBy('controls.clause')
            ->get();
    }

    private function requireAdmin(): void
    {
        abort_if(
            ! auth()->user()->isAdmin(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );
    }
}

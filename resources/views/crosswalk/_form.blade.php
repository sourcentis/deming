@php
    $controlsByFramework = $controls->groupBy(fn ($control) => $control->domain->framework ?? '');
@endphp

@include('partials.errors')

<div class="grid">
    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.source') }}</strong>
        </div>
        <div class="cell-lg-8 cell-md-9">
            <select name="source_control_id" data-role="select" data-filter="true" required>
                <option value="">-- {{ trans('cruds.crosswalk.choose_control') }} --</option>
                @foreach($controlsByFramework as $framework => $frameworkControls)
                    <optgroup label="{{ $framework }}">
                        @foreach($frameworkControls as $control)
                            <option value="{{ $control->id }}"
                                {{ (string) old('source_control_id', $mapping->source_control_id) === (string) $control->id ? 'selected' : '' }}>
                                {{ trim($control->clause) }} – {{ $control->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.target') }}</strong>
        </div>
        <div class="cell-lg-8 cell-md-9">
            <select name="target_control_id" data-role="select" data-filter="true" required>
                <option value="">-- {{ trans('cruds.crosswalk.choose_control') }} --</option>
                @foreach($controlsByFramework as $framework => $frameworkControls)
                    <optgroup label="{{ $framework }}">
                        @foreach($frameworkControls as $control)
                            <option value="{{ $control->id }}"
                                {{ (string) old('target_control_id', $mapping->target_control_id) === (string) $control->id ? 'selected' : '' }}>
                                {{ trim($control->clause) }} – {{ $control->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.fields.mapping_type') }}</strong>
        </div>
        <div class="cell-lg-3 cell-md-4">
            <select name="mapping_type" data-role="select" required>
                @foreach(\App\Models\ControlMapping::MAPPING_TYPES as $mappingType)
                    <option value="{{ $mappingType }}"
                        {{ old('mapping_type', $mapping->mapping_type) === $mappingType ? 'selected' : '' }}>
                        {{ trans('cruds.crosswalk.mapping_types.' . $mappingType) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="cell-lg-2 cell-md-2 text-right">
            <strong>{{ trans('cruds.crosswalk.fields.coverage') }}</strong>
        </div>
        <div class="cell-lg-3 cell-md-3">
            <select name="coverage" data-role="select">
                <option value="">–</option>
                @foreach(\App\Models\ControlMapping::COVERAGE_LEVELS as $coverage)
                    <option value="{{ $coverage }}"
                        {{ old('coverage', $mapping->coverage) === $coverage ? 'selected' : '' }}>
                        {{ trans('cruds.crosswalk.coverage_levels.' . $coverage) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.fields.confidence') }}</strong>
        </div>
        <div class="cell-lg-2 cell-md-3">
            <input type="number" data-role="input" name="confidence" min="0" max="100" step="0.01"
                   value="{{ old('confidence', $mapping->confidence) }}">
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.fields.rationale') }}</strong>
        </div>
        <div class="cell-lg-8 cell-md-9">
            <textarea name="rationale" data-role="textarea" rows="5">{{ old('rationale', $mapping->rationale) }}</textarea>
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.fields.source_reference') }}</strong>
        </div>
        <div class="cell-lg-8 cell-md-9">
            <input type="text" data-role="input" name="source_reference" maxlength="255"
                   value="{{ old('source_reference', $mapping->source_reference) }}">
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.fields.source_url') }}</strong>
        </div>
        <div class="cell-lg-8 cell-md-9">
            <input type="url" data-role="input" name="source_url" maxlength="2048"
                   value="{{ old('source_url', $mapping->source_url) }}">
        </div>
    </div>

    <div class="row">
        <div class="cell-lg-2 cell-md-3">
            <strong>{{ trans('cruds.crosswalk.fields.validated') }}</strong>
        </div>
        <div class="cell-lg-8 cell-md-9">
            <input type="hidden" name="validated" value="0">
            <input type="checkbox" data-role="checkbox" name="validated" value="1"
                   {{ old('validated', $mapping->validated) ? 'checked' : '' }}>
        </div>
    </div>

    <div class="row">
        <div class="cell-12">
            <button type="submit" class="button success">
                <span class="mif-floppy-disk2"></span>&nbsp;{{ trans('common.save') }}
            </button>
            <a class="button" href="{{ route('crosswalk.index') }}">
                <span class="mif-cancel"></span>&nbsp;{{ trans('common.cancel') }}
            </a>
        </div>
    </div>
</div>

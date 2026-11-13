@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'controls.import'])<i class="bi bi-download"></i> {{ trans('cruds.imports.index') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <div class="row">
        <div class="col-lg-8 col-md-12">
    		<b>{{ trans('cruds.imports.title') }}</b>
            /
            <a href="/export/alices" target="_blank">{{ trans('cruds.imports.current') }}</a>
    	</div>
    </div>

    <form action="/alice/import" method="post" enctype="multipart/form-data">
    @csrf
    	<div class="row">
            <div class="col-lg-6 col-md-7">
                <div class="input-group">
                    <span class="input-group-text">Select model</span>
                    <select class="form-select select2" name="model" id="model">
                        <option></option>
                        @foreach($models as $model)
                            <option>{{ basename($model, '.xlsx') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-1 col-md-2 text-md-end">
                <a href="#" id="download-model" class="btn btn-info">
                    {{ trans("common.download") }}
                </a>
            </div>
        </div>
    	<div class="row">
            <div class="col-lg-7 col-md-9">
                {{ trans('cruds.imports.or') }}
                <div class="input-group">
                    <span class="input-group-text">Select import file:</span>
                    <input class="form-control" name="file" type="file" id="file"/>
                </div>
    		</div>
        </div>
    	<div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="clean" id="clean"/><label class="form-check-label" for="clean">{{ trans('cruds.imports.remove_all') }}</label></div>
            </div>
        </div>
    	<div class="row">
            <div class="col-lg-6 col-md-7">
                <div class="form-check form-check-inline"><input id="test" class="form-check-input" type="checkbox" name="test"/><label class="form-check-label" for="test">{{ trans('cruds.imports.fake') }}</label></div>
            </div>
            <div class="col-lg-1 col-md-2 text-md-end">
    		    <button type="submit" class="btn btn-success"
                    id="import-submit">
                        {{ trans("common.import") }}
                </button>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-lg-9 col-md-12">
            {{ trans('cruds.imports.format') }}
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-md-9">
            <table class="table table-striped border table-sm">
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>A</td><td>{{ trans('cruds.imports.framework') }}</td><td>string(32)</td>
                        <td>{{ trans('cruds.imports.framework_helper') }}</td>
                    </tr>
                    <tr>
                        <td>B</td><td>{{ trans('cruds.imports.domain') }}</td><td>string(32)</td>
                        <td>{{ trans('cruds.imports.domain_helper') }}</td>
                    </tr>
                    <tr>
                        <td>C</td><td>{{ trans('cruds.imports.domain_description') }}</td><td>string(255)</td>
                        <td>{{ trans('cruds.imports.domain_description_helper') }}</td>
                    </tr>
                    <tr>
                        <td>D</td><td>{{ trans('cruds.imports.clause') }}</td><td>string(32)</td>
                        <td>
                            {!! trans('cruds.imports.clause_helper') !!}
                        </td>
                    </tr>
                    <tr>
                        <td>E</td><td>{{ trans('cruds.imports.name') }}</td><td>string(255)</td>
                        <td>{{ trans('cruds.imports.name_helper') }}</td>
                    </tr>
                    <tr>
                        <td>F</td><td>{{ trans('cruds.imports.description') }}</td><td>text</td>
                        <td>{{ trans('cruds.imports.description_helper') }}</td>
                    </tr>
                    <tr>
                        <td>G</td><td>{{ trans('cruds.imports.attributes') }}</td><td>text</td>
                        <td>{{ trans('cruds.imports.attributes_helper') }}</td>
                    </tr>
                    <tr>
                        <td>H</td><td>{{ trans('cruds.imports.input') }}</td><td>text</td>
                        <td>{{ trans('cruds.imports.input_helper') }}</td>
                    </tr>
                    <tr>
                        <td>I</td><td>Model</td><td>text</td>
                        <td>{{ trans('cruds.imports.model_helper') }}</td>
                    </tr>
                    <tr>
                        <td>J</td><td>{{ trans('cruds.imports.indicator') }}</td><td>text</td>
                        <td>{{ trans('cruds.imports.indicator_helper') }}</td>
                    </tr>
                    <tr>
                        <td>K</td><td>{{ trans('cruds.imports.action') }}</td><td>text</td>
                        <td>{{ trans('cruds.imports.action_helper') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 col-md-8 text-danger">
            {{ trans('cruds.imports.warning') }}
        </div>
    </div>
</div>
</div>

<script @nonce>
function downloadModel() {
    const model = document.getElementById('model').value;
    if (!model) {
        alert('Please select a model.');
        return;
    }
    window.location.href = `/alice/download?model=${encodeURIComponent(model)}`;
}

function checkImport() {
    if (!document.getElementById('model').value && !document.getElementById('file').value) {
        alert('Please select a model or an import file.');
        return false;
    }
    if ($('#clean').is(':checked'))
        return confirm('Do you want to remove all other measures and controls  ?');
    return true;
}

document.getElementById('download-model').addEventListener('click', function (e) {
    e.preventDefault();
    downloadModel();
});

document.getElementById('import-submit').addEventListener('click', function (e) {
    if (!checkImport()) {
        e.preventDefault();
    }
});
</script>
@endsection

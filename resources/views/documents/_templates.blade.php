{{-- "Templates" tab of the settings page: document templates and the report template tags --}}
<div class="row">

{{-- Aide : balises du modèle du rapport de pilotage (à droite, en dessous sur mobile) --}}
<div class="col-md-5 col-xl-4 order-2">
    @include('partials.report-template-help')
</div>

<div class="col-md-7 col-xl-8 order-1">
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'templates'])<i class="bi bi-file-earmark-text"></i> {{ trans('cruds.document.title.templates') }}</div>
    <div class="card-body">

    <form action="/doc/template" method="POST" role="form" enctype="multipart/form-data">
    @csrf
        <div class="container-fluid px-0">
            <div class="row">
                <div class="col-md-10 col-xl-8">
                    <a href="/doc/template?id=1" target="_new">{{ trans('cruds.document.model.control') }}</a>
                    @if (file_exists(storage_path('app/models/control_.docx')))
                        / <a href="/doc/template?id=2" target="_new">{{ trans('cruds.document.model.custom') }}</a>
                    @endif
                    <input class="form-control" type="file" name="template1"/>
                </div>
            </div>
            <div class="row">
                <div class="col-md-10 col-xl-8">
                    <a href="/doc/template?id=3" target="_new">{{ trans('cruds.document.model.report') }}</a>
                    @if (file_exists(storage_path('app/models/pilotage_.docx')))
                        / <a href="/doc/template?id=4" target="_new">{{ trans('cruds.document.model.custom') }}</a>
                    @endif
                    <input class="form-control {{ $errors->has('template2') ? 'is-invalid' : '' }}" type="file" name="template2" accept=".docx"/>
                    <span class="help-block">{{ trans('report_template.upload_helper') }}</span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-xl-6">

                <button type="submit" class="btn btn-success">
                    <span class="bi bi-floppy"></span> {{ trans("common.save") }}
                </button>
                &nbsp;
                <a class="btn cancel btn-light border" href="/">
                    <span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
                </a>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
</div>

</div>

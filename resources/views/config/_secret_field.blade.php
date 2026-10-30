{{-- Write-only secret: never displayed; empty keeps the stored value, the checkbox deletes it --}}
<div class="row mb-2">
    <div class="col-md-3 col-lg-2"><label for="{{ $field }}"><strong>{{ $label }}</strong></label></div>
    <div class="col-md-5 col-lg-4">
        <input class="form-control" type="password" name="{{ $field }}" id="{{ $field }}" value="" autocomplete="new-password" maxlength="1024">
        <small class="text-muted">{{ trans('cruds.config.notifications.smtp.secret_help') }}</small>
    </div>
    <div class="col-md-4 col-lg-4">
        @if ($stored)
            <span class="badge bg-success"><i class="bi bi-key"></i> {{ trans('cruds.config.notifications.smtp.secret_saved') }}</span>
            <div class="form-check d-inline-block ms-2">
                <input class="form-check-input" type="checkbox" name="{{ $field }}_delete" id="{{ $field }}_delete" value="1">
                <label class="form-check-label" for="{{ $field }}_delete">{{ trans('cruds.config.notifications.smtp.secret_delete') }}</label>
            </div>
        @else
            <span class="badge bg-secondary">{{ trans('cruds.config.notifications.smtp.secret_none') }}</span>
        @endif
    </div>
</div>

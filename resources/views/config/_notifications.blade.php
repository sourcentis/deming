{{-- "Notifications" tab of the settings page; values entered are kept after a test --}}
@php
    $mail_from = old('mail_from', $mail_from);
    $mail_subject = old('mail_subject', $mail_subject);
    $mail_content = old('mail_content', $mail_content);
    $frequency = old('frequency', $frequency);
    $expire_delay = old('expire_delay', $expire_delay);
    // SMTP values entered are kept after a test, except the secrets which are never displayed
    foreach (['smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_auth', 'smtp_auto_tls', 'dkim_domain', 'dkim_private', 'dkim_selector'] as $field) {
        $smtp[$field] = old($field, $smtp[$field]);
    }
@endphp
    @include('partials.help', ['page' => 'notifications'])
    <form method="POST" action="/config/save" enctype="multipart/form-data">
        @csrf

		<div class="container-fluid px-0">
	    	<div class="row">
	    		<div class="col-md-12 col-lg-6">
		            <label for="name">{{ trans("cruds.config.notifications.help") }}</label>
		        </div>
	        </div>

	    	<div class="row">
	    		<div class="col-md-12 col-lg-6">
		        </div>
	        </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans("cruds.config.notifications.sent_from") }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		            <input class="form-control" type="text" name="mail_from" id="mail_from" value="{{ $mail_from }}" required size=64/>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans("cruds.config.notifications.message_subject") }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		            <input class="form-control" type="text" name="mail_subject" id="mail_subject" value="{{ $mail_subject }}" required size=64/>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans("cruds.config.notifications.message_content") }}</strong>
		    	</div>
				<div class="col-md-12 col-lg-11">
		            <textarea class="form-control" name="mail_content" rows="10">{{ strlen($mail_content)==0 ? trans('cruds.config.notifications.message_default_content') : $mail_content }}</textarea>
				</div>
			</div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
	    		<strong>{{ trans("cruds.config.notifications.recurrence") }}</strong>
	    	</div>
			<div class="col-md-8 col-lg-4">
	            <select class="form-select select2" name="frequency" id="frequency">
	                <option value="0" {{ $frequency=="0" ? 'selected' : '' }}>{{ trans("common.never") }}</option>
	                <option value="1" {{ $frequency=="1" ? 'selected' : '' }}>{{ trans("common.day") }}</option>
	                <option value="7" {{ $frequency=="7" ? 'selected' : '' }}>{{ trans("common.week") }}</option>
	                <option value="30" {{ $frequency=="30" ? 'selected' : '' }}>{{ trans("common.month") }}</option>
	            </select>
	        </div>
	    </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans("cruds.config.notifications.delay") }}</strong>
		    	</div>
				<div class="col-md-8 col-lg-4">
		            <select class="form-select select2" name="expire_delay" id="expire_delay">
		                <option value="1" {{ $expire_delay=="1" ? 'selected' : '' }}>1 {{ trans("cruds.config.notifications.duration.day") }}</option>
		                <option value="7" {{ $expire_delay=="7" ? 'selected' : '' }}>7 {{ trans("cruds.config.notifications.duration.days") }}</option>
		                <option value="15" {{ $expire_delay=="15" ? 'selected' : '' }}>15 {{ trans("cruds.config.notifications.duration.days") }}</option>
		                <option value="30" {{ $expire_delay=="30" ? 'selected' : '' }}>1 {{ trans("cruds.config.notifications.duration.month") }}</option>
		            </select>
				</div>
			</div>

    	<div class="row">
    		<div class="col-md-12 col-lg-6">
	        </div>
        </div>

        <fieldset class="border rounded p-2 mb-3">
            <legend class="float-none w-auto px-2 fs-6 fw-bold"><i class="bi bi-envelope-at"></i> {{ trans('cruds.config.notifications.smtp.title') }}</legend>
            <p class="text-muted small mb-2">{{ trans('cruds.config.notifications.smtp.help') }}</p>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2"><label for="smtp_host"><strong>{{ trans('cruds.config.notifications.smtp.host') }}</strong></label></div>
                <div class="col-md-5 col-lg-4"><input class="form-control" type="text" name="smtp_host" id="smtp_host" value="{{ $smtp['smtp_host'] }}" maxlength="255"></div>
                <div class="col-md-1 col-lg-1 text-md-end"><label for="smtp_port"><strong>{{ trans('cruds.config.notifications.smtp.port') }}</strong></label></div>
                <div class="col-md-3 col-lg-2"><input class="form-control" type="number" name="smtp_port" id="smtp_port" value="{{ $smtp['smtp_port'] }}" min="1" max="65535"></div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2"><label for="smtp_encryption"><strong>{{ trans('cruds.config.notifications.smtp.encryption') }}</strong></label></div>
                <div class="col-md-4 col-lg-2">
                    <select class="form-select" name="smtp_encryption" id="smtp_encryption">
                        <option value="" {{ ($smtp['smtp_encryption'] ?? '') === '' ? 'selected' : '' }}>{{ trans('cruds.config.notifications.smtp.encryption_none') }}</option>
                        <option value="ssl" {{ $smtp['smtp_encryption'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="tls" {{ $smtp['smtp_encryption'] === 'tls' ? 'selected' : '' }}>TLS</option>
                    </select>
                </div>
                <div class="col-md-5 col-lg-6">
                    <div class="form-check form-check-inline mt-2">
                        <input type="hidden" name="smtp_auto_tls" value="0">
                        <input class="form-check-input" type="checkbox" name="smtp_auto_tls" id="smtp_auto_tls" value="1" {{ filter_var($smtp['smtp_auto_tls'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        <label class="form-check-label" for="smtp_auto_tls">{{ trans('cruds.config.notifications.smtp.auto_tls') }}</label>
                    </div>
                    <div class="form-check form-check-inline mt-2">
                        <input type="hidden" name="smtp_auth" value="0">
                        <input class="form-check-input" type="checkbox" name="smtp_auth" id="smtp_auth" value="1" {{ filter_var($smtp['smtp_auth'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        <label class="form-check-label" for="smtp_auth">{{ trans('cruds.config.notifications.smtp.auth') }}</label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 col-lg-2"><label for="smtp_username"><strong>{{ trans('cruds.config.notifications.smtp.username') }}</strong></label></div>
                <div class="col-md-5 col-lg-4"><input class="form-control" type="text" name="smtp_username" id="smtp_username" value="{{ $smtp['smtp_username'] }}" maxlength="255" autocomplete="off"></div>
            </div>

            @include('config._secret_field', ['field' => 'smtp_password', 'label' => trans('cruds.config.notifications.smtp.password'), 'stored' => $smtp['has_smtp_password']])

            <details class="mt-2">
                <summary class="fw-bold">{{ trans('cruds.config.notifications.smtp.dkim') }}</summary>
                <div class="row mb-2 mt-2">
                    <div class="col-md-3 col-lg-2"><label for="dkim_domain"><strong>{{ trans('cruds.config.notifications.smtp.dkim_domain') }}</strong></label></div>
                    <div class="col-md-5 col-lg-4"><input class="form-control" type="text" name="dkim_domain" id="dkim_domain" value="{{ $smtp['dkim_domain'] }}" maxlength="255"></div>
                    <div class="col-md-1 col-lg-1 text-md-end"><label for="dkim_selector"><strong>{{ trans('cruds.config.notifications.smtp.dkim_selector') }}</strong></label></div>
                    <div class="col-md-3 col-lg-2"><input class="form-control" type="text" name="dkim_selector" id="dkim_selector" value="{{ $smtp['dkim_selector'] }}" maxlength="255"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-3 col-lg-2"><label for="dkim_private"><strong>{{ trans('cruds.config.notifications.smtp.dkim_private') }}</strong></label></div>
                    <div class="col-md-9 col-lg-7"><input class="form-control" type="text" name="dkim_private" id="dkim_private" value="{{ $smtp['dkim_private'] }}" maxlength="1024"></div>
                </div>
                @include('config._secret_field', ['field' => 'dkim_passphrase', 'label' => trans('cruds.config.notifications.smtp.dkim_passphrase'), 'stored' => $smtp['has_dkim_passphrase']])
            </details>
        </fieldset>

    	<div class="row">
            <div class="form-group">
    			<button type="submit" class="btn btn-success" name="action" value="save">
                    <span class="bi bi-floppy"></span> {{ trans("common.save") }}
    			</button>
                <button type="submit" class="btn btn-primary" name="action" value="test">
    	            <span class="bi bi-eyedropper"></span> {{ trans("common.test") }}
    			</button>
        		<button type="submit" class="btn cancel btn-light border" name="action" value="cancel">
        			<span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
        		</button>

            </div>
        </div>
        </div>
    </form>

{{-- "Notifications" tab of the settings page; values entered are kept after a test --}}
@php
    $mail_from = old('mail_from', $mail_from);
    $mail_subject = old('mail_subject', $mail_subject);
    $mail_content = old('mail_content', $mail_content);
    $frequency = old('frequency', $frequency);
    $expire_delay = old('expire_delay', $expire_delay);
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

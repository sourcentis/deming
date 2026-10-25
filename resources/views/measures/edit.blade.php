@extends("layout")

@section('title', $measure->name)

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'measures.edit'])<i class="bi bi-clipboard-check"></i> {{ trans("cruds.measure.edit")}}</div>
    <div class="card-body">

    @include('partials.errors')

	<form method="POST" action="/bob/save">
		@csrf
		<input type="hidden" name="id" value="{{ $measure->id }}"/>

		<div class="container-fluid px-0">

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.clauses") }}</strong>
				</div>
        		<div class="col-lg-11 col-md-10">
                    <select class="form-select select2" id="controls" name="controls[]" multiple>
						@foreach($all_controls as $control)
						    <option value="{{ $control->id }}"
                                {{ in_array($control->id, old("controls", $controls)) ? "selected" : "" }}>
                                {{ $control->clause }} - {{ $control->name }}
                            </option>
					    @endforeach
					 </select>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.name") }}</strong>
	    		</div>
                <div class="col-lg-6 col-md-5">
					<input class="form-control" type="text" name="name" value="{{ $measure->name }}" maxlength="255">
				</div>
                <div class="col-lg-1 col-md-1 text-md-end">
		    		<strong>{{ trans("cruds.measure.fields.scope") }}</strong>
                </div>
                <div class="col-lg-4 col-md-4">
					<input class="form-control" type="text" name="scope" autocomplete="off" maxlength="32"
					value="{{ $measure->scope }}"/>
				</div>
			</div>


	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.objective") }}</strong>
		    	</div>
				<div class="col-lg-11 col-md-10">
                    <textarea name="objective" class="easymde form-control" id="objective">{{ old('objective', $measure->objective) }}</textarea>
				</div>
			</div>

			<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans('cruds.measure.fields.attributes') }}</strong>
		    	</div>
				<div class="col-lg-11 col-md-10">
                    <select class="form-select select2" id="attributes" name="attributes[]" multiple>
						@foreach($attributes as $attribute)
							@if (strlen($attribute)>0)
						    <option {{ str_contains($measure->attributes, $attribute) ? "selected" : ""}}>{{$attribute}}</option>
						    @endif
					    @endforeach
					 </select>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.input") }}</strong>
		    	</div>
				<div class="col-lg-11 col-md-10">
                    <textarea name="input" class="easymde form-control" id="input">{{ old('input', $measure->input) }}</textarea>
				</div>
			</div>
			<div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans('cruds.measure.fields.model') }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="model" rows="3">{{ old('model', $measure->model) }}</textarea>
                </div>
            </div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.plan_date") }}</strong>
		    	</div>
				<div class="col-lg-2 col-md-3">
					<input class="form-control" type="date"
					name="plan_date"
					value="{{ $measure->plan_date }}"
					/>
				</div>
                <div class="col-lg-1 col-md-2">
                </div>
                <div class="col-lg-1 col-md-2 text-md-end">
		    		<strong>{{ trans("cruds.measure.fields.realisation_date") }}</strong>
		    	</div>
				<div class="col-lg-2 col-md-3">
					<input class="form-control" type="date"
					name="realisation_date"
					value="{{ $measure->realisation_date == '0000-00-00' ? null : $measure->realisation_date}}"
					/>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.observations") }}</strong>
		    	</div>
				<div class="col-lg-11 col-md-10">
					<textarea class="form-control" name="observations" rows="5">{{ old('observations', $measure->observations) }}</textarea>
				</div>
		    </div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.evidence") }}</strong>
		    	</div>
				<div class="col-lg-11 col-md-10">
					<div class="dropzone dropzone-previews" id="dropzoneFileUpload"></div>
				</div>
		    </div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.note") }}</strong>
		    	</div>
                <div class="col-lg-2 col-md-2">
                    <input class="form-control" type="number"
                        id="note"
                        name="note"
                        value="{{ old('note', $measure->note) }}"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="0.00"
                    />
	    		</div>
		    </div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.indicator") }}</strong>
		    	</div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="indicator" rows="3">{{ old('indicator', $measure->indicator) }}</textarea>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.score") }}</strong>
		    	</div>
                <div class="col-lg-11 col-md-10">
					<div class="form-check form-check-inline"><input id="score3" class="form-check-input" type="radio" name="score" value="3" {{ ($measure->score==3) ? 'checked' : '' }}/><label class="form-check-label" for="score3"><font color='green'>{{ trans('common.green') }}</font></label></div>
					<div class="form-check form-check-inline"><input id="score2" class="form-check-input" type="radio" name="score" value="2" {{ ($measure->score==2) ? 'checked' : '' }}/><label class="form-check-label" for="score2"><font color='orange'>{{ trans('common.orange') }}</font></label></div>
					<div class="form-check form-check-inline"><input id="score1" class="form-check-input" type="radio" name="score" value="1" {{ ($measure->score==1) ? 'checked' : '' }}/><label class="form-check-label" for="score1"><font color='red'>{{ trans('common.red') }}</font></label></div>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans("cruds.measure.fields.action_plan") }}</strong>
		    	</div>
				<div class="col-lg-11 col-md-10">
                    <textarea name="action_plan" class="easymde form-control" id="action_plan">{{ old('action_plan', $measure->action_plan) }}</textarea>
				</div>
			</div>


			<div class="row">
	    		<div class="col-lg-1 col-md-2">
		    		<strong>{{ trans('cruds.measure.fields.periodicity') }}</strong>
		    	</div>
                <div class="col-lg-2 col-md-2">
                    <select class="form-select select2" id="periodicity" name="periodicity">
					    <option value="0" {{ $measure->periodicity==0 ? "selected" : ""}}>{{ trans('common.once') }}</option>
					    <option value="-1" {{ $measure->periodicity==-1 ? "selected" : ""}}>{{ trans('common.weekly') }}</option>
					    <option value="1" {{ $measure->periodicity==1 ? "selected" : ""}}>{{ trans('common.monthly') }}</option>
					    <option value="3" {{ $measure->periodicity==3 ? "selected" : ""}}>{{ trans('common.quarterly') }}</option>
					    <option value="6" {{ $measure->periodicity==6 ? "selected" : ""}}>{{ trans('common.biannually') }}</option>
					    <option value="12" {{ $measure->periodicity==12 ? "selected" : ""}}>{{ trans('common.annually') }}</option>
					 </select>
				</div>
                <div class="col-lg-1 col-md-2 text-md-end">
		    		<strong>Status</strong>
	    		</div>
				<div class="col-lg-2 col-xl-1 col-md-2">
                    <select class="form-select select2" id="status" name="status">
						<option value="0" {{ $measure->status==0 ? "selected" : ""}}>Todo</option>
						<option value="1" {{ $measure->status==1 ? "selected" : ""}}>Proposed</option>
						<option value="2" {{ $measure->status==2 ? "selected" : ""}}>Done</option>
					 </select>
	    		</div>
                <div class="col-lg-1 col-md-2 text-md-end">
		    		<strong>Next ID</strong>
		    	</div>
				<div class="col-lg-2 col-xl-1 col-md-2">
                    <select class="form-select select2" id="next_id" name="next_id">
						<option></option>
						@foreach($ids as $id)
						    <option {{ $measure->next_id === $id ? "selected" : ""}}>{{$id}}</option>
					    @endforeach
					 </select>
				</div>
			</div>

			<div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans('cruds.measure.fields.owners') }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <select class="form-select select2" name="owners[]" id="owners" multiple>
                        @foreach($owners as $id => $name)
                            <option
                                value="{{ $id }}"
                                {{ (in_array($id, old('owners', []))) ||
                                    (
                                        (str_starts_with($id,'USR_') && $measure->users->contains(intval(substr($id, 4)))) ||
                                        (str_starts_with($id,'GRP_') && $measure->groups->contains(intval(substr($id, 4))))
                                    )
                                    ? 'selected' : '' }}>
                            {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

	    	<div class="row">
                <div class="col-12">
    				<button type="submit" class="btn btn-success">
    					<span class="bi bi-floppy"></span> {{ trans("common.save") }}
    				</button>
    				&nbsp;
                    <a class="btn btn-light border" href="/bob/show/{{$measure->id}}" role="button">
                        <span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
                    </a>
                </div>
    		</div>
    	</div>
    </form>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

const myDropzone = new Dropzone("div#dropzoneFileUpload", {
	    url: '/doc/store',
	    headers: { 'x-csrf-token': '{{csrf_token()}}'},
        params: function params(files, xhr, chunk) { return { 'control': '{{ $measure->id }}' }; },
	    addRemoveLinks: true,
	    timeout: 50000,
	    removedfile: function(file)
	    {
	        console.log("remove file " + file.name + " " + file.id);
	        $.ajax({
	            headers: {
	              'X-CSRF-TOKEN': '{{csrf_token()}}'
	               },
	            type: 'GET',
	            url: '/doc/delete/'+file.id,
	            success: function (data) {
	                console.log("File has been successfully removed");
	            	},
	            error: function(e) {
	                console.log("File not removed");
	                console.log(e);
	            	}
	            });
	            // console.log('{{ url( "/doc/delete" ) }}'+"/"+file.id+']');
	            var fileRef;
	            return (fileRef = file.previewElement) != null ?
	            fileRef.parentNode.removeChild(file.previewElement) : void 0;
	    },

	    success: function(file, response)
	    {
	        file.id=response.id;
	        console.log("respose");
	        console.log(response);
	    },
	    error: function(file, response)
	    {
	        console.log(response);
	       return false;
	    },
		init: function () {
	    //Add existing files into dropzone
	    var existingFiles = [
	    @foreach ($documents as $document)
	        { name: "{{ $document->filename }}", size: {{ $document->size }}, id: {{ $document->id }} },
	    @endforeach
	    ];
	    for (i = 0; i < existingFiles.length; i++) {
	        this.emit("addedfile", existingFiles[i]);
	        this.emit("complete", existingFiles[i]);
		    }
		}
	});

    // Paste document or image on page
    document.addEventListener('paste', function(event) {
      const clipboardData = event.clipboardData || window.clipboardData;
      if (!clipboardData || !clipboardData.items) return;
      const items = Array.from(clipboardData.items);
      items.forEach((item) => {
        if (item.kind === 'file') {
          const file = item.getAsFile();
          if (file) {
            myDropzone.addFile(file);
          }
        }
      });
    });
});
</script>
@endsection

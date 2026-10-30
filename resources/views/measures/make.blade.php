@extends("layout")

@section('title', $measure->name)

@section("styles")
<style>
.CodeMirror.CodeMirror-disabled {
    background-color: #f5f5f5 !important;
    cursor: not-allowed;
}
</style>
@endsection

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'measures.make'])<i class="bi bi-clipboard-check"></i> {{ trans('cruds.measure.make') }}</div>
    <div class="card-body">

@include('partials.errors')

<form method="POST" action="/bob/make" enctype="multipart/form-data">
	@csrf
	<input type="hidden" name="id" value="{{ $measure->id }}"/>

	<div class="container-fluid px-0">
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.clauses') }}</strong>
	    	</div>
    		<div class="col-lg-11 col-md-10">
                @foreach($measure->controls as $control)
                    <a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a> - {{ $control->name }}
                    @if(!$loop->last)
                    <br>
                    @endif
                @endforeach
            </div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.name') }}</strong>
	    	</div>
            @if ($measure->scope==null)
    		<div class="col-lg-6 col-md-10">
				{{ $measure->name }}
			</div>
			@else
    		<div class="col-lg-5 col-md-5">
				{{ $measure->name }}
			</div>
            <div class="col-md-4 col-lg-2">
	    		<strong>{{ trans("cruds.measure.fields.scope") }}</strong>
	    		&nbsp;
    			{{ $measure->scope }}
    		</div>
            @endif
		</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.objective') }}</strong>
	    	</div>
            <div class="col-lg-11 col-md-10">
                {!! \Parsedown::instance()->text($measure->objective) !!}
			</div>
		</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.input') }}</strong>
	    	</div>
			<div class="col-lg-11 col-md-10">
                {!! \Parsedown::instance()->text($measure->input) !!}
			</div>
		</div>

        <div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.model') }}</strong>
	    	</div>
			<div class="col-lg-11 col-md-10">
				<pre>{{ $measure->model }}</pre>
			</div>
		</div>

        <div class="row">

			<div class="col-lg-1 col-md-2">
				<b>{{ trans('cruds.measure.fields.plan_date') }}</b>
			</div>
            <div class="col-lg-2 col-md-4">
                @if ((Auth::User()->role === 1)||(Auth::User()->role === 2))
					<input class="form-control" type="date"
						name="plan_date"
						value="{{ old('plan_date', $measure->plan_date) }}"
						/>
                @else
                    {{ $measure->plan_date }}
                @endif
			</div>
            <div class="col-lg-2 col-md-3 text-md-end">
	    		<strong>{{ trans('cruds.measure.fields.realisation_date') }}</strong>
	    	</div>
            <div class="col-lg-2 col-md-3">
				<input class="form-control" type="date"
					name="realisation_date"
					value="{{
						\Carbon\Carbon::now()
						->format('Y-m-d')
						}}"/>
			</div>
		</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.observations') }}</strong>
	    	</div>
			<div class="col-lg-11 col-md-10">
                <textarea class="form-control" name="observations" rows="5">{{ old('observations', $measure->observations) }}</textarea>
			</div>
	    </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.evidence') }}</strong>
	    		<br>
                <a target="_new" href="/bob/template/{{ $measure->id }}" id="checklist-link">{{ trans('cruds.measure.checklist') }}</a>
	    	</div>
			<div class="col-lg-11 col-md-10">
				<div class="dropzone dropzone-previews" id="dropzoneFileUpload"></div>
			</div>
	    </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.note') }}</strong>
	    	</div>
            <div class="col-lg-2 col-md-3">
                <input class="form-control" type="number"
                    id="note"
                    name="note"
                    value="{{ old('note', $measure->note) }}"
                    step="0.01"
                    placeholder="0.00"
                />
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.indicator') }}</strong>
	    	</div>
            <div class="col-lg-11 col-md-10">
				<pre>{{ $measure->indicator }}</pre>
			</div>
		</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>
	    			{{ trans('cruds.measure.fields.score') }}
	    		</strong>
	    	</div>
            <div class="col-lg-11 col-md-10">
                <div class="form-check form-check-inline"><input id="score3" class="form-check-input" type="radio" name="score" value="3" {{ $measure->score===3 ? "checked" : "" }} /><label class="form-check-label" for="score3"><font color='green'>{{ trans('common.green') }}</font></label></div>
                <div class="form-check form-check-inline"><input id="score2" class="form-check-input" type="radio" name="score" value="2" {{ $measure->score===2 ? "checked" : "" }} /><label class="form-check-label" for="score2"><font color='orange'>{{ trans('common.orange') }}</font></label></div>
                <div class="form-check form-check-inline"><input id="score1" class="form-check-input" type="radio" name="score" value="1" {{ $measure->score===1 ? "checked" : "" }} /><label class="form-check-label" for="score1"><font color='red'>{{ trans('common.red') }}</font></label></div>
            </div>
		</div>
        @if ((Auth::User()->role === 1)||(Auth::User()->role === 2))
    	<div class="row">
            <div class="col-lg-1 col-md-2">
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="add_action_plan" id="toggleTextarea"/><label class="form-check-label" for="toggleTextarea">{{ trans('cruds.measure.create_action') }}</label></div>
            </div>
        </div>
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.action_plan') }}</strong>
	    	</div>
            <div class="col-lg-11 col-md-10">
                <textarea name="action_plan" class="form-control easymde disabled-editor" id="action_plan">{{ old('action_plan', $measure->action_plan) }}</textarea>
			</div>
		</div>
        @endif
		@if ($measure->periodicity!==0)
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
	    		<strong>{{ trans('cruds.measure.fields.next') }}</strong>
	    	</div>
            <div class="col-lg-2 col-md-3">
                @if ((Auth::User()->role === 1)||(Auth::User()->role === 2))
					<input class="form-control" type="date"
                        lenght="12"
						name="next_date"
						value="{{ $next_date }}"/>
                </div>
                <div class="col-md-4 col-lg-2">
                @else
                    {{ $next_date }}
                @endif
			(
			@if ($measure->periodicity==-1)
				{{ trans("common.weekly") }}
			@elseif ($measure->periodicity==1)
				{{ trans("common.monthly") }}
			@elseif ($measure->periodicity==3)
				{{ trans("common.quarterly") }}
			@elseif ($measure->periodicity==6)
				{{ trans("common.biannually") }}
			@elseif ($measure->periodicity==12)
				{{ trans("common.annually") }}
			@else
				{{ $measure->periodicity }}
			@endif
			)
			</div>
		</div>
        @endif

    	<div class="row">
    		&nbsp;
    	</div>

    	<div class="row">
              <div class="col-12">
            @if ($measure->canMake())
                @if ($measure->status===0)
					<button type="submit" class="btn btn-success">
						<span class="bi bi-check-lg"></span> {{ trans('common.make') }}
					</button>
					&nbsp;
                    @endif
                @endif
                @if ((Auth::User()->role === 1)||(Auth::User()->role === 2))
                    @if ($measure->status===1)
					<button type="submit" class="btn btn-success" onclick='this.form.action="/bob/accept"'>
						<span class="bi bi-check-lg"></span> {{ trans('common.accept') }}
					</button>
					&nbsp;
					<button type="submit" class="btn btn-danger" onclick='this.form.action="/bob/reject"'>
						<span class="bi bi-trash"></span> {{ trans('common.reject') }}
					</button>
					&nbsp;
                    @endif
                @endif
                @if ($measure->canMake())
					<button type="submit" class="btn btn-primary" onclick='this.form.action="/bob/draft"'>
                        <span class="bi bi-floppy"></span> {{ trans('common.save') }}
					</button>
					&nbsp;
                @endif
                <a href="/bob/show/{{ $measure->id }}" class="btn btn-light border">
					<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
                </a>
    		</div>
    	</div>

	</form>
</div>
</div>
</div>
<br>
<br>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------------------------
    // DropZone
    // ---------------------------------------------------------

    const myDropzone = new Dropzone("div#dropzoneFileUpload", {
        url: '/doc/store',
	    headers: { 'x-csrf-token': '{{csrf_token()}}' },
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
                success: function (data){
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
            console.log("success response");
            console.log(response);
        },
        error: function(file, response)
        {
            console.log("error response");
            console.log(response);
            return false;
        },
        init: function () {
            // Add existing files into dropzone
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
                if (typeof myDropzone !== 'undefined' && typeof myDropzone.addFile === 'function') {
                  myDropzone.addFile(file);
                } else {
                  console.warn('myDropzone n\'est pas défini ou n\'est pas un objet Dropzone valide.');
                }
              }
            }
          });
        });

@if ((Auth::User()->role === 1)||(Auth::User()->role === 2))

    // ---------------------------------------------------------
    // Désativte EasyMDE au chargement
    // ---------------------------------------------------------

    function waitForEasyMDE(id, callback) {
        const interval = setInterval(() => {
            if (window.editors && window.editors[id]) {
                clearInterval(interval);
                callback(window.editors[id]);
            }
        }, 100); // Vérifie toutes les 100ms
    }

    // Rendre l'éditeur en lecture seule par défaut
    waitForEasyMDE('action_plan', function(easyMDE) {
        // Lire/écrire sur l'éditeur une fois prêt
        easyMDE.codemirror.setOption("readOnly", true);
        easyMDE.codemirror.getWrapperElement().classList.add('CodeMirror-disabled');

        document.getElementById('toggleTextarea').addEventListener('change', function() {
            if (this.checked) {
                easyMDE.codemirror.setOption("readOnly", false);
                easyMDE.codemirror.getWrapperElement().classList.remove('CodeMirror-disabled');
            } else {
                easyMDE.codemirror.setOption("readOnly", true);
                easyMDE.codemirror.getWrapperElement().classList.add('CodeMirror-disabled');
            }
        });
    });

@endif

    // ---------------------------------------------------------
    // Ajoute les observations en paramètre de la template de document
    // ---------------------------------------------------------


    const link = document.querySelector('#checklist-link');
    const textarea = document.querySelector('textarea[name="observations"]');

    link.addEventListener('click', async function (event) {
        event.preventDefault();

        const action = this.getAttribute('href');
        const observations = textarea.value;

        // Préparation des données à envoyer
        const formData = new FormData();
        formData.append('observations', observations);
        formData.append('_token', '{{csrf_token()}}'); // à générer côté serveur avant rendu

        try {
            const response = await fetch(action, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la génération du document');
            }

            const blob = await response.blob();

            // Récupération du nom du fichier depuis les en-têtes si disponible
            let filename = 'document.doc';
            const disposition = response.headers.get('Content-Disposition');
            if (disposition && disposition.includes('filename=')) {
                const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (match && match[1]) {
                    filename = match[1].replace(/['"]/g, '');
                }
            }

            // Création d'un lien de téléchargement temporaire
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);

        } catch (error) {
            console.error('Erreur pendant le téléchargement :', error);
            alert('Le téléchargement a échoué.');
        }
    });


    // ---------------------------------------------------------
    // Check-uncheck radio buttons
    // ---------------------------------------------------------
    let lastChecked = null;
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
      radio.addEventListener('click', function() {
        if (this === lastChecked) {
          this.checked = false;
          lastChecked = null;
        } else {
          lastChecked = this;
        }
      });
    });

    //--------------------------------------------------
    // Validation
    //---------------------------------------------------
    /*
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
    */
});

</script>

@endsection

@extends("layout")

@section("content")
<div class="p-3">
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'reports'])<i class="bi bi-graph-up"></i> {{ trans('cruds.exports.index') }}</div>
    <div class="card-body">

			@if (count($errors))
			<div class="form-group">
				<div class="alert alert-danger">
					<ul>
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
					</ul>
				</div>
            </div>
			@endif

			<div class="row">
		        <div class="col-md-10 col-lg-5">
					<b>{{ trans('cruds.imports.title') }}</b>
                    /
                    <a href="/export/alices" target="_blank">Current security Measures</a>
				</div>
			</div>

			<form action="/alice/import" method="post" enctype="multipart/form-data">
			@csrf
    			<div class="row">
    		        <div class="col-md-8 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text">Select model</span>
                            <select class="form-select select2" name="model" id="model">
                                <option id="emptyOption"></option>
                                @foreach($models as $model)
                                    <option>{{ basename($model,'.xlsx') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
    		        <div class="col-md-2 col-lg-1 text-md-end">
    				    <button type="submit" name="action" value="download" class="btn btn-info">
                                {{ trans("common.download") }}
                        </button>
    		        </div>
                </div>
    			<div class="row">
    		        <div class="col-md-10 col-lg-5">
                        or
                        <div class="input-group">
                            <span class="input-group-text">Select import file:</span>
                            <input class="form-control" name="file" type="file" id="file">
                        </div>
    				</div>
    		    </div>
    			<div class="row">
    		        <div class="col-md-8 col-lg-4">
                        <input class="form-check-input" type="checkbox" name="clean" id="clean">
                        Remove all other measures and controls
                    </div>
                </div>
    			<div class="row">
    		        <div class="col-md-8 col-lg-4">
                        <input class="form-check-input" type="checkbox" name="test">
                        Generate fake measurements
                    </div>
    		        <div class="col-md-2 col-lg-1 text-md-end">
    				    <button type="submit" class="btn btn-success"
                            data-confirm-if="#clean" data-confirm="Do you want to remove all other measures and controls  ?">
                                {{ trans("common.import") }}
                        </button>
    		        </div>
                </div>

            </form>


			<div class="row">
		        <div class="col-md-10 col-lg-5">
		        	The import format is an XLSX document with these columns :
		        </div>
		    </div>
			<div class="row">
		        <div class="col-md-12 col-lg-6">

		    	<table 	class="table table-sm">
		    		<thead>
		        		<tr>
		        			<td>Column</td><td>Name</td><td>Type</td><td>Description</td>
		        		</tr>
		    		</thead>
		    		<tbody>
		        		<tr>
		        			<td>A</td><td>Framework</td><td>string(32)</td>
		        			<td>
                                The security framework used.
		        			</td>
		        		</tr>
		        		<tr>
		        			<td>B</td><td>Domain name</td><td>string(32)</td>
		        			<td>
		        				The domains name, it is created if it does not exists.
		        			</td>
		        		</tr>
		        		<tr>
		        			<td>C</td><td>Domain description</td><td>string(255)</td>
		        			<td>
                                The description of the domain.
		        			</td>
		        		</tr>
		        		<tr>
		        			<td>D</td><td>Clause</td><td>string(32)</td>
		        			<td>
		        					If the clause exists the security measure is updated,<br>
		        					if the clause does not exists, a new security measure is created,<br>
		        					if all other fields of the line are empty, the measure, related controls and documents are removed.
		        			</td>
		        		</tr>
		        		<tr>
		        			<td>E</td><td>Name</td><td>string(255)</td><td>The name of the security measure</td>
		        		</tr>
		        		<tr>
		        			<td>F</td><td>Description</td><td>text</td><td>The description of the security measure</td>
		        		</tr>
		        		<tr>
		        			<td>G</td><td>Attributes</td><td>text</td><td>List of tags (#... #... #...)</td>
		        		</tr>
		        		<tr>
		        			<td>H</td><td>Input</td><td>text</td><td>The input elements</td>
		        		</tr>
		        		<tr>
		        			<td>I</td><td>Model</td><td>text</td><td>The computation model</td>
		        		</tr>
		        		<tr>
		        			<td>J</td><td>Indicator</td><td>text</td><td>The indicator (Green, Orange, Red)</td>
		        		</tr>
		        		<tr>
		        			<td>K</td><td>Action plan</td><td>text</td><td>The proposed action plan</td>
		        		</tr>
		        	</tbody>
		    	</table>

		        </div>
		    </div>

			<div class="row">
		        <div class="col-md-12 col-lg-6 text-danger">
		        	This action could not be undone, take a backup before !
		        </div>
		    </div>

		</div>
</div>
@endsection

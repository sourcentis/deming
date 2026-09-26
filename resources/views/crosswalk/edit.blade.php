@extends('layout')

@section('content')
<div data-role="panel"
     data-title-caption="{{ trans('cruds.crosswalk.edit') }}"
     data-collapsible="false"
     data-title-icon="<span class='mif-shuffle'></span>">
    <form method="POST" action="{{ route('crosswalk.update', $mapping) }}">
        @csrf
        @method('PUT')
        @include('crosswalk._form')
    </form>
</div>
@endsection

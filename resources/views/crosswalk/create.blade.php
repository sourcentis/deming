@extends('layout')

@section('content')
<div data-role="panel"
     data-title-caption="{{ trans('cruds.crosswalk.create') }}"
     data-collapsible="false"
     data-title-icon="<span class='mif-shuffle'></span>">
    <form method="POST" action="{{ route('crosswalk.store') }}">
        @csrf
        @include('crosswalk._form')
    </form>
</div>
@endsection

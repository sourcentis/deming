@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'logs'])<i class="bi bi-journal-text"></i> {{ trans("cruds.log.index")}}</div>
    <div class="card-body">

    <div class="container-fluid px-0">

    <table data-order="[[6,&quot;desc&quot;]]" data-paging="false" data-page-length="100"
        id="logs"
        class="table table-striped table-hover table-bordered datatable"
       >
            <thead>
                <th>
                    id
                </th>
                <th>
                    {{ trans('cruds.log.action') }}
                </th>
                <th>
                    {{ trans('cruds.log.subject_type') }}
                </th>
                <th>
                    {{ trans('cruds.log.subject_id') }}
                </th>
                <th>
                    {{ trans('cruds.log.user') }}
                </th>
                <th>
                    {{ trans('cruds.log.host') }}
                </th>
                <th>
                    {{ trans('cruds.log.timestamp') }}
                </th>
                <th>
                </th>
        </thead>
        <tbody>
                @foreach($logs as $log)
                <tr data-entry-id="{{ $log->id }}">
                    <td>
                        <a href="/logs/show/{{ $log->id }}">
                        {{ $log->id }}
                        </a>
                    </td>
                    <td>
                        {{ $log->description }}
                    </td>
                    <td>
                        {{ $log->subject_type }}
                    </td>
                    <td>
                        @if ($log->subject_type=='App\Models\Control')
                            <a href="/alice/show/{{ $log->subject_id }} ">
                                {{ $log->subject_id }}
                            </a>
                        @elseif ($log->subject_type=='App\Models\Measure')
                            <a href="/bob/show/{{ $log->subject_id }} ">
                                {{ $log->subject_id }}
                            </a>
                        @elseif ($log->subject_type=='App\Models\Action')
                            <a href="/action/show/{{ $log->subject_id }} ">
                                {{ $log->subject_id }}
                            </a>
                        @elseif ($log->subject_type=='App\Models\User')
                            <a href="/users/{{ $log->subject_id }} ">
                                {{ $log->subject_id }}
                            </a>
                        @elseif ($log->subject_type=='App\Models\Risk')
                            <a href="/risk/show/{{ $log->subject_id }} ">
                                {{ $log->subject_id }}
                            </a>
                        @else
                            {{ $log->subject_id }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.show', $log->user_id) }}">
                        {{ $log->name }}
                        </a>
                    </td>
                    <td>
                        {{ $log->host }}
                    </td>
                    <td>
                        {{ $log->created_at }}
                    </td>
                    <td nowrap>
                        <a class="btn btn-success btn-sm" href="/logs/show/{{ $log->id }}">
                            {{ trans('common.show') }}
                        </a>
                        <a class="btn btn-info btn-sm"
                        @if ($log->subject_type=='App\\Models\\Measure')
                            href="/logs/history/alice/{{ $log->subject_id }}"
                        @elseif ($log->subject_type=='App\\Models\\Control')
                            href="/logs/history/bob/{{ $log->subject_id }}"
                        @elseif ($log->subject_type=='App\\Models\\Action')
                            href="/logs/history/action/{{ $log->subject_id }}"
                        @elseif ($log->subject_type=='App\\Models\\User')
                            href="/logs/history/user/{{ $log->subject_id }}"
                        @elseif ($log->subject_type=='App\\Models\\Risk')
                            href="/logs/history/risk/{{ $log->subject_id }}"
                        @endif
                        >
                            {{ trans('common.history') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $logs->links() }}
    </div>
</div>
</div>
@endsection

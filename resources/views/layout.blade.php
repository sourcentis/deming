<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="metro:smooth_scroll" content="true">
    <title>Deming - @yield('title', 'ISMS Controls Made Easy')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
    @if (Config::get('app.test'))
    <style>
    .navview-content {
        padding-top: 50px;
    }
    .navview-pane {
        padding-top: 50px;
    }
    </style>
    @endif
    {{-- Metro UI Calendar: start week on Monday for European --}}
    <script>
        (function() {
            const weekStart = {{ in_array(app()->getLocale(), ['fr', 'de']) ? 1 : 0 }};
            window.metroCalendarPickerSetup = { weekStart: weekStart };
            window.metroCalendarSetup = { weekStart: weekStart };
        })();
    </script>

    {{-- Metro UI Table: remember the "rows per page" chosen by the user for each table --}}
    <script>
        (function() {
            const STORAGE_PREFIX = "metro-table-rows:";

            function getRowsSelect(tableId) {
                const table = document.getElementById(tableId);
                const component = table ? table.closest(".table-component") : null;
                return component ? component.querySelector(".table-rows-block select") : null;
            }

            window.metroTableSetup = {
                onTableCreate: function () {
                    const id = this.id;
                    const saved = id ? localStorage.getItem(STORAGE_PREFIX + id) : null;
                    if (!saved) {
                        return;
                    }

                    const select = getRowsSelect(id);
                    const hasOption = select && Array.from(select.options).some((o) => o.value === saved);
                    if (!hasOption) {
                        return;
                    }

                    const plugin = Metro.getPlugin(select, "select");
                    if (plugin) {
                        plugin.val(saved);
                    }
                },
                onRowsCountChange: function (val) {
                    if (this.id) {
                        localStorage.setItem(STORAGE_PREFIX + this.id, val);
                    }
                },
            };
        })();
    </script>

</head>
<body class="cloak">
@if (Config::get('app.test'))
<div class="app-bar pos-fixed bg-orange fg-white" data-role="appbar">
      <div class="app-bar-section">
        <span class="mif-warning"></span> {{ trans('menu.test') }}
    </div>
</div>
@endif
<div id="navview" data-role="navview" data-expand-point="md">
    <div class="navview-pane">
        <div class="logo-container">
            <button class="pull-button">
                <span class="mif-menu"></span>
            </button>
           <a href="/" class="d-flex flex-align-center bg-transparent">
                <div class="enlarge-2x text-weight-9">Deming</div>
            </a>
        </div>

        <form id="search-form" action="/global-search" method="GET">
            <div class="suggest-box">
                <input type="text" data-role="input" name="search" value="{{ $search ?? '' }}" id="search" data-clear-button="false" data-search-button="true">
                <button class="holder">
                    <span class="mif-search"></span>
                </button>
            </div>
        </form>

        <ul class="navview-menu pad-second-level" id="side-menu">
            <li class="{{ request()->is('/') ? 'active': '' }}">
                <a href="/">
                    <span class="icon mif-home"></span>
                    <span class="caption">{{ trans("menu.home") }}</span>
                </a>
            </li>
            <li class="{{
                    request()->is('alice*') && !request()->is('alice/import')
                    ? 'active': '' }}">
                <a href="/alice/index">
                    <span class="icon mif-books"></span>
                    <span class="caption">{{ trans("menu.measures") }}</span>
                </a>
            </li>
            <li class="{{ request()->is('bob/index') ? 'active': '' }}">
                <a href="/bob/index">
                    <span class="icon mif-paste"></span>
                    <span class="caption">{{ trans("menu.controls") }}</span>
                </a>
            </li>
            @if (Auth::User()->role <= 3)
            <li class="{{ request()->is('bob/history') ? 'active': '' }}">
                <a href="/bob/history">
                    <span class="icon mif-calendar"></span>
                    <span class="caption">{{ trans("menu.planning") }}</span>
                </a>
            </li>
            <li class="{{ request()->is('action*') ? 'active': '' }}">
                <a href="/actions">
                    <span class="icon mif-pending-actions"></span>
                    <span class="caption">{{ trans("menu.action_plan") }}</span>
                </a>
            </li>
            <li class="{{ request()->is('risk/index*') ? 'active': '' }}">
                <a href="/risk/index">
                    <span class="icon mif-warning"></span>
                    <span class="caption">{{ trans("menu.risks") }}</span>
                </a>
            </li>
            <li class="{{ request()->is('risk/matrix*') ? 'active': '' }}">
                <a href="/risk/matrix">
                    <span class="icon mif-grid"></span>
                    <span class="caption">{{ trans("menu.risks_matrix") }}</span>
                </a>
            </li>
            <li class="{{ request()->is('exceptions*') ? 'active': '' }}">
                <a href="/exception/index">
                    <span class="icon mif-cross"></span>
                    <span class="caption">{{ trans("menu.exceptions") }}</span>
                </a>
            </li>
            <li class="{{ request()->is('radar/*') ? 'active': '' }}">
                <a id="nav-radar" href="#" class="dropdown-toggle">
                    <span class="icon mif-meter"></span>
                    <span class="caption">{{ trans("menu.radar") }}</span>
                </a>
                <ul class="navview-menu"
                    data-role="collapse"
                    data-collapsed="{{ request()->is('radar/*') ? 'false': 'true' }}">
                    <li class="{{ request()->is('radar/domains') ? 'active': '' }}">
                        <a href="/radar/domains">
                        <span class="icon mif-stacked-bar-chart"></span>
                        <span class="caption">{{ trans("menu.radar_by_domains") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('radar/bob') ? 'active': '' }}">
                        <a href="/radar/bob">
                        <span class="icon mif-timeline"></span>
                        <span class="caption">{{ trans("menu.radar_by_measure") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('radar/alice') ? 'active': '' }}">
                        <a href="/radar/alice">
                        <span class="icon mif-pie-chart"></span>
                        <span class="caption">{{ trans("menu.radar_by_controls") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('radar/attributes') ? 'active': '' }}">
                        <a href="/radar/attributes">
                        <span class="icon mif-pie-chart"></span>
                        <span class="caption">{{ trans("menu.radar_by_attributes") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('radar/actions') ? 'active': '' }}">
                        <a href="/radar/actions">
                        <span class="icon mif-stacked-bar-chart"></span>
                        <span class="caption">{{ trans("menu.radar_by_actions") }}</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="{{ request()->is('reports*') ? 'active': '' }}">
                <a href="/reports">
                    <span class="icon mif-file-text"></span>
                    <span class="caption">{{ trans("menu.configuration.reports") }}</span>
                </a>
            </li>


            <li class="{{
                (
                    request()->is('attributes*')||
                    request()->is('domains*')||
                    request()->is('users*')||
                    request()->is('group*')||
                    request()->is('alice/import*')||
                    request()->is('doc*')||
                    request()->is('config*')||
                    request()->is('crosswalk*')||
                    request()->is('logs*')
                ) ? 'active': '' }}">
                <a href="#" class="dropdown-toggle open">
                    <span class="icon mif-cog"></span>
                    <span class="caption">{{ trans("menu.configuration.title") }}</span>
                </a>
                <ul class="navview-menu"
                    data-role="collapse"
                    data-collapsed="{{
                        (
                            request()->is('attributes*')||
                            request()->is('domains*')||
                            request()->is('users*')||
                            request()->is('group*')||
                            request()->is('risk/scoring*')||
                            request()->is('alice/import*')||
                            request()->is('doc*')||
                            request()->is('config*')||
                            request()->is('crosswalk*')||
                            request()->is('logs*')
                        ) ? 'false': 'true' }}">
                    <li  class="{{ request()->is('attributes*') ? 'active': '' }}">
                        <a href="/attributes">
                            <span class="icon mif-tags"></span>
                            <span class="caption">{{ trans("menu.attributes") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('domains*') ? 'active': '' }}">
                        <a href="/domains">
                            <span class="icon mif-library"></span>
                            <span class="caption">{{ trans("menu.domains") }}</span>
                        </a>
                    </li>
                    @if (Auth::User()->role==1)
                    <li class="{{ request()->is('users*') ? 'active': '' }}">
                        <a href="/users">
                        <span class="icon mif-person"></span>
                        <span class="caption">{{ trans("menu.configuration.users") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('group*') ? 'active': '' }}">
                        <a href="/groups">
                        <span class="icon mif-group"></span>
                        <span class="caption">{{ trans("menu.configuration.groups") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('risk/scoring*') ? 'active': '' }}">
                        <a href="/risk/scoring">
                        <span class="icon mif-calculator"></span>
                        <span class="caption">{{ trans("menu.configuration.scoring") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('alice/import*') ? 'active': '' }}">
                        <a href="/alice/import">
                        <span class="icon mif-import"></span>
                        <span class="caption">{{ trans("menu.configuration.import") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('crosswalk*') ? 'active': '' }}">
                        <a href="{{ route('crosswalk.index') }}">
                            <span class="icon mif-shuffle"></span>
                            <span class="caption">{{ trans('menu.crosswalk') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('doc*') ? 'active': '' }}">
                        <a href="/doc">
                        <span class="icon mif-file-text"></span>
                        <span class="caption">{{ trans("menu.configuration.documents") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('config*') ? 'active': '' }}">
                        <a href="/config">
                        <span class="icon mif-alarm"></span>
                        <span class="caption">{{ trans("menu.configuration.notifications") }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('logs*') ? 'active': '' }}">
                        <a href="/logs">
                        <span class="icon mif-log-file"></span>
                        <span class="caption">Logs</span>
                        </a>
                    </li>
                    @endif
                    </ul>
                </li>
                @endif
                <li>
                    <a class="dropdown-item" href="/logout"
                       onclick="event.preventDefault();
                                     document.getElementById('logout-form').submit();">
                        <span class="icon mif-exit"></span>
                        <span class="caption">{{ trans("menu.logout") }}</span>

                    </a>
                    <form id="logout-form" action="/logout" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        <div class="w-100 text-center text-small data-box p-2 border-top bd-grayMouse" style="position: absolute; bottom: 0">
            Version {{ $appVersion }}
        </div>
    </div>

    <div class="navview-content">
        <div data-role="appbar" class="bg-reserve-steppe border-bottom bd-default" data-expand-point="fs">
            <div class="app-bar-item-static d-none-fs d-flex-md">
                <div class="text-bold enlarge-2" id="content-title">
                @yield('none')
                </div>
            </div>
            <ul class="app-bar-menu ml-auto">
                <a href="/bob/index?attribute=none&period=0&scope=none&domain=0&status=2" class="no-underline"
                   title="{{ __('menu.new') }}" aria-label="{{ __('menu.new') }}">
                    <span class="mif-mail-outline mif-2x"></span>
                    @if (Session::get("planed_controls_this_month_count")!=null)
                    <span class="badge bg-green fg-white mt-2 mr-1">{{Session::get("planed_controls_this_month_count")}}</span>
                    @else
                        &nbsp;
                    @endif
                </a>
                <a href="/bob/index?attribute=none&period=99&scope=none&domain=0&status=1&late=1" class="no-underline"
                   title="{{ __('menu.late') }}" aria-label="{{ __('menu.late') }}">
                    <span class="mif-notifications mif-2x"></span>
                    @if (Session::get("late_controls_count")!=null)
                    <span class="badge bg-red fg-white mt-2 mr-1">{{Session::get("late_controls_count")}}</span>
                    @else
                        &nbsp;
                    @endif
                </a>
                <a href="/actions" class="no-underline"
                   title="{{ __('menu.action_plan') }}" aria-label="{{ __('menu.action_plan') }}">
                    <span class="mif-flag mif-2x"></span>
                    @if (Session::get("action_plans_count")!=null)
                    <span class="badge bg-blue fg-white mt-2 mr-1">{{Session::get("action_plans_count")}}</span>
                    @else
                        &nbsp;
                    @endif
                </a>
                @if(auth()->user()->isAdmin() || auth()->user()->isUser())
                <a href="/group/toggle" class="no-underline group-view-toggle {{ auth()->user()->seesAllData() ? 'group-view-active' : '' }}"
                   title="{{ auth()->user()->seesAllData() ? __('common.my_data')  : __('common.all_data') }}"
                   aria-label="{{ auth()->user()->seesAllData() ? __('common.my_data') : __('common.all_data') }}">
                    <span class="mif-group mif-2x"></span>
                </a>
                @endif
                <a href="/users/{{ Auth::User()->id }}/edit" class="no-underline"
                   title="{{ __('menu.profile') }}" aria-label="{{ __('menu.profile') }}">
                    <span class="mif-person mif-2x"></span>
                    <span class="badge bg-black fg-white mt-2 mr-1">{{ Auth::User()->initiales() }}</span>
                </a>
                <li>
                    <a href="/about" target="_blank" rel="noopener noreferrer" class="no-underline">
                        <span class="mif-help-outline mif-2x"></span>
                    </a>
                </li>
            </ul>

            <div class="app-bar-item-static">
                <input type="checkbox" data-role="theme-switcher" />
            </div>
        </div>

        <main id="page-content">
@yield('content')
        </main>
    </div>
</div>
</body>
</html>

@php
    $configActive = request()->is('attributes*') || request()->is('domains*') || request()->is('users*')
        || request()->is('group*') || request()->is('risk/scoring*') || request()->is('alice/import*')
        || request()->is('doc*') || request()->is('config*') || request()->is('crosswalk*') || request()->is('logs*');
@endphp
<nav id="sidebar" class="sidebar">
    <div class="search-box">
        <form id="search-form" action="/global-search" method="GET">
            <div class="input-group input-group-sm">
                <input type="text" name="search" value="{{ $search ?? '' }}" id="search" class="form-control">
                <button class="btn btn-outline-secondary" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>

    <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">
        <i class="bi bi-house"></i><span class="menu-text">{{ trans("menu.home") }}</span>
    </a>
    <a href="/alice/index" class="{{ request()->is('alice*') && !request()->is('alice/import') ? 'active' : '' }}">
        <i class="bi bi-book"></i><span class="menu-text">{{ trans("menu.measures") }}</span>
    </a>
    <a href="/bob/index" class="{{ request()->is('bob/index') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check"></i><span class="menu-text">{{ trans("menu.controls") }}</span>
    </a>
    @if (Auth::User()->role <= 3)
    <a href="/bob/history" class="{{ request()->is('bob/history') ? 'active' : '' }}">
        <i class="bi bi-calendar3"></i><span class="menu-text">{{ trans("menu.planning") }}</span>
    </a>
    <a href="/actions" class="{{ request()->is('action*') ? 'active' : '' }}">
        <i class="bi bi-list-check"></i><span class="menu-text">{{ trans("menu.action_plan") }}</span>
    </a>
    <a href="/risk/index" class="{{ request()->is('risk/index*') ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle"></i><span class="menu-text">{{ trans("menu.risks") }}</span>
    </a>
    <a href="/risk/matrix" class="{{ request()->is('risk/matrix*') ? 'active' : '' }}">
        <i class="bi bi-grid-3x3"></i><span class="menu-text">{{ trans("menu.risks_matrix") }}</span>
    </a>
    <a href="/exception/index" class="{{ request()->is('exception*') ? 'active' : '' }}">
        <i class="bi bi-x-octagon"></i><span class="menu-text">{{ trans("menu.exceptions") }}</span>
    </a>

    <a id="nav-radar" class="dropdown-toggle" data-bs-toggle="collapse" href="#submenu-radar" role="button"
       aria-expanded="{{ request()->is('radar/*') ? 'true' : 'false' }}">
        <i class="bi bi-speedometer2"></i><span class="menu-text">{{ trans("menu.radar") }}</span>
    </a>
    <div id="submenu-radar" class="collapse {{ request()->is('radar/*') ? 'show' : '' }}">
        <a href="/radar/monitoring" class="{{ request()->is('radar/monitoring') ? 'active' : '' }}">
            <i class="bi bi-activity"></i><span class="menu-text">{{ trans("menu.radar_monitoring") }}</span>
        </a>
        <a href="/radar/domains" class="{{ request()->is('radar/domains') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i><span class="menu-text">{{ trans("menu.radar_by_domains") }}</span>
        </a>
        <a href="/radar/bob" class="{{ request()->is('radar/bob') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i><span class="menu-text">{{ trans("menu.radar_by_measure") }}</span>
        </a>
        <a href="/radar/alice" class="{{ request()->is('radar/alice') ? 'active' : '' }}">
            <i class="bi bi-pie-chart"></i><span class="menu-text">{{ trans("menu.radar_by_controls") }}</span>
        </a>
        <a href="/radar/attributes" class="{{ request()->is('radar/attributes') ? 'active' : '' }}">
            <i class="bi bi-pie-chart"></i><span class="menu-text">{{ trans("menu.radar_by_attributes") }}</span>
        </a>
        <a href="/radar/actions" class="{{ request()->is('radar/actions') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i><span class="menu-text">{{ trans("menu.radar_by_actions") }}</span>
        </a>
    </div>

    <a href="/reports" class="{{ request()->is('reports*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i><span class="menu-text">{{ trans("menu.configuration.reports") }}</span>
    </a>

    <a class="dropdown-toggle" data-bs-toggle="collapse" href="#submenu-config" role="button"
       aria-expanded="{{ $configActive ? 'true' : 'false' }}">
        <i class="bi bi-gear"></i><span class="menu-text">{{ trans("menu.configuration.title") }}</span>
    </a>
    <div id="submenu-config" class="collapse {{ $configActive ? 'show' : '' }}">
        <a href="/attributes" class="{{ request()->is('attributes*') ? 'active' : '' }}">
            <i class="bi bi-tags"></i><span class="menu-text">{{ trans("menu.attributes") }}</span>
        </a>
        <a href="/domains" class="{{ request()->is('domains*') ? 'active' : '' }}">
            <i class="icon-library"></i><span class="menu-text">{{ trans("menu.domains") }}</span>
        </a>
        @if (Auth::User()->role==1)
        <a href="/users" class="{{ request()->is('users*') ? 'active' : '' }}">
            <i class="bi bi-person"></i><span class="menu-text">{{ trans("menu.configuration.users") }}</span>
        </a>
        <a href="/groups" class="{{ request()->is('group*') ? 'active' : '' }}">
            <i class="bi bi-people"></i><span class="menu-text">{{ trans("menu.configuration.groups") }}</span>
        </a>
        <a href="/alice/import" class="{{ request()->is('alice/import*') ? 'active' : '' }}">
            <i class="bi bi-box-arrow-in-down"></i><span class="menu-text">{{ trans("menu.configuration.import") }}</span>
        </a>
        <a href="{{ route('crosswalk.index') }}" class="{{ request()->is('crosswalk*') ? 'active' : '' }}">
            <i class="bi bi-shuffle"></i><span class="menu-text">{{ trans('menu.crosswalk') }}</span>
        </a>
        {{-- Documents, templates, classification, AI and notifications, in tabs --}}
        <a href="/config/parameters" class="{{ request()->is('config*') || request()->is('risk/scoring*') || request()->is('doc/check') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i><span class="menu-text">{{ trans("menu.configuration.parameters") }}</span>
        </a>
        <a href="/logs" class="{{ request()->is('logs*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i><span class="menu-text">Logs</span>
        </a>
        @endif
    </div>
    @endif

    <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="bi bi-box-arrow-right"></i><span class="menu-text">{{ trans("menu.logout") }}</span>
    </a>

    <div class="sidebar-footer">
        Version {{ $appVersion }}
    </div>
</nav>

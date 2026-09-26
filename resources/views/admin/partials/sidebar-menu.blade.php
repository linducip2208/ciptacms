<ul class="navbar-nav pt-lg-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <span class="nav-link-icon"><i class="ti ti-home"></i></span> Dashboard
        </a>
    </li>
    @if(!empty($adminMenu))
        @include('admin.partials.menu-tree', ['items' => $adminMenu])
    @endif
</ul>
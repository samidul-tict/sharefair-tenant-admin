<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Share Fair</title>
<!-- General CSS Files -->
<link rel="stylesheet" href="{{ asset('backend-assets/css/app.min.css') }}"/>
<!-- Template CSS -->
<link rel="stylesheet" href="{{ asset('backend-assets/css/style.css') }}"/>
<link rel="stylesheet" href="{{ asset('backend-assets/css/components.css') }}"/>
<!-- Custom style CSS -->
<link rel="stylesheet" href="{{ asset('backend-assets/css/custom.css') }}"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;900&family=Cabinet+Grotesk:wght@400;500;700;800&family=IBM+Plex+Mono:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel='shortcut icon' type='image/x-icon' href="{{ asset('backend-assets/img/favicon.ico') }}" />
@stack('styles')
<style>
.main-content {padding-top: 95px!important;}
.table:not(.table-sm):not(.table-md):not(.dataTable) td, .table:not(.table-sm):not(.table-md):not(.dataTable) th {height: 40px!important;}
.skip-link { position: absolute; left: -9999px; z-index: 9999; padding: 0.5rem 1rem; font-weight: 600; }
.skip-link:focus { left: 0.5rem; top: 0.5rem; background: #fff; color: #0f172a; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
*:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
</style>
</head>

<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="loader" aria-hidden="true"></div>
<div id="app">
    @php
        // Check loggedin user and their role/tenant
        $logUser =  \App\Models\User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();
    @endphp
    <div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg" aria-hidden="true"></div>
    <nav class="navbar navbar-expand-lg main-navbar sticky" aria-label="Primary navigation">
        <div class="form-inline mr-auto">
            <ul class="navbar-nav mr-3">
                <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg collapse-btn" aria-label="Toggle sidebar menu"> <i data-feather="align-justify" aria-hidden="true"></i></a></li>
                <li><a href="#" class="nav-link nav-link-lg fullscreen-btn" aria-label="Toggle fullscreen"> <i data-feather="maximize" aria-hidden="true"></i> </a></li>
                <!--<li>-->
                <!--    <form class="form-inline mr-auto">-->
                <!--        <div class="search-element">-->
                <!--            <input class="form-control" type="search" placeholder="Search" aria-label="Search" data-width="200">-->
                <!--            <button class="btn" type="submit">-->
                <!--            <i class="fas fa-search"></i>-->
                <!--            </button>-->
                <!--        </div>-->
                <!--    </form>-->
                <!--</li>-->
            </ul>
        </div>
        <ul class="navbar-nav navbar-right">
            <!--<li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown" class="nav-link nav-link-lg message-toggle"><i data-feather="mail"></i> <span class="badge headerBadge1"> 6 </span> </a>-->
            <!--    <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">-->
            <!--        <div class="dropdown-header">-->
            <!--            Messages <div class="float-right"> <a href="#">Mark All As Read</a> </div>-->
            <!--        </div>-->
            <!--        <div class="dropdown-list-content dropdown-list-message">-->
            <!--            <a href="#" class="dropdown-item"> -->
            <!--                <span class="dropdown-item-avatar text-white"> -->
            <!--                    <img alt="image" src="{{ asset('backend-assets/img/users/user-1.png') }}" class="rounded-circle">-->
            <!--                </span> -->
            <!--                <span class="dropdown-item-desc"> -->
            <!--                    <span class="message-user">John Deo</span>-->
            <!--                    <span class="time messege-text">Please check your mail !!</span>-->
            <!--                    <span class="time">2 Min Ago</span>-->
            <!--                </span>-->
            <!--            </a> -->
            <!--        </div>-->
            <!--        <div class="dropdown-footer text-center">-->
            <!--            <a href="#">View All <i class="fas fa-chevron-right"></i></a>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</li>-->
            <!--<li class="dropdown dropdown-list-toggle">-->
            <!--    <a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg">-->
            <!--        <i data-feather="bell" class="bell"></i>-->
            <!--    </a>-->
            <!--    <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">-->
            <!--        <div class="dropdown-header">-->
            <!--            Notifications <div class="float-right"> <a href="#">Mark All As Read</a> </div>-->
            <!--        </div>-->
            <!--        <div class="dropdown-list-content dropdown-list-icons">-->
            <!--            <a href="#" class="dropdown-item dropdown-item-unread"> -->
            <!--                <span class="dropdown-item-icon bg-primary text-white"> <i class="fas fa-code"></i></span> -->
            <!--                <span class="dropdown-item-desc"> -->
            <!--                    Template update is available now! <span class="time">2 Min Ago</span>-->
            <!--                </span>-->
            <!--            </a> -->
            <!--        </div>-->
            <!--        <div class="dropdown-footer text-center">-->
            <!--            <a href="#">View All <i class="fas fa-chevron-right"></i></a>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</li>-->
            <li class="dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user" aria-label="User menu for {{ Auth::user()->name }}" aria-haspopup="true" aria-expanded="false"> 
                    <img alt="" src="{{ asset('backend-assets/img/user.png') }}" class="user-img-radious-style"> 
                    <span class="d-sm-none d-lg-inline-block"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right pullDown" role="menu">
                    <div class="dropdown-title" role="presentation">Hello {{ Auth::user()->name }}</div>
                    <!--<a href="#" class="dropdown-item has-icon"> -->
                    <!--    <i class="far fa-user"></i> Profile-->
                    <!--</a> -->
                    <!--<a href="timeline.html" class="dropdown-item has-icon"> -->
                    <!--    <i class="fas fa-bolt"></i> Activities-->
                    <!--</a>-->
                    <a href="{{ route('admin.profile') }}" class="dropdown-item has-icon" role="menuitem"> 
                        <i class="far fa-user" aria-hidden="true"></i> Profile
                    </a>
                    
                    @if(isset($logUser) && $logUser->user_role_id == 'TENANT_A')
                    <a href="{{ route('admin.my-subscriptions') }}" class="dropdown-item has-icon" role="menuitem"> 
                        <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> My Subscriptions
                    </a>
                    @endif
                    {{-- Reset password: re-enable by uncommenting the link below (routes & UserController methods remain available) --}}
                    {{-- <a href="{{ route('admin.users.reset-password', Auth::id()) }}" class="dropdown-item has-icon" role="menuitem">
                        <i class="fas fa-key" aria-hidden="true"></i> Reset password
                    </a> --}}
                    <div class="dropdown-divider"></div>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="#" class="dropdown-item has-icon text-danger" role="menuitem" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" aria-label="Log out">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
            <div class="sidebar-brand">
                <a href="{{ route('admin.dashboard') }}" aria-label="Share Fair home"> 
                    <img alt="" src="{{ asset('backend-assets/img/logo.svg') }}" class="header-logo" /> 
                    <span class="logo-name">Share Fair</span>
                </a>
            </div>
            @php
                $menus = [];

                if ($logUser->user_role_id == 'TENANT_A') {

                    // Super Admin / Full Access
                    $menus = \App\Models\Menu::select('menu.*')
                        ->where('menu.is_active', true)
                        ->where('menu.admin_type', 'TA')
                        ->orderBy('menu.sort_order', 'ASC')
                        ->get();

                } else {

                    // Normal User — Load menu only if any permission exists
                    $menus = \App\Models\Menu::select('menu.*', 'mp.can_view', 'mp.can_create', 'mp.can_edit', 'mp.can_delete')
                        ->join('menu_permission as mp', 'menu.id', '=', 'mp.menu_id')
                        ->where('mp.user_id', $logUser->id)
                        ->where('menu.is_active', true)
                        ->where('menu.admin_type', 'TA')
                        ->where(function($q){
                            $q->where('mp.can_view', true)
                            ->orWhere('mp.can_create', true)
                            ->orWhere('mp.can_edit', true)
                            ->orWhere('mp.can_delete', true);
                        })
                        ->orderBy('menu.sort_order', 'ASC')
                        ->get();
                }
            @endphp

            <ul class="sidebar-menu" role="navigation" aria-label="Main menu">
                @foreach($menus as $menu)
                    @if ($menu->parent_id == null)
                        <li class="menu-header" role="presentation">{{ $menu->menu_name }}</li>
                    @else
                        <li class="dropdown {{ request()->routeIs($menu->route_name) ? 'active' : '' }}">
                            <a href="{{ $menu->route_name ? route($menu->route_name) : '#' }}" class="nav-link" {{ request()->routeIs($menu->route_name) ? 'aria-current="page"' : '' }}>
                                @php
                                    $menuIcon = ($menu->route_name ?? '') === 'admin.cases.index' ? 'briefcase' : $menu->icon;
                                @endphp
                                <i data-feather="{{ $menuIcon }}" aria-hidden="true"></i>
                                <span>{{ $menu->menu_name }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

        </aside>

    </div>
    <!-- Main Content -->
    <main id="main-content" class="main-content" role="main">
        @yield('proxima')
    </main>
</div>
</div>

<!-- General JS Scripts -->
<script src="{{ asset('backend-assets/js/app.min.js') }}"></script>
<!-- JS Libraies -->
<script src="{{ asset('backend-assets/bundles/apexcharts/apexcharts.min.js') }}"></script>
<!-- Page Specific JS File -->
<script src="{{ asset('backend-assets/js/page/index.js') }}"></script>
<!-- Template JS File -->
<script src="{{ asset('backend-assets/js/scripts.js') }}"></script>
<!-- Custom JS File -->
<script src="{{ asset('backend-assets/js/custom.js') }}"></script>
@stack('scripts')
</body>
</html>
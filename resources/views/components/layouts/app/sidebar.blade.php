<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <div class="col-md-3 left_col">
                <div class="left_col scroll-view">
                    <div class="navbar nav_title" style="border: 0;">
                        <a href="{{ route('dashboard') }}" class="site_title">
                            <span>
                                <center>
                                    <b>ICS</b>
                                </center>
                            </span>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                    <!-- menu profile quick info -->
                    <div class="profile clearfix">
                        <div class="profile_info">
                            Admin
                            <h2>
                                [ <u>paki</u> ]
                                <br>
                                <br>
                            </h2>
                        </div>
                    </div>
                    <!-- /menu profile quick info -->
                    {{-- <br /> --}}
                    <!-- sidebar menu -->
                    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                        <div class="menu_section">
                            <h3>__________________________</h3>
                            <ul class="nav side-menu">
                                <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><a href="{{ route('dashboard') }}" wire:navigate><i class="fa fa-home"></i>
                                        Dashboard</a></li>
                                <li class="{{ request()->routeIs('customers') ? 'active' : '' }}"><a href="{{ route('customers') }}" wire:navigate><i class="fa fa-users"></i>
                                        Customers</a></li>
                                <li class="{{ request()->routeIs('tasks') ? 'active' : '' }}"><a href="{{ route('tasks') }}" wire:navigate><i class="fa fa-check-square-o"></i>
                                        Tasks</a></li>
                                <li class="{{ request()->routeIs('tools') ? 'active' : '' }}"><a href="{{ route('tools') }}" wire:navigate><i class="fa fa-wrench"></i> Tools</a>
                                </li>
                                <li class="{{ request()->routeIs('tire-job-orders') ? 'active' : '' }}"><a href="{{ route('tire-job-orders') }}" wire:navigate><i
                                            class="fa fa-briefcase"></i> Tire Job Orders</a></li>
                                <li class="{{ request()->routeIs('calculation') ? 'active' : '' }}"><a href="{{ route('calculation') }}" wire:navigate><i
                                            class="fa fa-calculator"></i> Repair Calculator</a></li>
                            </ul>
                            <h3>__________________________</h3>
                        </div>
                    </div>
                    <!-- /sidebar menu -->
                </div>
            </div>
            <!-- top navigation -->
            <div class="top_nav">
                <div class="nav_menu">
                    <div class="nav toggle">
                        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                    </div>
                    {{-- <nav class="nav navbar-nav">
                        <ul class=" navbar-right">
                            <li class="nav-item dropdown open" style="padding-left: 15px;">
                                <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown" data-toggle="dropdown" aria-expanded="false">
                                    <img src="https://colorlib.com/polygon/gentelella/images/img.jpg" alt="">{{ auth()->user()->name }}
                                </a>
                                <div class="dropdown-menu dropdown-usermenu pull-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="javascript:;"> Profile</a>
                                    <a class="dropdown-item" href="javascript:;">
                                        <span class="badge bg-red pull-right">50%</span>
                                        <span>Settings</span>
                                    </a>
                                    <a class="dropdown-item" href="javascript:;">Help</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="fa fa-sign-out pull-right"></i> Log Out</button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </nav> --}}
                </div>
            </div>
            <!-- /top navigation -->
            <!-- page content -->
            <div class="right_col" role="main">
                {{ $slot }}
            </div>
            <!-- /page content -->
            <!-- footer content -->
            <footer>
                <div class="pull-right">
                    Chitra - Jobshop Scheduling System
                </div>
                <div class="clearfix"></div>
            </footer>
            <!-- /footer content -->
        </div>
    </div>
    <script src="/gentela-gh-pages/vendors/moment/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    @stack('scripts')
    @livewireScripts
</body>

</html>

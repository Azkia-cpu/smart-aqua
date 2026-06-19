<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SmartAqua') }} — @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @yield('styles')
</head>
<body class="dashboard-body">

    <!-- NAVBAR -->
    <nav class="sa-navbar">
        <div class="sa-navbar-inner">
            <!-- Left: Brand -->
            <div class="sa-navbar-brand">
                <span class="sa-brand-logo">IoT</span>
                <div class="sa-brand-text">
                    <span class="sa-brand-title">SMART AQUACULTURE</span>
                    <span class="sa-brand-subtitle">DASHBOARD MONITORING AIR</span>
                </div>
            </div>

            <!-- Center: Tabs (Kolam selector as dropdown + Lokasi) -->
            <div class="sa-navbar-center">
                <div class="sa-tab-group">
                    @if(isset($ponds) && $ponds->count() > 0)
                    <div class="dropdown">
                        <button class="sa-tab active dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Kolam {{ $currentPond->name ?? '' }}
                        </button>
                        <ul class="dropdown-menu sa-dropdown-menu">
                            @foreach($ponds as $pond)
                            <li>
                                <a class="dropdown-item {{ ($currentPond->id ?? 0) == $pond->id ? 'active' : '' }}"
                                   href="{{ route('dashboard', ['pond' => $pond->code]) }}">
                                    {{ $pond->name }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right: DateTime + Theme toggle + Bell + User -->
            <div class="sa-navbar-right">
                <div class="sa-datetime" id="navDateTime"></div>

                <!-- Theme Toggle -->
                <button class="sa-theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>

                <!-- Notifications bell -->
                <button class="sa-nav-icon" id="navBellBtn" title="Notifikasi">
                    <i class="bi bi-bell-fill"></i>
                    <span class="sa-badge-count" id="unreadCount" style="display:none;">0</span>
                </button>

                <!-- Admin links (only for admin) -->
                @if(auth()->user() && auth()->user()->is_admin)
                <div class="dropdown">
                    <button class="sa-nav-icon dropdown-toggle" data-bs-toggle="dropdown" title="Admin" aria-expanded="false">
                        <i class="bi bi-gear-fill"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end sa-dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('admin.ponds.index') }}"><i class="bi bi-droplet-half me-2"></i>Kelola Kolam</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.devices.index') }}"><i class="bi bi-cpu me-2"></i>Perangkat</a></li>
                    </ul>
                </div>
                @endif

                <!-- User -->
                <div class="dropdown">
                    <button class="sa-nav-icon sa-user-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end sa-dropdown-menu">
                        <li><span class="dropdown-item-text sa-dropdown-username">{{ Auth::user()->name }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="sa-main">
        <div class="container-fluid px-3 px-md-4 py-3">
            @yield('content')
        </div>
    </main>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

    <script>
        window.SmartAqua = {
            csrfToken: '{{ csrf_token() }}',
            currentPond: '{{ $currentPond->code ?? "" }}',
            pondConfig: {
                minWaterLevel: {{ $currentPond->min_water_level ?? 3 }},
                maxWaterLevel: {{ $currentPond->max_water_level ?? 12 }},
                minPh: {{ $currentPond->min_ph ?? 6.5 }},
                maxPh: {{ $currentPond->max_ph ?? 8.5 }}
            },
            urls: {
                latestData: '{{ route("dashboard.latest", ["pondCode" => ":pondCode"]) }}',
                chartData: '{{ route("dashboard.charts", ["pondCode" => ":pondCode"]) }}',
                notifications: '{{ route("dashboard.notifications", ["pondCode" => ":pondCode"]) }}',
                history: '{{ route("dashboard.history", ["pondCode" => ":pondCode"]) }}',
                pumpControl: '{{ route("dashboard.pump-control") }}',
                markRead: '/dashboard/notifications/:id/read'
            }
        };

        // Convert absolute URLs to relative paths to support dynamic IP access (e.g. localhost and local network IP)
        for (var key in window.SmartAqua.urls) {
            if (window.SmartAqua.urls.hasOwnProperty(key)) {
                var urlStr = window.SmartAqua.urls[key];
                if (urlStr.startsWith('http://') || urlStr.startsWith('https://')) {
                    try {
                        var urlObj = new URL(urlStr);
                        window.SmartAqua.urls[key] = urlObj.pathname + urlObj.search;
                    } catch (e) {
                        console.error('Failed to parse URL:', urlStr, e);
                    }
                }
            }
        }
    </script>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    <script src="{{ asset('js/charts.js') }}"></script>
    <script src="{{ asset('js/pump-control.js') }}"></script>
    @yield('scripts')
</body>
</html>

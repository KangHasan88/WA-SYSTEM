<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KURMIGO - @yield('title', 'WhatsApp Blast')</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Global CSS -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        
        :root {
            --k-white: #ffffff;
            --k-navy: #1e3a8a;
            --k-navy-dark: #172554;
            --k-navy-light: #dbeafe;
            --k-navy-soft: #eff6ff;
            --k-gray-50: #f8fafc;
            --k-gray-100: #f1f5f9;
            --k-gray-200: #e2e8f0;
            --k-gray-300: #cbd5e1;
            --k-gray-400: #94a3b8;
            --k-gray-500: #64748b;
            --k-gray-600: #475569;
            --k-gray-700: #334155;
            --k-gray-800: #1e293b;
            --k-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --k-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --k-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            --k-shadow-navy: 0 4px 12px rgba(30, 58, 138, 0.12);
            --k-green: #10b981;
            --k-red: #ef4444;
            --k-orange: #ea580c;
            --k-orange-dark: #c2410c;
            --k-orange-light: #fed7aa;
            --k-orange-soft: #fff7ed;
        }
        
        /* Global Classes */
        .top-bar { 
            background: var(--k-white); 
            padding: 0.75rem 1.5rem; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            border-bottom: 1px solid var(--k-gray-200); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            box-shadow: var(--k-shadow-sm); 
        }
        
        .page-title h1 { font-size: 1.1rem; font-weight: 700; color: var(--k-navy); margin: 0; }
        .page-title p { font-size: 0.7rem; color: var(--k-gray-500); margin: 0.1rem 0 0 0; }
        .logo-icon { width: 34px; height: 34px; background: var(--k-white); border: 1px solid var(--k-gray-200); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 0.75rem; }
        .logo-icon i { color: var(--k-navy); font-size: 1rem; }
        .top-bar-right { display: flex; align-items: center; gap: 1rem; }
        
        .nav-link-custom {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--k-gray-50);
            border-radius: 24px;
            color: var(--k-gray-600);
            font-size: 0.7rem;
            text-decoration: none;
            border: 1px solid var(--k-gray-200);
            transition: all 0.2s;
        }
        
        .nav-link-custom:hover { background: var(--k-navy-soft); border-color: var(--k-navy); color: var(--k-navy); }
        .nav-link-custom.active { background: var(--k-navy); border-color: var(--k-navy); color: white; }
        
        .date-display { 
            display: flex; 
            align-items: center; 
            gap: 0.4rem; 
            padding: 0.3rem 0.8rem; 
            background: var(--k-gray-50); 
            border-radius: 24px; 
            color: var(--k-gray-600); 
            font-size: 0.7rem; 
            border: 1px solid var(--k-gray-200); 
        }
        
        .status-indicator { 
            display: flex; 
            align-items: center; 
            gap: 0.4rem; 
            padding: 0.3rem 0.8rem; 
            background: var(--k-gray-50); 
            border-radius: 24px; 
            font-size: 0.7rem; 
            border: 1px solid var(--k-gray-200); 
        }
        
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc2626; }
        .status-dot.connected { background: #10b981; box-shadow: 0 0 0 2px #d1fae5; }
        .status-dot.warning { background: var(--k-orange); }
        
        .badge-orange {
            background: var(--k-orange);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.6rem;
            font-weight: 600;
        }
        
        .main-container { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
        
        .k-card { 
            background: var(--k-white); 
            border-radius: 1rem; 
            border: 1px solid var(--k-gray-200); 
            box-shadow: var(--k-shadow-sm); 
            overflow: hidden; 
            transition: all 0.2s ease; 
        }
        
        .k-card:hover { box-shadow: var(--k-shadow-navy); transform: translateY(-2px); }
        
        .k-card-header { 
            padding: 1rem 1.5rem; 
            border-bottom: 1px solid var(--k-gray-200); 
            background: linear-gradient(to right, var(--k-white), var(--k-gray-50));
            position: relative;
        }
        
        .k-card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--k-orange);
            border-radius: 3px;
        }
        
        .k-card-header h3 { 
            font-size: 0.95rem; 
            font-weight: 700; 
            color: var(--k-navy); 
            margin: 0; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
        
        .k-card-body { padding: 1.5rem; }
        
        .k-btn { 
            padding: 0.6rem 1.5rem; 
            border-radius: 2rem; 
            font-weight: 600; 
            font-size: 0.75rem; 
            border: none; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            transition: all 0.2s ease; 
        }
        
        .k-btn-primary { 
            background: linear-gradient(135deg, var(--k-navy), var(--k-navy-dark));
            color: var(--k-white); 
        }
        
        .k-btn-primary:hover:not(:disabled) { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 16px rgba(30, 58, 138, 0.25);
        }
        
        .k-btn-orange {
            background: var(--k-orange);
            color: white;
        }
        
        .badge-status { 
            padding: 0.2rem 0.6rem; 
            border-radius: 1rem; 
            font-size: 0.6rem; 
            font-weight: 600; 
            display: inline-block; 
        }
        
        .badge-status.success { background: #d1fae5; color: #065f46; }
        .badge-status.error { background: #fee2e2; color: #dc2626; }
        .badge-status.warning { background: var(--k-orange-light); color: var(--k-orange-dark); }
        .badge-status.info { background: var(--k-navy-light); color: var(--k-navy); }
        
        .loading-overlay { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0, 0, 0, 0.6); 
            z-index: 9999; 
            display: none; 
            justify-content: center; 
            align-items: center; 
        }
        
        .loading-spinner { 
            background: var(--k-white); 
            padding: 2rem; 
            border-radius: 1.5rem; 
            text-align: center; 
            min-width: 320px; 
        }
        
        .modal-custom {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-custom.show { display: flex; }
        
        .modal-custom-content {
            background: var(--k-white);
            border-radius: 1rem;
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .text-muted { color: var(--k-gray-500) !important; }
        .text-danger { color: #dc2626 !important; }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="app-container">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <div style="display: flex; align-items: center;">
                    <div class="logo-icon"><i class="fas fa-paper-plane"></i></div>
                    <div>
                        <h1>KURMIGO - @yield('header_title', 'WhatsApp Blast')</h1>
                        <p>@yield('header_subtitle', 'Bulk messaging solution for business')</p>
                    </div>
                </div>
            </div>
            <div class="top-bar-right">
                <a href="{{ route('dashboard.index') }}" class="nav-link-custom {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('wa-blast.index') }}" class="nav-link-custom {{ request()->routeIs('wa-blast.*') ? 'active' : '' }}">
                    <i class="fas fa-paper-plane"></i> WA Blast
                </a>
                <a href="{{ route('wa-inbox.index') }}" class="nav-link-custom {{ request()->routeIs('wa-inbox.*') ? 'active' : '' }}">
                    <i class="fas fa-inbox"></i> Inbox
                    <span class="badge-orange" id="inbox-unread-badge">0</span>
                </a>
                <a href="{{ route('contact-groups.index') }}" class="nav-link-custom {{ request()->routeIs('contact-groups.*') ? 'active' : '' }}">
                    <i class="fas fa-address-book"></i> Contact Groups
                </a>
                <a href="{{ route('wa-schedule.index') }}" class="nav-link-custom {{ request()->routeIs('wa-schedule.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i> Schedules
                </a>
                <a href="{{ route('wa-templates.index') }}" class="nav-link-custom {{ request()->routeIs('wa-templates.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i> Templates
                </a>
                <a href="{{ route('api-clients.index') }}" class="nav-link-custom {{ request()->routeIs('api-clients.*') ? 'active' : '' }}">
                    <i class="fas fa-key"></i> API Clients
                </a>
                <div class="date-display">
                    <i class="fas fa-calendar"></i>
                    <span id="current-date"></span>
                </div>
                <div class="status-indicator">
                    <span class="status-dot" id="status-dot"></span>
                    <span id="wa-status-text">Checking connection...</span>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="loading-overlay">
            <div class="loading-spinner">
                <div class="spinner-border mb-3" style="color: var(--k-orange);"></div>
                <h6 id="loading-text" style="font-size: 0.9rem;">Processing...</h6>
                <div class="progress-container" style="height: 6px; background: var(--k-gray-200); border-radius: 3px; overflow: hidden; margin-top: 0.5rem; width: 260px;">
                    <div id="loading-progress" class="progress-fill" style="height: 100%; background: var(--k-orange); width: 0%; transition: width 0.3s ease;"></div>
                </div>
                <p id="loading-status" class="mt-2 small text-muted" style="font-size: 0.7rem;">0 / 0</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-container">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
    
    <script>
        // Global functions
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date
            const today = new Date();
            const dateElem = document.getElementById('current-date');
            if (dateElem) {
                dateElem.textContent = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            }
        });
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showToast(message, isError = false) {
            // Toast implementation
            console.log(message);
        }
    </script>
    
    @stack('scripts')
</body>
</html>

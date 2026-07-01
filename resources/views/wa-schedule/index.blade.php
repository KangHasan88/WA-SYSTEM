<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KURMIGO - Schedules</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1e293b; }
        
        /* ================= BLUE DARK + ORANGE + WHITE THEME ================= */
        :root {
            --k-white: #ffffff;
            --k-blue-dark: #1e3a8a;      /* Biru Tua Utama */
            --k-blue-darker: #172554;    /* Biru Lebih Tua */
            --k-blue-light: #dbeafe;     /* Biru Muda */
            --k-blue-soft: #eff6ff;      /* Biru Sangat Lembut */
            --k-orange: #ea580c;         /* Orange Utama */
            --k-orange-dark: #c2410c;    /* Orange Lebih Tua */
            --k-orange-light: #fed7aa;   /* Orange Muda */
            --k-orange-soft: #fff7ed;    /* Orange Lembut */
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
            --k-shadow-blue: 0 4px 12px rgba(30, 58, 138, 0.12);
            --k-shadow-orange: 0 4px 12px rgba(234, 88, 12, 0.15);
            --k-green: #10b981;
            --k-red: #ef4444;
        }
        
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
        
        .page-title h1 { font-size: 1.1rem; font-weight: 700; color: var(--k-blue-dark); margin: 0; }
        .page-title p { font-size: 0.7rem; color: var(--k-gray-500); margin: 0.1rem 0 0 0; }
        .logo-icon { width: 34px; height: 34px; background: var(--k-white); border: 1px solid var(--k-gray-200); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 0.75rem; }
        .logo-icon i { color: var(--k-blue-dark); font-size: 1rem; }
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
        
        .nav-link-custom:hover { background: var(--k-blue-soft); border-color: var(--k-blue-dark); color: var(--k-blue-dark); }
        .nav-link-custom.active { background: var(--k-blue-dark); border-color: var(--k-blue-dark); color: white; }
        
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
        
        .main-container { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
        
        .k-card { 
            background: var(--k-white); 
            border-radius: 0.75rem; 
            border: 1px solid var(--k-gray-200); 
            box-shadow: var(--k-shadow-sm); 
            overflow: hidden; 
            transition: all 0.2s ease;
        }
        
        .k-card:hover { 
            box-shadow: var(--k-shadow-blue);
        }
        
        .k-card-header { 
            padding: 1rem 1.25rem; 
            border-bottom: 1px solid var(--k-gray-200); 
            background: var(--k-white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        /* Garis bawah orange di card header */
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
            color: var(--k-blue-dark); 
            margin: 0; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
        
        .k-card-header h3 i { color: var(--k-blue-dark); font-size: 1rem; }
        
        .table-container { overflow-x: auto; }
        
        .k-table { width: 100%; border-collapse: collapse; }
        
        .k-table th { 
            text-align: left; 
            padding: 0.75rem 1rem; 
            color: var(--k-blue-dark); 
            font-size: 0.7rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            border-bottom: 2px solid var(--k-orange-light);
            background: var(--k-gray-50); 
            letter-spacing: 0.3px;
        }
        
        .k-table td { 
            padding: 0.75rem 1rem; 
            border-bottom: 1px solid var(--k-gray-100); 
            color: var(--k-gray-600); 
            font-size: 0.75rem; 
            vertical-align: middle;
        }
        
        .k-table tbody tr:hover { background: var(--k-orange-soft); }
        
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
        .badge-status.info { background: var(--k-blue-light); color: var(--k-blue-dark); }
        
        .k-btn { 
            padding: 0.3rem 0.8rem; 
            border-radius: 1.5rem; 
            font-weight: 500; 
            font-size: 0.65rem; 
            border: none; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem; 
            transition: all 0.2s; 
        }
        
        .k-btn-outline { 
            background: transparent; 
            border: 1px solid var(--k-gray-300); 
            color: var(--k-gray-600); 
        }
        
        .k-btn-outline:hover { 
            border-color: var(--k-orange); 
            color: var(--k-orange); 
            background: var(--k-orange-soft); 
            transform: translateY(-1px);
        }
        
        .k-btn-primary {
            background: var(--k-blue-dark);
            color: white;
        }
        
        .k-btn-primary:hover {
            background: var(--k-blue-darker);
            transform: translateY(-1px);
            box-shadow: var(--k-shadow-blue);
        }
        
        .k-btn-danger {
            background: transparent;
            border: 1px solid #fee2e2;
            color: #dc2626;
        }
        
        .k-btn-danger:hover {
            background: #fee2e2;
            border-color: #dc2626;
            transform: translateY(-1px);
        }
        
        .k-btn-warning {
            background: transparent;
            border: 1px solid var(--k-orange-light);
            color: var(--k-orange);
        }
        
        .k-btn-warning:hover {
            background: var(--k-orange-light);
            border-color: var(--k-orange);
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--k-gray-400);
        }
        
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state p { font-size: 0.8rem; margin: 0; }
        
        .empty-state a {
            background: var(--k-orange);
            color: white;
            padding: 0.4rem 1.2rem;
            border-radius: 1.5rem;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-block;
            margin-top: 1rem;
            transition: all 0.2s;
        }
        
        .empty-state a:hover {
            background: var(--k-orange-dark);
            transform: translateY(-1px);
        }
        
        .modal-custom {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-custom.show { display: flex; }
        
        .modal-custom-content {
            background: var(--k-white);
            border-radius: 1rem;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--k-shadow-lg);
        }
        
        .modal-custom-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--k-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--k-blue-dark);
            color: white;
            border-radius: 1rem 1rem 0 0;
        }
        
        .modal-custom-header h4 { margin: 0; font-size: 0.9rem; font-weight: 600; }
        .modal-custom-close { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; opacity: 0.8; }
        .modal-custom-close:hover { opacity: 1; }
        .modal-custom-body { padding: 1.5rem; }
        
        .detail-label { font-size: 0.65rem; font-weight: 600; color: var(--k-gray-500); margin-bottom: 0.25rem; text-transform: uppercase; }
        .detail-value { background: var(--k-gray-50); padding: 0.5rem; border-radius: 0.5rem; font-size: 0.75rem; margin-bottom: 0.75rem; word-break: break-word; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            margin: 0;
        }
        
        .pagination .page-link {
            padding: 0.3rem 0.75rem;
            font-size: 0.7rem;
            border-radius: 1.5rem;
            color: var(--k-blue-dark);
            background: var(--k-white);
            border: 1px solid var(--k-gray-200);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .pagination .page-link:hover {
            background: var(--k-orange-soft);
            border-color: var(--k-orange);
            color: var(--k-orange);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--k-orange);
            color: white;
            border-color: var(--k-orange);
        }
        
        @media (max-width: 768px) {
            .k-table th, .k-table td { padding: 0.5rem; font-size: 0.65rem; }
            .main-container { padding: 1rem; }
            .k-card-header { flex-direction: column; gap: 0.5rem; align-items: flex-start; }
        }
        
        .text-warning { color: var(--k-orange) !important; }
        .text-success { color: #10b981 !important; }
        .text-danger { color: #dc2626 !important; }
        
        .progress-container {
            height: 4px;
            background: var(--k-gray-200);
            border-radius: 2px;
            margin-top: 0.3rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 4px;
            background: var(--k-orange);
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        /* Auto-refresh indicator */
        .auto-refresh-badge {
            font-size: 0.6rem;
            color: var(--k-orange);
            margin-left: 1rem;
            background: var(--k-orange-soft);
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
        }
        
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .refreshing {
            animation: pulse 0.5s ease;
        }
        
        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: pointer;
        }
        
        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.2rem 0.5rem;
            background: var(--k-gray-800);
            color: white;
            font-size: 0.55rem;
            border-radius: 0.25rem;
            white-space: nowrap;
            display: none;
            z-index: 100;
        }
        
        [data-tooltip]:hover:before {
            display: block;
        }
        
        /* Tombol New Schedule */
        .btn-new-schedule {
            background: var(--k-orange);
            color: white;
            border: none;
        }
        
        .btn-new-schedule:hover {
            background: var(--k-orange-dark);
            transform: translateY(-1px);
            box-shadow: var(--k-shadow-orange);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="top-bar">
            <div class="page-title">
                <div style="display: flex; align-items: center;">
                    <div class="logo-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <h1>KURMIGO - Schedules</h1>
                        <p>Manage your scheduled WhatsApp messages</p>
                    </div>
                </div>
            </div>
            <div class="top-bar-right">
                <a href="{{ route('dashboard.index') }}" class="nav-link-custom">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('wa-blast.index') }}" class="nav-link-custom">
                    <i class="fas fa-paper-plane"></i> WA Blast
                </a>
                <a href="{{ route('contact-groups.index') }}" class="nav-link-custom">
                    <i class="fas fa-address-book"></i> Contact Groups
                </a>
                <div class="date-display">
                    <i class="fas fa-calendar"></i>
                    <span id="current-date"></span>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="k-card">
                <div class="k-card-header">
                    <h3>
                        <i class="fas fa-calendar-alt"></i> Scheduled Messages
                        <span class="auto-refresh-badge" id="refresh-status">
                            <i class="fas fa-sync-alt"></i> Auto refresh every 10s
                        </span>
                    </h3>
                    <a href="{{ route('wa-blast.index') }}" class="nav-link-custom btn-new-schedule">
                        <i class="fas fa-plus"></i> New Schedule
                    </a>
                </div>
                <div class="k-card-body p-0" id="schedules-content">
                    @if($schedules->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Belum ada jadwal pengiriman</p>
                            <a href="{{ route('wa-blast.index') }}">Buat Jadwal Baru</a>
                        </div>
                    @else
                        <div class="table-container">
                            <table class="k-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th>Judul</th>
                                        <th style="width: 160px;">Jadwal (WIB)</th>
                                        <th style="width: 70px;">Jumlah</th>
                                        <th style="width: 90px;">Status</th>
                                        <th style="width: 150px;">Progress</th>
                                        <th style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="schedules-tbody">
                                    @foreach($schedules as $schedule)
                                    @php
                                        $wibTime = $schedule->scheduled_at->copy()->addHours(7);
                                    @endphp
                                    <tr>
                                        <td>{{ $schedule->id }}</td>
                                        <td>
                                            <strong>{{ $schedule->title ?? '-' }}</strong>
                                            <div class="text-muted small" style="font-size: 0.6rem; margin-top: 0.2rem;">
                                                {{ Str::limit($schedule->message, 50) }}
                                            </div>
                                        </td>
                                        <td>{{ $wibTime->format('d/m/Y H:i') }}</td>
                                        <td class="text-center">{{ $schedule->total_numbers }}</td>
                                        <td>
                                            @php
                                                $badgeClass = 'info';
                                                $badgeText = 'Pending';
                                                if ($schedule->status == 'completed') { $badgeClass = 'success'; $badgeText = 'Completed'; }
                                                elseif ($schedule->status == 'processing') { $badgeClass = 'warning'; $badgeText = 'Processing'; }
                                                elseif ($schedule->status == 'paused') { $badgeClass = 'warning'; $badgeText = 'Paused'; }
                                                elseif ($schedule->status == 'cancelled') { $badgeClass = 'error'; $badgeText = 'Cancelled'; }
                                                elseif ($schedule->status == 'failed') { $badgeClass = 'error'; $badgeText = 'Failed'; }
                                            @endphp
                                            <span class="badge-status {{ $badgeClass }}">{{ $badgeText }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="small">
                                                {{ $schedule->dispatched_count ?? $schedule->sent_count }}/{{ $schedule->total_numbers }} dispatched
                                            </div>
                                            <div class="progress-container">
                                                <div class="progress-fill" style="width: {{ $schedule->total_numbers > 0 ? (($schedule->dispatched_count ?? $schedule->sent_count) / $schedule->total_numbers) * 100 : 0 }}%;"></div>
                                            </div>
                                            @if($schedule->next_dispatch_at && in_array($schedule->status, ['pending', 'processing']))
                                                <div class="text-muted small" style="font-size: 0.58rem; margin-top: 0.2rem;">
                                                    Next: {{ $schedule->next_dispatch_at->copy()->addHours(7)->format('d/m H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="k-btn k-btn-outline" onclick="viewSchedule({{ $schedule->id }})" data-tooltip="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if(in_array($schedule->status, ['pending', 'processing']))
                                                <button class="k-btn k-btn-warning" onclick="pauseSchedule({{ $schedule->id }})" data-tooltip="Pause">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <button class="k-btn k-btn-warning" onclick="cancelSchedule({{ $schedule->id }})" data-tooltip="Batalkan">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                            @if($schedule->status == 'paused')
                                                <button class="k-btn k-btn-primary" onclick="resumeSchedule({{ $schedule->id }})" data-tooltip="Resume">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button class="k-btn k-btn-warning" onclick="cancelSchedule({{ $schedule->id }})" data-tooltip="Batalkan">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                            <button class="k-btn k-btn-danger" onclick="deleteSchedule({{ $schedule->id }})" data-tooltip="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination" id="schedules-pagination">
                            {{ $schedules->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div id="detailModal" class="modal-custom">
        <div class="modal-custom-content">
            <div class="modal-custom-header">
                <h4><i class="fas fa-info-circle me-2"></i> Detail Jadwal</h4>
                <button class="modal-custom-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-custom-body" id="modal-body">
                <div class="text-center py-3">
                    <div class="spinner-border text-primary spinner-border-sm"></div> Memuat...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            document.getElementById('current-date').textContent = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        });

        // Auto refresh function - AJAX polling (tanpa reload halaman)
        let refreshInterval = null;
        let isRefreshing = false;

        function startAutoRefresh() {
            if (refreshInterval) clearInterval(refreshInterval);
            refreshInterval = setInterval(refreshSchedules, 10000); // 10 detik
        }

        async function refreshSchedules() {
            if (isRefreshing) return;
            isRefreshing = true;
            
            const refreshStatus = document.getElementById('refresh-status');
            if (refreshStatus) {
                refreshStatus.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
            }
            
            try {
                const response = await axios.get(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const html = response.data;
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update tbody
                const newTbody = doc.querySelector('#schedules-tbody');
                const currentTbody = document.querySelector('#schedules-tbody');
                if (newTbody && currentTbody) {
                    currentTbody.innerHTML = newTbody.innerHTML;
                }
                
                // Update pagination
                const newPagination = doc.querySelector('#schedules-pagination');
                const currentPagination = document.querySelector('#schedules-pagination');
                if (newPagination && currentPagination) {
                    currentPagination.innerHTML = newPagination.innerHTML;
                }
                
                // Update empty state jika perlu
                const newContent = doc.querySelector('#schedules-content');
                const currentContent = document.querySelector('#schedules-content');
                if (newContent && currentContent && newContent.innerHTML !== currentContent.innerHTML) {
                    // Jika struktur berubah (misal dari tabel ke empty state)
                    currentContent.innerHTML = newContent.innerHTML;
                }
                
                // Animasi efek refresh
                const container = document.querySelector('.k-card-body');
                if (container) {
                    container.classList.add('refreshing');
                    setTimeout(() => container.classList.remove('refreshing'), 500);
                }
                
            } catch (error) {
                console.error('Auto refresh error:', error);
            } finally {
                if (refreshStatus) {
                    refreshStatus.innerHTML = '<i class="fas fa-sync-alt"></i> Auto refresh every 10s';
                }
                isRefreshing = false;
            }
        }

        async function viewSchedule(id) {
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modal-body');
            
            modal.classList.add('show');
            modalBody.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div> Memuat...</div>';
            
            try {
                const response = await axios.get(`/wa-schedule/${id}`);
                const s = response.data;
                
                let imageHtml = '';
                if (s.image_url) {
                    imageHtml = `
                        <div>
                            <div class="detail-label"><i class="fas fa-image me-1"></i> Gambar</div>
                            <div class="detail-value"><img src="${s.image_url}" style="max-width: 100%; max-height: 150px; border-radius: 0.5rem;"></div>
                        </div>
                    `;
                }
                
                let statusBadge = '';
                if (s.status == 'pending') statusBadge = '<span class="badge-status info">Pending</span>';
                else if (s.status == 'processing') statusBadge = '<span class="badge-status warning">Processing</span>';
                else if (s.status == 'paused') statusBadge = '<span class="badge-status warning">Paused</span>';
                else if (s.status == 'completed') statusBadge = '<span class="badge-status success">Completed</span>';
                else if (s.status == 'cancelled') statusBadge = '<span class="badge-status error">Cancelled</span>';
                else statusBadge = '<span class="badge-status error">Failed</span>';
                
                const scheduledDate = new Date(s.scheduled_at);
                const wibDate = new Date(scheduledDate.getTime() + (7 * 60 * 60 * 1000));
                const formattedDate = wibDate.toLocaleString('id-ID');
                const plan = s.campaign_plan || {};
                const nextDispatch = s.next_dispatch_at ? new Date(new Date(s.next_dispatch_at).getTime() + (7 * 60 * 60 * 1000)).toLocaleString('id-ID') : '-';
                
                modalBody.innerHTML = `
                    <div>
                        <div class="detail-label"><i class="fas fa-heading me-1"></i> Judul</div>
                        <div class="detail-value">${escapeHtml(s.title) || '-'}</div>
                    </div>
                    <div>
                        <div class="detail-label"><i class="fas fa-envelope me-1"></i> Pesan</div>
                        <div class="detail-value" style="max-height: 150px; overflow-y: auto; white-space: pre-wrap;">${escapeHtml(s.message)}</div>
                    </div>
                    ${imageHtml}
                    <div>
                        <div class="detail-label"><i class="fas fa-calendar me-1"></i> Jadwal (WIB)</div>
                        <div class="detail-value">${formattedDate}</div>
                    </div>
                    <div>
                        <div class="detail-label"><i class="fas fa-tag me-1"></i> Status</div>
                        <div class="detail-value">${statusBadge}</div>
                    </div>
                    <div>
                        <div class="detail-label"><i class="fas fa-chart-line me-1"></i> Progress</div>
                        <div class="detail-value">${s.dispatched_count || s.sent_count || 0} / ${s.total_numbers} dispatch dibuat</div>
                    </div>
                    <div>
                        <div class="detail-label"><i class="fas fa-layer-group me-1"></i> Campaign Plan</div>
                        <div class="detail-value">
                            ${plan.batch_count || '-'} batch, maksimal ${plan.max_per_hour || 100} penerima/jam,
                            jeda ${plan.interval_seconds_min || 36}-${plan.interval_seconds_max || 55} detik.
                            <br>Estimasi selesai: ${plan.estimated_minutes || '-'} menit.
                        </div>
                    </div>
                    <div>
                        <div class="detail-label"><i class="fas fa-forward me-1"></i> Next Dispatch</div>
                        <div class="detail-value">${nextDispatch}</div>
                    </div>
                    <div>
                        <div class="detail-label"><i class="fas fa-clock me-1"></i> Dibuat</div>
                        <div class="detail-value">${new Date(s.created_at).toLocaleString('id-ID')}</div>
                    </div>
                `;
            } catch (error) {
                modalBody.innerHTML = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i> Gagal memuat detail</div>';
            }
        }
        
        async function cancelSchedule(id) {
            if (confirm('Batalkan jadwal ini?')) {
                try {
                    await axios.post(`/wa-schedule/${id}/cancel`);
                    alert('Jadwal dibatalkan');
                    refreshSchedules(); // Refresh tanpa reload
                } catch (error) {
                    alert('Gagal membatalkan jadwal');
                }
            }
        }

        async function pauseSchedule(id) {
            if (confirm('Pause campaign ini? Batch berikutnya tidak akan berjalan sampai di-resume.')) {
                try {
                    await axios.post(`/wa-schedule/${id}/pause`);
                    alert('Campaign di-pause');
                    refreshSchedules();
                } catch (error) {
                    alert(error.response?.data?.message || 'Gagal pause campaign');
                }
            }
        }

        async function resumeSchedule(id) {
            if (confirm('Lanjutkan campaign ini sekarang?')) {
                try {
                    await axios.post(`/wa-schedule/${id}/resume`);
                    alert('Campaign dilanjutkan');
                    refreshSchedules();
                } catch (error) {
                    alert(error.response?.data?.message || 'Gagal resume campaign');
                }
            }
        }
        
        async function deleteSchedule(id) {
            if (confirm('Hapus jadwal ini? Tindakan ini tidak dapat dibatalkan.')) {
                try {
                    await axios.delete(`/wa-schedule/${id}`);
                    alert('Jadwal dihapus');
                    refreshSchedules(); // Refresh tanpa reload
                } catch (error) {
                    alert('Gagal menghapus jadwal');
                }
            }
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        }
        
        // Start auto refresh setelah halaman selesai loading
        startAutoRefresh();
        
        // Optional: Hentikan auto refresh saat halaman tidak terlihat (menghemat resource)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                if (refreshInterval) clearInterval(refreshInterval);
                refreshInterval = null;
            } else {
                startAutoRefresh();
                refreshSchedules(); // Refresh langsung saat halaman terlihat lagi
            }
        });
    </script>
</body>
</html>

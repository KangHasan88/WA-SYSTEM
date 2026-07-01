<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KURMIGO - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { max-width: 100%; overflow-x: hidden; }
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
        .top-bar-right { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        
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
        
        .k-card:hover { box-shadow: var(--k-shadow-blue); }
        
        .k-card-header { 
            padding: 0.75rem 1.25rem; 
            border-bottom: 1px solid var(--k-gray-200); 
            background: var(--k-white);
            position: relative;
        }
        
        /* Garis bawah orange di card header */
        .k-card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--k-orange);
            border-radius: 2px;
        }
        
        .k-card-header h3 { 
            font-size: 0.85rem; 
            font-weight: 600; 
            color: var(--k-blue-dark); 
            margin: 0; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
        
        .k-card-header h3 i { color: var(--k-blue-dark); }
        
        .k-card-body { padding: 1.25rem; }
        
        /* Stats Cards */
        .stat-card {
            background: var(--k-white);
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid var(--k-gray-200);
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--k-shadow-md);
            border-color: var(--k-orange-light);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--k-gray-800);
        }
        
        .stat-label {
            font-size: 0.7rem;
            color: var(--k-gray-500);
            margin-top: 0.25rem;
        }
        
        .stat-trend {
            font-size: 0.65rem;
            margin-top: 0.5rem;
        }
        
        .stat-trend.up { color: var(--k-green); }
        .stat-trend.down { color: var(--k-red); }
        
        .row-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .row-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .k-table {
            width: 100%;
            font-size: 0.7rem;
        }
        
        .k-table th {
            text-align: left;
            padding: 0.5rem;
            color: var(--k-blue-dark);
            font-weight: 600;
            border-bottom: 2px solid var(--k-orange-light);
        }
        
        .k-table td {
            padding: 0.5rem;
            border-bottom: 1px solid var(--k-gray-100);
            color: var(--k-gray-600);
        }
        
        .k-table tbody tr:hover {
            background: var(--k-orange-soft);
        }
        
        .badge-status {
            padding: 0.15rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.55rem;
            font-weight: 600;
        }
        
        .badge-status.success { background: #d1fae5; color: #065f46; }
        .badge-status.error { background: #fee2e2; color: #dc2626; }
        .badge-status.warning { background: var(--k-orange-light); color: var(--k-orange-dark); }
        .badge-status.info { background: var(--k-blue-light); color: var(--k-blue-dark); }
        
        @media (max-width: 992px) {
            .row-stats { grid-template-columns: repeat(2, 1fr); }
            .row-charts { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 1200px) {
            .top-bar {
                align-items: flex-start;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .top-bar-right {
                width: 100%;
                gap: 0.5rem;
                overflow-x: auto;
                padding-bottom: 0.25rem;
                scrollbar-width: thin;
            }

            .nav-link-custom,
            .date-display {
                flex: 0 0 auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 576px) {
            .row-stats { grid-template-columns: 1fr; }
            .top-bar {
                padding: 0.8rem 1rem;
                position: relative;
            }

            .page-title {
                width: 100%;
            }

            .page-title h1 {
                font-size: 1rem;
                line-height: 1.2;
            }

            .page-title p {
                max-width: 260px;
                line-height: 1.35;
            }

            .top-bar-right {
                margin: 0 -1rem;
                padding: 0 1rem 0.35rem;
            }

            .main-container {
                padding: 1rem;
            }

            .k-card-body {
                padding: 1rem;
            }

            .stat-card:hover {
                transform: none;
            }
        }
        
        canvas { max-height: 300px; }
        
        /* Orange accent untuk tombol dan link */
        .link-orange {
            color: var(--k-orange);
            text-decoration: none;
        }
        
        .link-orange:hover {
            color: var(--k-orange-dark);
            text-decoration: underline;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--k-gray-100);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--k-gray-400);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--k-orange);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="top-bar">
            <div class="page-title">
                <div style="display: flex; align-items: center;">
                    <div class="logo-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <h1>KURMIGO - Dashboard</h1>
                        <p>Analytics & Statistics</p>
                    </div>
                </div>
            </div>
            <div class="top-bar-right">
                <a href="{{ route('wa-blast.index') }}" class="nav-link-custom">
                    <i class="fas fa-paper-plane"></i> WA Blast
                </a>
                <a href="{{ route('contact-groups.index') }}" class="nav-link-custom">
                    <i class="fas fa-address-book"></i> Contact Groups
                </a>
                <a href="{{ route('wa-schedule.index') }}" class="nav-link-custom">
                    <i class="fas fa-calendar-alt"></i> Schedules
                </a>
                <div class="date-display">
                    <i class="fas fa-calendar"></i>
                    <span id="current-date"></span>
                </div>
            </div>
        </div>

        <div class="main-container">
            <!-- Stats Cards -->
            <div class="row-stats">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($totalMessages) }}</div>
                            <div class="stat-label">Total Pesan Terkirim</div>
                        </div>
                        <div class="stat-icon" style="background: var(--k-blue-soft); color: var(--k-blue-dark);">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-check-circle"></i> {{ $successRate }}% Success Rate
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($successMessages) }}</div>
                            <div class="stat-label">Berhasil</div>
                        </div>
                        <div class="stat-icon" style="background: #d1fae5; color: #065f46;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-chart-line"></i> {{ $totalMessages > 0 ? round(($successMessages / $totalMessages) * 100, 1) : 0 }}% dari total
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($failedMessages) }}</div>
                            <div class="stat-label">Gagal</div>
                        </div>
                        <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-trend down">
                        <i class="fas fa-chart-line"></i> {{ $totalMessages > 0 ? round(($failedMessages / $totalMessages) * 100, 1) : 0 }}% dari total
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($totalContacts) }}</div>
                            <div class="stat-label">Total Kontak</div>
                        </div>
                        <div class="stat-icon" style="background: var(--k-orange-soft); color: var(--k-orange);">
                            <i class="fas fa-address-book"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-layer-group"></i> {{ $totalGroups }} Groups
                    </div>
                </div>
            </div>
            
            <!-- Additional Stats Row -->
            <div class="row-stats">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($todayMessages) }}</div>
                            <div class="stat-label">Hari Ini</div>
                        </div>
                        <div class="stat-icon" style="background: var(--k-blue-soft); color: var(--k-blue-dark);">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-check-circle"></i> {{ $todaySuccess }} berhasil
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($weekMessages) }}</div>
                            <div class="stat-label">Minggu Ini</div>
                        </div>
                        <div class="stat-icon" style="background: var(--k-blue-soft); color: var(--k-blue-dark);">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($monthMessages) }}</div>
                            <div class="stat-label">Bulan Ini</div>
                        </div>
                        <div class="stat-icon" style="background: var(--k-blue-soft); color: var(--k-blue-dark);">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value">{{ number_format($pendingSchedules) }}</div>
                            <div class="stat-label">Jadwal Tertunda</div>
                        </div>
                        <div class="stat-icon" style="background: var(--k-orange-soft); color: var(--k-orange);">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row-charts">
                <div class="k-card">
                    <div class="k-card-header">
                        <h3><i class="fas fa-chart-line"></i> 7 Hari Terakhir</h3>
                    </div>
                    <div class="k-card-body">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
                
                <div class="k-card">
                    <div class="k-card-header">
                        <h3><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
                    </div>
                    <div class="k-card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Logs -->
            <div class="k-card">
                <div class="k-card-header">
                    <h3><i class="fas fa-history"></i> Pengiriman Terbaru</h3>
                </div>
                <div class="k-card-body p-0">
                    <div style="overflow-x: auto;">
                        <table class="k-table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Nomor</th>
                                    <th>Judul</th>
                                    <th>Status</th>
                                </thead>
                            <tbody>
                                @forelse($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m H:i') }}</td>
                                    <td><code>{{ $log->number }}</code></td>
                                    <td>{{ Str::limit($log->title ?? '-', 40) }}</td>
                                    <td>
                                        @if($log->status == 'success')
                                            <span class="badge-status success">Berhasil</span>
                                        @elseif($log->status == 'error')
                                            <span class="badge-status error">Gagal</span>
                                        @elseif($log->status == 'invalid')
                                            <span class="badge-status warning">Invalid</span>
                                        @else
                                            <span class="badge-status info">{{ $log->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada data pengiriman</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            document.getElementById('current-date').textContent = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            
            // Weekly Chart
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels']) !!},
                    datasets: [
                        {
                            label: 'Total Pesan',
                            data: {!! json_encode($chartData['total']) !!},
                            borderColor: '#1e3a8a',
                            backgroundColor: 'rgba(30, 58, 138, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Berhasil',
                            data: {!! json_encode($chartData['success']) !!},
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
            
            // Status Distribution Chart - dengan warna orange untuk pending
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Berhasil', 'Gagal', 'Invalid', 'Pending'],
                    datasets: [{
                        data: [
                            {{ $statusDistribution['success'] }},
                            {{ $statusDistribution['error'] }},
                            {{ $statusDistribution['invalid'] }},
                            {{ $statusDistribution['pending'] }}
                        ],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#ea580c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>
</body>
</html>

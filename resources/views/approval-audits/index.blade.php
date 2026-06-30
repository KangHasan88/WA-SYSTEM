@extends('layouts.app')

@section('title', 'Approval Audit')
@section('header_title', 'Approval Audit')
@section('header_subtitle', 'Review keputusan approval via WhatsApp')

@push('styles')
<style>
    .audit-summary {
        display: grid;
        grid-template-columns: repeat(5, minmax(120px, 1fr));
        gap: 0.8rem;
        margin-bottom: 1rem;
    }
    .summary-tile {
        background: var(--k-white);
        border: 1px solid var(--k-gray-200);
        border-radius: 0.85rem;
        padding: 0.9rem 1rem;
        box-shadow: var(--k-shadow-sm);
    }
    .summary-label {
        font-size: 0.68rem;
        color: var(--k-gray-500);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .summary-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--k-navy);
        line-height: 1.1;
    }
    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 160px 150px 150px 150px auto;
        gap: 0.65rem;
        align-items: end;
    }
    .form-label { font-size: 0.72rem; font-weight: 700; color: var(--k-gray-700); }
    .form-control, .form-select {
        border: 1px solid var(--k-gray-200);
        border-radius: 0.65rem;
        font-size: 0.78rem;
        padding: 0.58rem 0.75rem;
    }
    .audit-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
    }
    .audit-table th {
        text-transform: uppercase;
        letter-spacing: 0;
        font-size: 0.66rem;
        color: var(--k-gray-500);
        background: var(--k-gray-50);
        padding: 0.75rem;
        border-bottom: 1px solid var(--k-gray-200);
        white-space: nowrap;
    }
    .audit-table td {
        padding: 0.78rem 0.75rem;
        border-bottom: 1px solid var(--k-gray-100);
        vertical-align: top;
    }
    .mono {
        font-family: Consolas, monospace;
        font-size: 0.72rem;
    }
    .action-text {
        max-width: 360px;
        color: var(--k-gray-700);
        line-height: 1.35;
    }
    .risk-low { background: #d1fae5; color: #065f46; }
    .risk-medium { background: #fef3c7; color: #92400e; }
    .risk-high { background: #fee2e2; color: #991b1b; }
    .payload-note {
        font-size: 0.68rem;
        color: var(--k-gray-500);
        margin-top: 0.25rem;
    }
    @media (max-width: 1100px) {
        .audit-summary { grid-template-columns: repeat(2, 1fr); }
        .filter-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')
<div class="audit-summary">
    @foreach($summary as $label => $value)
        <div class="summary-tile">
            <div class="summary-label">{{ str_replace('_', ' ', $label) }}</div>
            <div class="summary-value">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="k-card mb-3">
    <div class="k-card-header">
        <h3><i class="fas fa-filter"></i> Filter Audit</h3>
    </div>
    <div class="k-card-body">
        <form method="GET" class="filter-grid">
            <div>
                <label class="form-label">Source</label>
                <input type="text" name="source" class="form-control" value="{{ $filters['source'] }}" placeholder="codex, dms, hris">
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach(['all' => 'Semua', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'expired' => 'Expired'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Risk</label>
                <select name="risk" class="form-select">
                    @foreach(['all' => 'Semua', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['risk'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div>
                <label class="form-label">Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div>
                <button class="k-btn k-btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="k-card">
    <div class="k-card-header">
        <h3><i class="fas fa-clipboard-check"></i> Riwayat Approval</h3>
    </div>
    <div class="table-responsive">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Approval</th>
                    <th>Source</th>
                    <th>Action</th>
                    <th>Risk</th>
                    <th>Status</th>
                    <th>Nomor</th>
                    <th>Keputusan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvals as $approval)
                    <tr>
                        <td>
                            <div>{{ optional($approval->created_at)->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                            <div class="text-muted">{{ optional($approval->created_at)->timezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                        </td>
                        <td>
                            <div class="mono">{{ $approval->approval_id }}</div>
                            <div class="text-muted mono">Kode: {{ $approval->code }}</div>
                        </td>
                        <td>{{ $approval->source ?: '-' }}</td>
                        <td>
                            <div class="action-text">{{ $approval->action }}</div>
                            @if($approval->payload)
                                <div class="payload-note"><i class="fas fa-lock"></i> Payload disembunyikan</div>
                            @endif
                        </td>
                        <td><span class="badge-status risk-{{ $approval->risk }}">{{ strtoupper($approval->risk) }}</span></td>
                        <td><span class="badge-status {{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'error' : 'warning') }}">{{ strtoupper($approval->status) }}</span></td>
                        <td class="mono">{{ substr($approval->to_number, 0, 4) . str_repeat('*', max(0, strlen($approval->to_number) - 7)) . substr($approval->to_number, -3) }}</td>
                        <td>
                            @if($approval->approved_at)
                                <div>Approved</div>
                                <div class="text-muted">{{ $approval->approved_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</div>
                            @elseif($approval->rejected_at)
                                <div>Rejected</div>
                                <div class="text-muted">{{ $approval->rejected_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</div>
                            @else
                                <div class="text-muted">Expired: {{ optional($approval->expires_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada data approval sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">
        {{ $approvals->links() }}
    </div>
</div>
@endsection

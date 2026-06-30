@extends('layouts.app')

@section('title', 'Delivery Report')
@section('header_title', 'Delivery Report')
@section('header_subtitle', 'Pantau status kirim dan retry pesan gagal')

@push('styles')
<style>
    .report-summary {
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
        grid-template-columns: 150px 1fr 1fr 150px 150px auto;
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
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.77rem;
    }
    .report-table th {
        text-transform: uppercase;
        letter-spacing: 0;
        font-size: 0.65rem;
        color: var(--k-gray-500);
        background: var(--k-gray-50);
        padding: 0.72rem;
        border-bottom: 1px solid var(--k-gray-200);
        white-space: nowrap;
    }
    .report-table td {
        padding: 0.76rem 0.72rem;
        border-bottom: 1px solid var(--k-gray-100);
        vertical-align: top;
    }
    .mono { font-family: Consolas, monospace; font-size: 0.72rem; }
    .message-preview {
        max-width: 340px;
        line-height: 1.35;
        color: var(--k-gray-700);
    }
    .error-box {
        max-width: 320px;
        max-height: 90px;
        overflow: auto;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 0.55rem;
        padding: 0.5rem;
        color: #9a3412;
        font-size: 0.68rem;
        white-space: pre-wrap;
    }
    @media (max-width: 1100px) {
        .report-summary { grid-template-columns: repeat(2, 1fr); }
        .filter-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
@endif

<div class="report-summary">
    @foreach($summary as $label => $value)
        <div class="summary-tile">
            <div class="summary-label">{{ $label }}</div>
            <div class="summary-value">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="k-card mb-3">
    <div class="k-card-header">
        <h3><i class="fas fa-filter"></i> Filter Delivery</h3>
    </div>
    <div class="k-card-body">
        <form method="GET" class="filter-grid">
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach(['all' => 'Semua', 'queued' => 'Queued', 'sending' => 'Sending', 'success' => 'Success', 'failed' => 'Failed', 'invalid' => 'Invalid'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Source</label>
                <input type="text" name="source" class="form-control" value="{{ $filters['source'] }}" placeholder="codex, dms, hris">
            </div>
            <div>
                <label class="form-label">Nomor</label>
                <input type="text" name="number" class="form-control" value="{{ $filters['number'] }}" placeholder="628...">
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
        <h3><i class="fas fa-list-check"></i> Riwayat Delivery</h3>
    </div>
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Message ID</th>
                    <th>Nomor</th>
                    <th>Source</th>
                    <th>Pesan</th>
                    <th>Status API</th>
                    <th>Status WA</th>
                    <th>Error Terakhir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                    @php
                        $waLog = $message->latest_wa_log;
                        $response = $waLog?->response ? json_decode($waLog->response, true) : null;
                        $errorText = is_array($response)
                            ? ($response['error'] ?? $response['message'] ?? null)
                            : $waLog?->response;
                    @endphp
                    <tr>
                        <td>
                            <div>{{ optional($message->created_at)->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                            <div class="text-muted">{{ optional($message->created_at)->timezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                        </td>
                        <td>
                            <div class="mono">{{ $message->message_id }}</div>
                            <div class="text-muted">{{ $message->reference_type ?: '-' }} {{ $message->reference_id ?: '' }}</div>
                        </td>
                        <td class="mono">{{ $message->to_number }}</td>
                        <td>
                            <div>{{ $message->source ?: '-' }}</div>
                            <div class="text-muted">{{ $message->apiClient?->slug ?: '-' }}</div>
                        </td>
                        <td>
                            <div class="message-preview">{{ \Illuminate\Support\Str::limit($message->message, 130) }}</div>
                            @if($message->title)
                                <div class="text-muted">Judul: {{ $message->title }}</div>
                            @endif
                        </td>
                        <td><span class="badge-status {{ $message->status === 'success' ? 'success' : (in_array($message->status, ['failed', 'invalid'], true) ? 'error' : 'warning') }}">{{ strtoupper($message->status) }}</span></td>
                        <td>
                            @if($waLog)
                                <span class="badge-status {{ $waLog->status === 'success' ? 'success' : 'error' }}">{{ strtoupper($waLog->status) }}</span>
                                <div class="text-muted">Log #{{ $waLog->id }}</div>
                            @else
                                <span class="text-muted">Belum ada log</span>
                            @endif
                        </td>
                        <td>
                            @if($errorText && $waLog?->status !== 'success')
                                <div class="error-box">{{ $errorText }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if(in_array($message->status, ['failed', 'invalid'], true))
                                <form method="POST" action="{{ route('delivery-reports.retry', $message) }}" onsubmit="return confirm('Retry pesan ini lewat rate limiter?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada data sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">
        {{ $messages->links() }}
    </div>
</div>
@endsection

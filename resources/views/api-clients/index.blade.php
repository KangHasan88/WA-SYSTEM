@extends('layouts.app')

@section('title', 'API Clients')
@section('header_title', 'API Client Management')
@section('header_subtitle', 'Kelola akses modul ke WA Gateway')

@push('styles')
<style>
    .api-grid {
        display: grid;
        grid-template-columns: minmax(320px, 0.9fr) minmax(520px, 1.6fr);
        gap: 1rem;
        align-items: start;
    }
    .form-label { font-size: 0.75rem; font-weight: 700; color: var(--k-gray-700); }
    .form-control, .form-select {
        border: 1px solid var(--k-gray-200);
        border-radius: 0.7rem;
        font-size: 0.8rem;
        padding: 0.65rem 0.8rem;
    }
    .scope-list {
        display: grid;
        gap: 0.45rem;
    }
    .scope-option {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid var(--k-gray-200);
        border-radius: 0.7rem;
        padding: 0.55rem 0.65rem;
        font-size: 0.75rem;
        background: var(--k-gray-50);
    }
    .api-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
    }
    .api-table th {
        text-transform: uppercase;
        letter-spacing: 0;
        font-size: 0.67rem;
        color: var(--k-gray-500);
        background: var(--k-gray-50);
        padding: 0.75rem;
        border-bottom: 1px solid var(--k-gray-200);
    }
    .api-table td {
        padding: 0.75rem;
        border-bottom: 1px solid var(--k-gray-100);
        vertical-align: middle;
    }
    .scope-badge {
        display: inline-flex;
        margin: 0.12rem;
        padding: 0.18rem 0.45rem;
        border-radius: 1rem;
        background: var(--k-navy-soft);
        color: var(--k-navy);
        font-size: 0.65rem;
        font-weight: 700;
    }
    .token-box {
        font-family: Consolas, monospace;
        font-size: 0.78rem;
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.9rem;
        word-break: break-all;
    }
    .action-row {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }
    @media (max-width: 980px) {
        .api-grid { grid-template-columns: 1fr; }
        .top-bar-right { flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif

@if($newToken)
    <div class="alert alert-warning border-0 shadow-sm">
        <div class="fw-bold mb-2"><i class="fas fa-key me-1"></i> Token baru untuk {{ $newTokenSlug }}</div>
        <div class="token-box">{{ $newToken }}</div>
        <div class="small mt-2">Token ini hanya tampil sekali. Simpan ke secret/env modul terkait.</div>
    </div>
@endif

<div class="api-grid">
    <div class="k-card">
        <div class="k-card-header">
            <h3><i class="fas fa-plus-circle"></i> Client Baru</h3>
        </div>
        <div class="k-card-body">
            <form method="POST" action="{{ route('api-clients.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Kurmigo DMS" required>
                    @error('name') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="dms" required>
                    @error('slug') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Rate Limit Per Menit</label>
                    <input type="number" name="rate_limit_per_minute" class="form-control" value="{{ old('rate_limit_per_minute', 60) }}" min="1" max="600" required>
                    @error('rate_limit_per_minute') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Scopes</label>
                    <div class="scope-list">
                        @foreach(['read:status', 'send:message', 'approval:request', 'approval:read', '*'] as $scope)
                            <label class="scope-option">
                                <input type="checkbox" name="scopes[]" value="{{ $scope }}" @checked(in_array($scope, old('scopes', ['read:status']), true))>
                                <span>{{ $scope }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('scopes') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <button class="k-btn k-btn-primary w-100 justify-content-center" type="submit">
                    <i class="fas fa-save"></i> Buat Client
                </button>
            </form>
        </div>
    </div>

    <div class="k-card">
        <div class="k-card-header d-flex justify-content-between align-items-center">
            <h3><i class="fas fa-shield-alt"></i> API Clients</h3>
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" @selected($status === 'all')>Semua</option>
                    <option value="active" @selected($status === 'active')>Aktif</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="api-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Scopes</th>
                        <th>Rate</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $client->name }}</div>
                                <div class="text-muted">{{ $client->slug }}</div>
                            </td>
                            <td>
                                @foreach(($client->scopes ?: []) as $scope)
                                    <span class="scope-badge">{{ $scope }}</span>
                                @endforeach
                            </td>
                            <td>{{ $client->rate_limit_per_minute }}/menit</td>
                            <td>
                                <span class="badge-status {{ $client->is_active ? 'success' : 'error' }}">
                                    {{ $client->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>{{ $client->last_used_at ? $client->last_used_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}</td>
                            <td>
                                <div class="action-row">
                                    <form method="POST" action="{{ route('api-clients.toggle', $client) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $client->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            <i class="fas {{ $client->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('api-clients.rotate', $client) }}" onsubmit="return confirm('Rotate token client ini? Token lama langsung tidak berlaku.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada API client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Templates')
@section('header_title', 'Templates')
@section('header_subtitle', 'Kelola template pesan WhatsApp')

@push('styles')
<style>
    .template-grid {
        display: grid;
        grid-template-columns: minmax(300px, 420px) 1fr;
        gap: 1rem;
    }

    .template-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
    }

    .template-table th {
        text-transform: uppercase;
        letter-spacing: 0;
        font-size: 0.67rem;
        color: var(--k-navy);
        background: var(--k-gray-50);
        padding: 0.75rem;
        border-bottom: 1px solid var(--k-gray-200);
    }

    .template-table td {
        padding: 0.8rem 0.75rem;
        border-bottom: 1px solid var(--k-gray-100);
        vertical-align: top;
    }

    .template-message {
        max-width: 360px;
        color: var(--k-gray-600);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .scope-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 1rem;
        background: var(--k-navy-soft);
        color: var(--k-navy);
        font-size: 0.65rem;
        font-weight: 700;
    }

    @media (max-width: 992px) {
        .template-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="template-grid">
    <div class="k-card">
        <div class="k-card-header">
            <h3><i class="fas fa-file-circle-plus"></i> Template Baru</h3>
        </div>
        <div class="k-card-body">
            <form method="POST" action="{{ route('wa-templates.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" maxlength="100" placeholder="Contoh: Promo Follow Up" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" class="form-control" maxlength="100" placeholder="Opsional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="category" class="form-control" maxlength="50" placeholder="promo, follow-up, approval">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pesan</label>
                    <textarea name="message" class="form-control" rows="7" placeholder="Tulis isi template..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL</label>
                    <input type="text" name="image_url" class="form-control" maxlength="255" placeholder="Opsional">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="template-active" checked>
                    <label class="form-check-label" for="template-active">Aktif</label>
                </div>
                <button class="k-btn k-btn-primary w-100 justify-content-center" type="submit">
                    <i class="fas fa-save"></i> Simpan Template
                </button>
            </form>
        </div>
    </div>

    <div class="k-card">
        <div class="k-card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <h3><i class="fas fa-file-lines"></i> Daftar Template</h3>
            <form method="GET" class="d-flex gap-2">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="template-table">
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Pesan</th>
                        <th>Status</th>
                        <th>Dipakai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <strong>{{ $template->name }}</strong>
                                <div class="text-muted small">{{ $template->title ?: 'Tanpa judul' }}</div>
                                @if($template->category)
                                    <span class="scope-pill mt-2">{{ $template->category }}</span>
                                @endif
                            </td>
                            <td><div class="template-message">{{ $template->message }}</div></td>
                            <td>
                                <span class="badge-status {{ $template->is_active ? 'success' : 'warning' }}">
                                    {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>{{ number_format($template->usage_count) }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form method="POST" action="{{ route('wa-templates.toggle', $template->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary" type="submit" title="Toggle status">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('wa-templates.destroy', $template->id) }}" onsubmit="return confirm('Hapus template ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-file-lines fa-2x mb-3 d-block"></i>
                                Belum ada template pesan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $templates->links() }}
        </div>
    </div>
</div>
@endsection

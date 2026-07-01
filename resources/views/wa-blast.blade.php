<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KURMIGO - WA Blast</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at top right, rgba(18, 59, 122, .08), transparent 34rem),
                linear-gradient(180deg, #f8fbff 0%, #f6f8fb 100%);
            color: #1e293b;
        }

        :root {
            --k-white: #ffffff;
            --k-navy: #071a3d;
            --k-navy-dark: #061532;
            --k-navy-light: #123b7a;
            --k-navy-soft: #eaf2ff;
            --k-accent: #123b7a;
            --k-accent-light: #2b5ca8;
            --k-accent-soft: #eef5ff;
            --k-gray-50: #f8fafc;
            --k-gray-100: #f1f5f9;
            --k-gray-200: #e2e8f0;
            --k-gray-300: #cbd5e1;
            --k-gray-400: #94a3b8;
            --k-gray-500: #64748b;
            --k-gray-600: #475569;
            --k-gray-700: #334155;
            --k-gray-800: #1e293b;
            --k-line: #d8e2ee;
            --k-soft-line: #e3ebf5;
            --k-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --k-shadow-md: 0 8px 18px rgba(15, 23, 42, 0.08);
            --k-shadow-lg: 0 18px 42px rgba(15, 23, 42, 0.12);
            --k-shadow-navy: 0 14px 32px rgba(15, 23, 42, 0.06);
            --k-green: #10b981;
            --k-red: #ef4444;
            --k-orange: var(--k-accent);
            --k-orange-dark: var(--k-navy);
            --k-orange-light: #d8e5f4;
            --k-orange-soft: var(--k-accent-soft);
            --k-purple: #8b5cf6;
            --k-purple-dark: #6d28d9;
        }

        .app-container { min-height: 100vh; background: transparent; }

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
            border-radius: 0.5rem;
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--k-shadow-lg);
            animation: modalFadeIn 0.2s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-custom-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--k-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--k-navy);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-custom-header h4 { margin: 0; font-size: 1rem; font-weight: 600; }
        .modal-custom-close { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; opacity: 0.8; transition: opacity 0.2s; }
        .modal-custom-close:hover { opacity: 1; }
        .modal-custom-body { padding: 1.5rem; }

        .detail-item { margin-bottom: 1rem; }
        .detail-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--k-gray-500); margin-bottom: 0.25rem; }
        .detail-value { font-size: 0.85rem; color: var(--k-gray-800); word-break: break-word; background: var(--k-gray-50); padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--k-gray-200); }
        .detail-value-message { font-size: 0.85rem; color: var(--k-gray-800); word-break: break-word; background: var(--k-gray-50); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--k-gray-200); max-height: 200px; overflow-y: auto; white-space: pre-wrap; }
        .detail-value-image { padding: 0; text-align: center; background: var(--k-gray-100); }
        .detail-value-image img { max-width: 100%; max-height: 250px; border-radius: 0.5rem; object-fit: contain; }

        .top-bar {
            background: var(--k-white);
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--k-line);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
        }

        .page-title h1 { font-size: 1.1rem; font-weight: 800; color: var(--k-navy); margin: 0; letter-spacing: 0; }
        .page-title p { font-size: 0.7rem; color: var(--k-gray-500); margin: 0.1rem 0 0 0; }
        .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--k-navy), var(--k-accent)); border: 0; border-radius: 0.5rem; display: inline-flex; align-items: center; justify-content: center; margin-right: 0.75rem; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12); }
        .logo-icon i { color: white; font-size: 0.95rem; }
        .top-bar-right { display: flex; align-items: center; gap: 1rem; }

        .nav-link-custom {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--k-gray-50);
            border-radius: 0.5rem;
            color: var(--k-gray-600);
            font-size: 0.7rem;
            text-decoration: none;
            border: 1px solid var(--k-gray-200);
            transition: all 0.2s;
        }

        .nav-link-custom:hover { background: var(--k-navy-soft); border-color: #d8e5f4; color: var(--k-accent); }
        .nav-link-custom.active { background: linear-gradient(135deg, var(--k-navy), var(--k-accent)); border-color: var(--k-accent); color: white; }

        .date-display {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            background: var(--k-gray-50);
            border-radius: 0.5rem;
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
            border-radius: 0.5rem;
            font-size: 0.7rem;
            border: 1px solid var(--k-gray-200);
        }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc2626; }
        .status-dot.connected { background: #10b981; box-shadow: 0 0 0 2px #d1fae5; }
        .status-dot.warning { background: var(--k-orange); }

        .main-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1.15rem 1.5rem 1.5rem;
        }

        .k-card {
            background: var(--k-white);
            border-radius: 0.5rem;
            border: 1px solid var(--k-line);
            box-shadow: var(--k-shadow-navy);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .k-card:hover { border-color: #bfd4f3; box-shadow: var(--k-shadow-md); }

        .k-card-header {
            padding: 1rem 1.4rem;
            border-bottom: 1px solid var(--k-soft-line);
            background: var(--k-white);
            position: relative;
        }

        .k-card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 68px;
            height: 3px;
            background: linear-gradient(90deg, var(--k-navy), var(--k-accent));
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

        .k-card-header h3 i { color: var(--k-accent); font-size: 1.05rem; }
        .k-card-body { padding: 1.35rem; }

        .form-group { margin-bottom: 1.1rem; }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--k-gray-700);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
        }

        .form-label i { color: var(--k-accent); margin-right: 0.4rem; width: 1rem; }

        .form-control, .form-select {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: var(--k-white);
            border: 1px solid var(--k-soft-line);
            border-radius: 0.5rem;
            color: var(--k-gray-800);
            font-size: 0.85rem;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--k-accent);
            box-shadow: 0 0 0 3px var(--k-navy-soft);
        }

        .form-control::placeholder { color: var(--k-gray-400); }
        textarea.form-control { resize: vertical; min-height: 104px; }

        .text-xs { font-size: 0.65rem; line-height: 1.3; }

        .input-method-tab {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--k-soft-line);
        }

        .method-tab {
            padding: 0.5rem 1.25rem;
            cursor: pointer;
            border-radius: 0.5rem;
            transition: all 0.2s;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--k-gray-500);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .method-tab i { font-size: 0.8rem; }

        .method-tab.active {
            background: var(--k-navy);
            color: white;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .method-tab:not(.active):hover {
            background: var(--k-navy-soft);
            color: var(--k-navy);
        }

        .upload-area {
            border: 1px dashed var(--k-gray-300);
            border-radius: 0.5rem;
            padding: 1.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--k-gray-50);
        }

        .upload-area:hover {
            background: var(--k-navy-soft);
            border-color: var(--k-accent);
        }

        .upload-area i { color: var(--k-accent); font-size: 2.25rem; margin-bottom: 0.75rem; }
        .upload-area:hover i { color: var(--k-navy); }

        .upload-area p { font-size: 0.8rem; margin: 0; color: var(--k-gray-600); }
        .upload-area small { font-size: 0.65rem; color: var(--k-gray-500); }

        .preview-numbers {
            max-height: 180px;
            overflow-y: auto;
            background: var(--k-gray-50);
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.7rem;
            border: 1px solid var(--k-gray-200);
        }

        .k-btn {
            padding: 0.55rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .k-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

        .k-btn-primary {
            background: linear-gradient(135deg, var(--k-navy), var(--k-navy-dark));
            color: var(--k-white);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
        }

        .k-btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
        }

        .k-btn-orange {
            background: linear-gradient(135deg, var(--k-navy), var(--k-accent));
            color: white;
            box-shadow: none;
        }

        .k-btn-orange:hover:not(:disabled) {
            transform: translateY(-1px);
            background: linear-gradient(135deg, var(--k-navy-dark), var(--k-accent));
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
        }

        .k-btn-outline {
            background: transparent;
            border: 1px solid var(--k-gray-300);
            color: var(--k-gray-600);
        }

        .k-btn-outline:hover:not(:disabled) {
            border-color: #d8e5f4;
            color: var(--k-accent);
            background: var(--k-navy-soft);
        }

        .k-btn-sm { padding: 0.35rem 1rem; font-size: 0.7rem; }

        .badge-count {
            background: var(--k-gray-100);
            color: var(--k-gray-600);
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-count.success { background: #d1fae5; color: #065f46; }

        .badge-status {
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-status.success { background: #d1fae5; color: #065f46; }
        .badge-status.error { background: #fee2e2; color: #dc2626; }
        .badge-status.warning { background: #fef3c7; color: #b45309; }
        .badge-status.info { background: var(--k-navy-light); color: var(--k-navy); }

        .table-container {
            position: relative;
            max-height: 450px;
            overflow-y: auto;
            overflow-x: auto;
        }

        .k-table { width: 100%; border-collapse: collapse; min-width: 600px; }

        .k-table thead { position: sticky; top: 0; z-index: 10; background: var(--k-white); }

        .k-table th {
            text-align: left;
            padding: 0.78rem 1rem;
            color: var(--k-navy);
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            border-bottom: 1px solid var(--k-soft-line);
            background: #f8fbff;
            letter-spacing: 0;
        }

        .k-table td {
            padding: 0.72rem 1rem;
            border-bottom: 1px solid var(--k-gray-100);
            color: var(--k-gray-700);
            font-size: 0.75rem;
        }

        /* Warna khusus untuk kolom Nomor (kolom ke-2) - BIRU TUA */
        .k-table td:nth-child(2) {
            color: var(--k-navy);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .k-table td:nth-child(2) code {
            color: var(--k-navy);
            font-weight: 600;
            background: transparent;
            padding: 0;
        }

        .k-table tbody tr { cursor: pointer; transition: background 0.2s, box-shadow 0.2s; }
        .k-table tbody tr:hover { background: #f8fbff; box-shadow: inset 3px 0 0 var(--k-accent); }

        .filter-card {
            background: linear-gradient(180deg, #fbfdff 0%, #f8fbff 100%);
            border-radius: 0.5rem;
            padding: 0.9rem;
            margin-bottom: 0;
            border: 1px solid var(--k-soft-line);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.52);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            background: var(--k-white);
            padding: 2rem;
            border-radius: 0.5rem;
            text-align: center;
            min-width: 320px;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-lg);
        }

        .loading-spinner .spinner-border { color: var(--k-accent); width: 2.5rem; height: 2.5rem; }
        .loading-spinner h6 { font-size: 0.9rem; margin-top: 1rem; }

        .progress-container {
            height: 6px;
            background: var(--k-gray-200);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--k-navy), var(--k-accent));
            width: 0%;
            transition: width 0.3s ease;
        }

        .qr-container {
            margin-top: 1.5rem;
            border: 1px solid var(--k-gray-200);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .qr-header {
            background: var(--k-navy-soft);
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--k-gray-200);
        }

        .qr-header h5 { font-size: 0.85rem; font-weight: 600; margin: 0; color: var(--k-navy); }

        .rate-limit-card {
            background: linear-gradient(135deg, var(--k-navy-soft), var(--k-white));
            border-left: 4px solid var(--k-accent);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
        }

        .rate-limit-text { font-size: 0.7rem; font-weight: 600; color: var(--k-navy); }

.row-custom {
    display: grid;
    grid-template-columns: minmax(500px, 0.98fr) minmax(580px, 1.02fr);
    gap: 1.35rem;
    align-items: stretch;
}

.workspace-panel {
    height: clamp(720px, calc(100vh - 235px), 920px);
    min-height: 720px;
    display: flex;
    flex-direction: column;
}

.workspace-panel > .k-card-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

.compose-panel > .k-card-body {
    padding-bottom: 0;
}

.logs-panel {
    max-height: none;
}

.logs-panel > .k-card-body {
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.logs-panel .filter-card {
    flex: 0 0 auto;
}

.logs-panel .table-container {
    flex: 1 1 auto;
    max-height: none;
    min-height: 0;
    border-top: 1px solid var(--k-soft-line);
    background: var(--k-white);
    overflow-x: auto;
}

        .filter-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .panel-meta {
            color: #52657d;
            font-size: 0.68rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .compose-footer {
            position: sticky;
            bottom: 0;
            margin: 1.25rem -1.35rem -1.35rem;
            padding: 0.9rem 1.35rem;
            background: rgba(255, 255, 255, 0.96);
            border-top: 1px solid var(--k-soft-line);
            backdrop-filter: blur(8px);
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .compose-action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .k-table {
            min-width: 680px;
        }

        @media (max-width: 992px) {
            .row-custom { grid-template-columns: 1fr; gap: 1rem; }
            .k-card-body { padding: 1rem; }
            .workspace-panel, .logs-panel { height: auto; min-height: auto; max-height: none; }
            .workspace-panel > .k-card-body { overflow: visible; }
            .logs-panel .table-container { min-height: 320px; }
            .filter-actions { align-items: flex-start; flex-direction: column; }
            .compose-footer { position: static; margin: 1rem 0 0; padding: 0; border-top: 0; background: transparent; }
        }

        @media (max-width: 576px) {
            .main-container { padding: 0.8rem; }
            .top-bar { padding: 0.75rem 0.9rem; align-items: flex-start; gap: 0.75rem; }
            .top-bar-right { width: 100%; flex-wrap: wrap; gap: 0.5rem; }
            .nav-link-custom, .date-display, .status-indicator { font-size: 0.64rem; padding: 0.35rem 0.55rem; }
            .compose-footer { align-items: stretch !important; }
            .compose-action-buttons { width: 100%; justify-content: stretch; }
            .compose-action-buttons .k-btn { flex: 1 1 150px; justify-content: center; }
            .filter-card .form-control,
            .filter-card .form-select { min-width: 0; }
            .filter-buttons .k-btn { flex: 1 1 120px; justify-content: center; }
        }

        .table-container::-webkit-scrollbar { width: 6px; height: 6px; }
        .table-container::-webkit-scrollbar-track { background: var(--k-gray-100); border-radius: 3px; }
        .table-container::-webkit-scrollbar-thumb { background: var(--k-gray-400); border-radius: 3px; }
        .table-container::-webkit-scrollbar-thumb:hover { background: var(--k-accent); }

        .text-danger { color: #dc2626 !important; }
        .text-warning { color: #f59e0b !important; }
        .text-success { color: #10b981 !important; }
        .text-muted { color: var(--k-gray-500) !important; }

        .contact-checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
            border: 1px solid transparent;
        }

        .contact-checkbox-label:hover {
            background: var(--k-navy-soft);
            border-color: #d8e5f4;
        }

        .contact-checkbox:checked + label {
            color: var(--k-navy);
        }

        .contact-checkbox { margin-right: 0.5rem; transform: scale(1.1); accent-color: var(--k-accent); }

        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, var(--k-soft-line), transparent);
            margin: 1rem 0;
        }

        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0;
            padding: 0.8rem;
            border-top: 1px solid var(--k-soft-line);
            background: #fbfdff;
        }

        .pagination-controls .k-btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.65rem;
        }

        .pagination-controls .badge-count {
            background: var(--k-navy);
            color: white;
            padding: 0.25rem 0.75rem;
        }

        .schedule-card {
            background: var(--k-gray-50);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            border: 1px solid var(--k-gray-200);
            border-left: 3px solid var(--k-accent);
        }

        .badge-orange {
            background: var(--k-accent);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            font-size: 0.6rem;
            font-weight: 600;
        }

        .link-orange {
            color: var(--k-accent);
            text-decoration: none;
            transition: all 0.2s;
        }

        .link-orange:hover {
            color: var(--k-navy);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="top-bar">
            <div class="page-title">
                <div style="display: flex; align-items: center;">
                    <div class="logo-icon"><i class="fas fa-paper-plane"></i></div>
                    <div><h1>KURMIGO - WhatsApp Marketing Automation Tool</h1><p>Bulk messaging solution for business</p></div>
                </div>
            </div>
            <div class="top-bar-right">
                <a href="{{ route('dashboard.index') }}" class="nav-link-custom">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('wa-inbox.index') }}" class="nav-link-custom">
                    <i class="fas fa-inbox"></i> Inbox
                    <span class="badge-orange">3</span>
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
                <div class="status-indicator">
                    <span class="status-dot" id="status-dot"></span>
                    <span id="wa-status-text">Checking connection...</span>
                </div>
            </div>
        </div>

        <div id="loading-overlay" class="loading-overlay">
            <div class="loading-spinner">
                <div class="spinner-border mb-3"></div>
                <h6 id="loading-text" style="font-size: 0.9rem;">Sending messages...</h6>
                <div class="progress-container" style="width: 260px;"><div id="loading-progress" class="progress-fill"></div></div>
                <p id="loading-status" class="mt-2 small text-muted" style="font-size: 0.7rem;">0 / 0</p>
            </div>
        </div>

        <div class="main-container">
            <div class="row-custom">
                <!-- Compose Message Card -->
                <div class="k-card workspace-panel compose-panel">
                    <div class="k-card-header">
                        <h3><i class="fas fa-pen-fancy"></i> Compose Message</h3>
                    </div>
                    <div class="k-card-body">
                        <div class="rate-limit-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="rate-limit-text"><i class="fas fa-tachometer-alt me-2"></i> Rate Limit</span>
                                <span id="rate-limit-badge" class="badge-status info" style="background: white;">0/100 hour</span>
                            </div>
                        </div>

                        <form id="blast-form">
                            @csrf
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-heading"></i> Judul <span class="text-muted">(Opsional)</span></label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="Contoh: PROMO SPESIAL! atau 📢 INFO PENTING">
                                <small class="text-muted text-xs d-block mt-1">Judul akan muncul tebal di WhatsApp. Maksimal 100 karakter.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Pesan <span class="text-danger">*</span></label>
                                <textarea id="message" name="message" class="form-control" rows="5" required placeholder="Ketik pesan Anda di sini..."></textarea>
                                <div class="mt-1 text-end"><small class="text-muted" id="char-counter" style="font-size: 0.6rem;">0 / 2000 karakter</small></div>
                            </div>

                            <div class="form-group">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="include-image">
                                    <label class="form-check-label" for="include-image">
                                        <i class="fas fa-image me-1"></i> Sertakan Gambar
                                    </label>
                                </div>

                                <div id="image-section" style="display: none;">
                                    <label class="form-label"><i class="fas fa-link"></i> URL Gambar</label>
                                    <input type="url" id="image-url" class="form-control" placeholder="https://wa.kurmigo.id/images/nama.jpg">
                                    <small class="text-muted text-xs d-block mt-1">Masukkan URL gambar (jpg, png, jpeg)</small>

                                    <div id="image-preview" class="mt-2" style="display: none;">
                                        <img id="preview-img" src="" style="max-width: 100%; max-height: 150px; border-radius: 0.75rem;">
                                        <button type="button" id="remove-image" class="k-btn k-btn-outline k-btn-sm mt-2">Hapus</button>
                                    </div>
                                </div>
                            </div>

                            <div class="section-divider"></div>

                            <div class="input-method-tab">
                                <div class="method-tab active" data-method="manual"><i class="fas fa-keyboard"></i> Manual Input</div>
                                <div class="method-tab" data-method="excel"><i class="fas fa-file-excel"></i> Excel Upload</div>
                                <div class="method-tab" data-method="group"><i class="fas fa-users"></i> From Groups</div>
                            </div>

                            <div id="manual-input" class="input-method-content">
                                <label class="form-label"><i class="fas fa-phone-alt"></i> WhatsApp Numbers <span class="text-danger">*</span></label>
                                <textarea id="numbers" name="numbers" class="form-control" rows="5" placeholder="+628123456789&#10;+628987654321&#10;08123456789"></textarea>
                                <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;">Format: +628xxx atau 08xxx (akan diformat otomatis)</small>
                            </div>

                            <div id="excel-input" class="input-method-content" style="display: none;">
                                <label class="form-label"><i class="fas fa-file-excel"></i> File Excel</label>
                                <div id="upload-area" class="upload-area">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-0">Klik atau drag & drop file Excel</p>
                                    <small>Format: .xlsx, .xls, .csv | Kolom pertama berisi nomor WhatsApp</small>
                                    <input type="file" id="excel-file" accept=".xlsx,.xls,.csv" style="display: none;">
                                </div>
                                <div id="excel-preview" class="mt-3" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong style="font-size: 0.7rem; color: var(--k-gray-700);"><i class="fas fa-list me-1"></i> Preview Numbers:</strong>
                                        <span id="excel-count" class="badge-count success" style="font-size: 0.6rem;">0 numbers</span>
                                    </div>
                                    <div id="preview-list" class="preview-numbers"></div>
                                    <button type="button" id="clear-excel" class="k-btn k-btn-outline k-btn-sm mt-2"><i class="fas fa-trash me-1"></i> Clear</button>
                                </div>
                            </div>

                            <div id="group-input" class="input-method-content" style="display: none;">
                                <label class="form-label"><i class="fas fa-layer-group"></i> Pilih Group</label>
                                <select id="group-select" class="form-select mb-3">
                                    <option value="">-- Pilih Contact Group --</option>
                                </select>

                                <div id="group-contacts-container" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0"><i class="fas fa-list-check"></i> Pilih Kontak</label>
                                        <div>
                                            <button type="button" id="select-all-contacts" class="k-btn k-btn-outline k-btn-sm">Pilih Semua</button>
                                            <button type="button" id="unselect-all-contacts" class="k-btn k-btn-outline k-btn-sm ms-1">Hapus Pilihan</button>
                                        </div>
                                    </div>
                                    <div id="group-contacts-list" class="preview-numbers" style="max-height: 250px;">
                                        <div class="text-center text-muted py-3">Pilih group terlebih dahulu</div>
                                    </div>
                                    <div class="mt-2">
                                        <span id="selected-contacts-count" class="badge-count">0 kontak dipilih</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Section -->
                            <div class="schedule-card">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="schedule-blast">
                                    <label class="form-check-label" for="schedule-blast">
                                        <i class="fas fa-calendar-alt me-1"></i> Jadwalkan Pengiriman
                                    </label>
                                </div>

                                <div id="schedule-section" style="display: none;">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-calendar-day"></i> Tanggal</label>
                                            <input type="date" id="schedule-date" class="form-control" min="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-clock"></i> Jam</label>
                                            <input type="time" id="schedule-time" class="form-control" step="60">
                                        </div>
                                    </div>
                                    <small class="text-muted text-xs d-block mt-2">
                                        <i class="fas fa-info-circle"></i> Pesan akan dikirim otomatis pada waktu yang dipilih
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center compose-footer">
                                <div><span id="numbers-count" class="badge-count">0 nomor</span></div>
                                <div class="compose-action-buttons">
                                    <button type="button" id="schedule-btn" class="k-btn k-btn-outline" style="display: none;">
                                        <i class="fas fa-calendar-alt me-2"></i>Jadwalkan
                                    </button>
                                    <button type="submit" id="send-btn" class="k-btn k-btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Sekarang
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delivery Logs Card -->
                <div class="k-card workspace-panel logs-panel">
                    <div class="k-card-header">
                        <h3><i class="fas fa-history"></i> Riwayat Pengiriman</h3>
                    </div>
                    <div class="k-card-body p-0">
                        <div class="filter-card" style="margin: 1rem 1rem 0 1rem;">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" id="search-number" class="form-control" placeholder="Cari nomor..." style="font-size: 0.7rem;">
                                </div>
                                <div class="col-md-3">
                                    <select id="filter-status" class="form-select" style="font-size: 0.7rem;"></select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" id="filter-date" class="form-control" placeholder="Pilih tanggal..." style="font-size: 0.7rem;">
                                </div>
                                <div class="col-md-2">
                                    <select id="per-page" class="form-select" style="font-size: 0.7rem;">
                                        <option value="10">10 baris</option>
                                        <option value="25" selected>25 baris</option>
                                        <option value="50">50 baris</option>
                                        <option value="100">100 baris</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <div class="filter-buttons">
                                    <button id="btn-filter" class="k-btn k-btn-primary k-btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                                    <button id="btn-reset" class="k-btn k-btn-outline k-btn-sm"><i class="fas fa-undo me-1"></i>Reset</button>
                                </div>
                                <span class="panel-meta" id="log-count">Total: 0</span>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="k-table">
                                <thead>
                                    <tr>
                                        <th style="width: 20%;">Waktu</th>
                                        <th style="width: 25%;">Nomor</th>
                                        <th style="width: 15%;">Status</th>
                                        <th style="width: 40%;">Judul</th>
                                    </tr>
                                </thead>
                                <tbody id="log-table-body">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Memuat data...<\/td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="pagination-controls" class="pagination-controls"></div>
                    </div>
                </div>
            </div>

            <div id="qr-container" class="qr-container mt-3" style="display: none;">
                <div class="qr-header">
                    <h5><i class="fas fa-qrcode me-2"></i>Scan QR Code</h5>
                </div>
                <div class="text-center p-4">
                    <canvas id="qr-code"></canvas>
                    <p class="text-muted mt-3 small" id="qr-instruction">Scan QR code dengan WhatsApp Web di HP Anda</p>
                </div>
            </div>

            <div class="k-card mt-3">
                <div class="k-card-header">
                    <h3><i class="fas fa-microchip"></i> WhatsApp Service Manager</h3>
                </div>
                <div class="k-card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge-status" id="node-status-badge" style="background: #fee2e2; color: #dc2626;">
                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Checking...
                                </span>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="reset-session-checkbox">
                                    <label class="form-check-label small" for="reset-session-checkbox">
                                        <i class="fas fa-trash-alt me-1"></i> Reset Session (ganti nomor WA)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button id="btn-force-reset" class="k-btn k-btn-orange k-btn-sm">
                                <i class="fas fa-bolt me-1"></i> Force Reset WhatsApp
                            </button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" id="node-status-message">
                            <i class="fas fa-info-circle me-1"></i>
                            Klik "Force Reset" untuk restart service WhatsApp. Centang "Reset Session" jika ingin mengganti nomor WA.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="logDetailModal" class="modal-custom">
        <div class="modal-custom-content">
            <div class="modal-custom-header">
                <h4><i class="fas fa-info-circle me-2"></i> Detail Pengiriman</h4>
                <button class="modal-custom-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-custom-body">
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-phone me-1"></i> Nomor WhatsApp</div>
                    <div class="detail-value" id="detail-number">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-heading me-1"></i> Judul</div>
                    <div class="detail-value" id="detail-title">-</div>
                </div>
                <div class="detail-item" id="detail-image-container" style="display: none;">
                    <div class="detail-label"><i class="fas fa-image me-1"></i> Gambar</div>
                    <div class="detail-value-image">
                        <img id="detail-image" src="" alt="Tidak ada gambar" style="max-width: 100%; border-radius: 0.5rem;">
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-envelope me-1"></i> Pesan</div>
                    <div class="detail-value-message" id="detail-message">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-tag me-1"></i> Status</div>
                    <div class="detail-value" id="detail-status">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-comment me-1"></i> Response</div>
                    <div class="detail-value-message" id="detail-response">-</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fas fa-clock me-1"></i> Waktu Kirim</div>
                    <div class="detail-value" id="detail-time">-</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const today = new Date();
        document.getElementById('current-date').textContent = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

        const statusDot = document.getElementById('status-dot');
        const waStatusText = document.getElementById('wa-status-text');
        const rateLimitBadge = document.getElementById('rate-limit-badge');
        const qrContainer = document.getElementById('qr-container');
        const qrCanvas = document.getElementById('qr-code');
        const qrInstruction = document.getElementById('qr-instruction');
        const numbersTextarea = document.getElementById('numbers');
        const numbersCountSpan = document.getElementById('numbers-count');
        const messageTextarea = document.getElementById('message');
        const titleInput = document.getElementById('title');
        const charCounter = document.getElementById('char-counter');
        const uploadArea = document.getElementById('upload-area');
        const excelFile = document.getElementById('excel-file');
        const excelPreview = document.getElementById('excel-preview');
        const previewList = document.getElementById('preview-list');
        const excelCount = document.getElementById('excel-count');
        const clearExcel = document.getElementById('clear-excel');
        const methodTabs = document.querySelectorAll('.method-tab');
        const manualInput = document.getElementById('manual-input');
        const excelInput = document.getElementById('excel-input');
        const groupInput = document.getElementById('group-input');
        const loadingOverlay = document.getElementById('loading-overlay');
        const loadingText = document.getElementById('loading-text');
        const loadingProgress = document.getElementById('loading-progress');
        const loadingStatus = document.getElementById('loading-status');
        const searchNumber = document.getElementById('search-number');
        const filterStatus = document.getElementById('filter-status');
        const filterDate = document.getElementById('filter-date');
        const btnFilter = document.getElementById('btn-filter');
        const btnReset = document.getElementById('btn-reset');
        const logCount = document.getElementById('log-count');

        const modal = document.getElementById('logDetailModal');

        const includeImageCheckbox = document.getElementById('include-image');
        const imageSection = document.getElementById('image-section');
        const imageUrlInput = document.getElementById('image-url');
        const imagePreview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        const removeImageBtn = document.getElementById('remove-image');

        const groupSelect = document.getElementById('group-select');
        const groupContactsContainer = document.getElementById('group-contacts-container');
        const groupContactsList = document.getElementById('group-contacts-list');
        const selectedContactsCount = document.getElementById('selected-contacts-count');
        const selectAllBtn = document.getElementById('select-all-contacts');
        const unselectAllBtn = document.getElementById('unselect-all-contacts');

        const scheduleCheckbox = document.getElementById('schedule-blast');
        const scheduleSection = document.getElementById('schedule-section');
        const scheduleDate = document.getElementById('schedule-date');
        const scheduleTime = document.getElementById('schedule-time');
        const scheduleBtn = document.getElementById('schedule-btn');
        const sendBtn = document.getElementById('send-btn');

        const PROXY_URL = '/wa-proxy';
        let excelNumbers = [];
        let selectedContactsFromGroup = [];
        let statusCheckInterval = null;
        let isSending = false;
        let rateLimitSent = 0;
        let rateLimitMax = 100;
        let currentPage = 1;
        let perPage = 25;

        window.openModal = function(log) {
            document.getElementById('detail-number').innerHTML = `<code>${escapeHtml(log.number)}</code>`;
            document.getElementById('detail-title').innerHTML = log.title ? escapeHtml(log.title) : '<em class="text-muted">Tidak ada judul</em>';
            document.getElementById('detail-message').innerHTML = escapeHtml(log.message || '-').replace(/\n/g, '<br>');
            document.getElementById('detail-status').innerHTML = getStatusBadge(log.status);
            document.getElementById('detail-response').innerHTML = escapeHtml(log.response || '-').replace(/\n/g, '<br>');
            document.getElementById('detail-time').innerHTML = formatDate(log.created_at);

            const imageContainer = document.getElementById('detail-image-container');
            const imageElem = document.getElementById('detail-image');

            if (log.image_url && log.image_url.trim()) {
                imageElem.src = log.image_url;
                imageContainer.style.display = 'block';
            } else {
                imageContainer.style.display = 'none';
                imageElem.src = '';
            }

            modal.classList.add('show');
        };

        window.closeModal = function() {
            modal.classList.remove('show');
        };

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        flatpickr("#filter-date", { mode: "range", dateFormat: "Y-m-d", placeholder: "Pilih rentang tanggal" });
        filterStatus.innerHTML = ['All', 'success', 'error', 'invalid', 'pending'].map(s => `<option value="${s}">${s === 'All' ? 'Semua Status' : s}</option>`).join('');

        methodTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                methodTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const method = tab.dataset.method;

                if (method === 'manual') {
                    manualInput.style.display = 'block';
                    excelInput.style.display = 'none';
                    groupInput.style.display = 'none';
                } else if (method === 'excel') {
                    manualInput.style.display = 'none';
                    excelInput.style.display = 'block';
                    groupInput.style.display = 'none';
                } else if (method === 'group') {
                    manualInput.style.display = 'none';
                    excelInput.style.display = 'none';
                    groupInput.style.display = 'block';

                    if (groupSelect.options.length <= 1) {
                        loadGroups();
                    }
                }
                updateNumberCount();
            });
        });

        async function loadGroups() {
            try {
                const response = await axios.get('/contact-groups/list');
                const groups = response.data.groups;
                groupSelect.innerHTML = '<option value="">-- Pilih Contact Group --</option>';

                groups.forEach(group => {
                    const option = document.createElement('option');
                    option.value = group.id;
                    option.textContent = `${group.name} (${group.contacts_count} kontak)`;
                    groupSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading groups:', error);
            }
        }

        async function loadGroupContacts(groupId) {
            if (!groupId) {
                groupContactsContainer.style.display = 'none';
                selectedContactsFromGroup = [];
                updateNumberCount();
                return;
            }

            try {
                const response = await axios.get(`/contact-groups/contacts/${groupId}`);
                const contacts = response.data.contacts;

                if (contacts.length === 0) {
                    groupContactsList.innerHTML = '<div class="text-center text-muted py-3">Tidak ada kontak di group ini</div>';
                    groupContactsContainer.style.display = 'block';
                    selectedContactsFromGroup = [];
                    updateNumberCount();
                    return;
                }

                let html = '';
                contacts.forEach(contact => {
                    const number = contact.number;
                    const name = contact.name || number;
                    html += `
                        <div class="contact-checkbox-label">
                            <input type="checkbox" class="contact-checkbox" value="${number}" data-name="${escapeHtml(name)}" id="contact_${contact.id}">
                            <label for="contact_${contact.id}" style="flex:1; cursor:pointer;">
                                <strong>${escapeHtml(name)}</strong><br>
                                <code class="text-muted" style="font-size: 0.65rem;">${number}</code>
                            </label>
                        </div>
                    `;
                });

                groupContactsList.innerHTML = html;
                groupContactsContainer.style.display = 'block';

                document.querySelectorAll('.contact-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateSelectedContacts);
                });

                updateSelectedContacts();

            } catch (error) {
                console.error('Error loading contacts:', error);
                groupContactsList.innerHTML = '<div class="text-center text-danger py-3">Gagal memuat kontak</div>';
                groupContactsContainer.style.display = 'block';
            }
        }

        function updateSelectedContacts() {
            const checkboxes = document.querySelectorAll('.contact-checkbox:checked');
            selectedContactsFromGroup = Array.from(checkboxes).map(cb => cb.value);
            const count = selectedContactsFromGroup.length;
            selectedContactsCount.innerHTML = `${count} kontak dipilih`;
            updateNumberCount();
        }

        function selectAllContacts() {
            document.querySelectorAll('.contact-checkbox').forEach(cb => {
                cb.checked = true;
            });
            updateSelectedContacts();
        }

        function unselectAllContacts() {
            document.querySelectorAll('.contact-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateSelectedContacts();
        }

        if (groupSelect) {
            groupSelect.addEventListener('change', function() {
                const groupId = this.value;
                if (groupId) {
                    loadGroupContacts(groupId);
                } else {
                    groupContactsContainer.style.display = 'none';
                    selectedContactsFromGroup = [];
                    updateNumberCount();
                }
            });
        }

        if (selectAllBtn) selectAllBtn.addEventListener('click', selectAllContacts);
        if (unselectAllBtn) unselectAllBtn.addEventListener('click', unselectAllContacts);

        uploadArea.addEventListener('click', () => excelFile.click());
        uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.style.background = '#f0f0f0'; });
        uploadArea.addEventListener('dragleave', (e) => { e.preventDefault(); uploadArea.style.background = ''; });
        uploadArea.addEventListener('drop', (e) => { e.preventDefault(); uploadArea.style.background = ''; const file = e.dataTransfer.files[0]; if (file) processExcelFile(file); });
        excelFile.addEventListener('change', (e) => { if (e.target.files[0]) processExcelFile(e.target.files[0]); });

        function processExcelFile(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                excelNumbers = [];
                rows.forEach(row => { if (row[0]) excelNumbers.push(row[0].toString().trim()); });
                excelNumbers = [...new Set(excelNumbers)].filter(n => n && n.length > 5);
                if (excelNumbers.length > 500) { alert(`Maksimal 500 nomor!`); excelNumbers = excelNumbers.slice(0, 500); }
                previewList.innerHTML = excelNumbers.map(num => `<div><code>${escapeHtml(num)}</code></div>`).join('');
                excelCount.textContent = `${excelNumbers.length} nomor`;
                excelPreview.style.display = 'block';
                updateNumberCount();
            };
            reader.readAsArrayBuffer(file);
        }

        clearExcel.addEventListener('click', () => { excelNumbers = []; excelPreview.style.display = 'none'; excelFile.value = ''; updateNumberCount(); });

        function getCurrentNumbers() {
            const activeMethod = document.querySelector('.method-tab.active').dataset.method;
            if (activeMethod === 'manual') {
                return numbersTextarea.value.split('\n').map(n => n.trim()).filter(n => n.length > 0);
            } else if (activeMethod === 'excel') {
                return excelNumbers;
            } else if (activeMethod === 'group') {
                return selectedContactsFromGroup;
            }
            return [];
        }

        function updateNumberCount() {
            const count = getCurrentNumbers().length;
            numbersCountSpan.textContent = `${count} nomor`;
            numbersCountSpan.className = count > 500 ? 'badge-count' : (count > 0 ? 'badge-count success' : 'badge-count');
            if (count > 500) numbersCountSpan.textContent = `${count} nomor (melebihi batas 500!)`;
        }

        function updateCharCount() { const len = messageTextarea.value.length; charCounter.textContent = `${len} / 2000 karakter`; charCounter.classList.toggle('text-danger', len > 2000); }

        numbersTextarea.addEventListener('input', updateNumberCount);
        messageTextarea.addEventListener('input', updateCharCount);
        updateNumberCount(); updateCharCount();

        async function updateRateLimit() {
            try {
                const res = await fetch(`${PROXY_URL}/wa-status`);
                const data = await res.json();
                if (data.rateLimit) {
                    rateLimitSent = data.rateLimit.messagesSent || 0;
                    rateLimitMax = data.rateLimit.maxPerHour || 100;
                    rateLimitBadge.textContent = `${rateLimitSent}/${rateLimitMax} jam`;
                }
            } catch(e) {
                console.error('Failed to fetch rate limit:', e);
            }
        }

        if (scheduleCheckbox) {
            scheduleCheckbox.addEventListener('change', function() {
                scheduleSection.style.display = this.checked ? 'block' : 'none';
                if (this.checked) {
                    scheduleBtn.style.display = 'inline-flex';
                    sendBtn.style.display = 'none';
                } else {
                    scheduleBtn.style.display = 'none';
                    sendBtn.style.display = 'inline-flex';
                }
            });
        }

        if (scheduleBtn) {
            scheduleBtn.addEventListener('click', async function() {
                const message = messageTextarea.value.trim();
                const numbers = getCurrentNumbers();
                const title = titleInput ? titleInput.value.trim() : null;
                const includeImage = includeImageCheckbox ? includeImageCheckbox.checked : false;
                const imageUrl = includeImage && imageUrlInput ? imageUrlInput.value.trim() : null;

                if (numbers.length === 0) { alert('Masukkan minimal 1 nomor WA'); return; }
                if (!message) { alert('Pesan tidak boleh kosong'); return; }
                if (numbers.length > 500) { alert('Maksimal 500 nomor per blast!'); return; }

                const scheduleDateValue = scheduleDate.value;
                const scheduleTimeValue = scheduleTime.value;

                if (!scheduleDateValue || !scheduleTimeValue) {
                    alert('Pilih tanggal dan jam untuk penjadwalan');
                    return;
                }

                const scheduledAt = new Date(`${scheduleDateValue}T${scheduleTimeValue}:00`);

                if (scheduledAt <= new Date()) {
                    alert('Waktu penjadwalan harus lebih besar dari sekarang');
                    return;
                }

                let fullMessage = message;
                if (title) {
                    fullMessage = `*${title}*\n\n${message}`;
                }

                const confirmMsg = `Jadwalkan pengiriman ke ${numbers.length} nomor pada:\n${scheduledAt.toLocaleString('id-ID')}\n\nLanjutkan?`;
                if (!confirm(confirmMsg)) return;

                scheduleBtn.disabled = true;
                scheduleBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menjadwalkan...';

                try {
                    const response = await axios.post('/wa-schedule', {
                        title: title,
                        message: fullMessage,
                        numbers: numbers,
                        image_url: imageUrl,
                        scheduled_at: scheduledAt.toISOString()
                    });

                    if (response.data.success) {
                        alert('✅ Pesan berhasil dijadwalkan!\n\nPengiriman akan dilakukan otomatis pada waktu yang ditentukan.');
                        numbersTextarea.value = '';
                        messageTextarea.value = '';
                        if (titleInput) titleInput.value = '';
                        excelNumbers = [];
                        excelPreview.style.display = 'none';
                        excelFile.value = '';
                        selectedContactsFromGroup = [];
                        if (groupSelect) groupSelect.value = '';
                        if (groupContactsContainer) groupContactsContainer.style.display = 'none';
                        if (includeImageCheckbox) includeImageCheckbox.checked = false;
                        if (imageSection) imageSection.style.display = 'none';
                        if (imageUrlInput) imageUrlInput.value = '';
                        if (imagePreview) imagePreview.style.display = 'none';
                        scheduleCheckbox.checked = false;
                        scheduleSection.style.display = 'none';
                        scheduleDate.value = '';
                        scheduleTime.value = '';
                        updateNumberCount();
                        updateCharCount();
                    } else {
                        alert('Gagal menjadwalkan: ' + (response.data.message || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Error: ' + (error.response?.data?.message || error.message));
                } finally {
                    scheduleBtn.disabled = false;
                    scheduleBtn.innerHTML = '<i class="fas fa-calendar-alt me-2"></i>Jadwalkan';
                    sendBtn.style.display = 'inline-flex';
                    scheduleBtn.style.display = 'none';
                }
            });
        }

        if (includeImageCheckbox) {
            includeImageCheckbox.addEventListener('change', function() {
                imageSection.style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    if (imageUrlInput) imageUrlInput.value = '';
                    if (imagePreview) imagePreview.style.display = 'none';
                }
            });
        }

        if (imageUrlInput) {
            imageUrlInput.addEventListener('input', function() {
                const url = this.value.trim();
                if (url && (url.match(/\.(jpeg|jpg|png|gif|webp)/i) || url.startsWith('http'))) {
                    previewImg.src = url;
                    imagePreview.style.display = 'block';
                } else {
                    imagePreview.style.display = 'none';
                }
            });
        }

        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', function() {
                if (imageUrlInput) imageUrlInput.value = '';
                if (imagePreview) imagePreview.style.display = 'none';
            });
        }

        async function loadLogs(params = {}) {
            try {
                const queryParams = new URLSearchParams({
                    page: currentPage,
                    per_page: perPage,
                    ...params
                }).toString();

                const response = await axios.get(`/wa-logs/data${queryParams ? '?' + queryParams : ''}`);
                const logs = response.data.logs;
                const pagination = response.data.pagination || {
                    total: response.data.total,
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    per_page: perPage
                };

                logCount.textContent = `Total: ${pagination.total} | Halaman ${pagination.current_page} / ${pagination.last_page}`;

                const tbody = document.getElementById('log-table-body');
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">📭 Tidak ada data log<\/td><\/tr>';
                    return;
                }

                tbody.innerHTML = logs.map(log => `
                    <tr>
                        <td style="white-space: nowrap;">${formatDate(log.created_at)}<\/td>
                        <td><code class="text-navy fw-semibold">${escapeHtml(log.number)}<\/code><\/td>
                        <td>${getStatusBadge(log.status)}<\/td>
                        <td>${log.title ? escapeHtml(log.title.substring(0, 50)) + (log.title.length > 50 ? '...' : '') : '-'}<\/td>
                    <\/tr>
                `).join('');

                document.querySelectorAll('#log-table-body tr').forEach((row, index) => {
                    const logData = logs[index];
                    if (logData) {
                        row.style.cursor = 'pointer';
                        row.addEventListener('click', () => openModal(logData));
                    }
                });

                renderPagination(pagination);

            } catch (err) {
                console.error('Error loading logs:', err);
                document.getElementById('log-table-body').innerHTML = '<td><td colspan="4" class="text-center text-danger py-4">❌ Gagal memuat data<\/td><\/tr>';
            }
        }

        function renderPagination(pagination) {
            const paginationDiv = document.getElementById('pagination-controls');
            if (!paginationDiv) return;

            if (pagination.last_page <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }

            let html = '';

            if (pagination.current_page > 1) {
                html += `<button class="k-btn k-btn-outline k-btn-sm" onclick="goToPage(${pagination.current_page - 1})"><i class="fas fa-chevron-left"></i> Sebelumnya</button>`;
            } else {
                html += `<button class="k-btn k-btn-outline k-btn-sm" disabled><i class="fas fa-chevron-left"></i> Sebelumnya</button>`;
            }

            html += `<span class="badge-count" style="background: var(--k-navy); color: white;">${pagination.current_page} / ${pagination.last_page}</span>`;

            if (pagination.current_page < pagination.last_page) {
                html += `<button class="k-btn k-btn-outline k-btn-sm" onclick="goToPage(${pagination.current_page + 1})">Selanjutnya <i class="fas fa-chevron-right"></i></button>`;
            } else {
                html += `<button class="k-btn k-btn-outline k-btn-sm" disabled>Selanjutnya <i class="fas fa-chevron-right"></i></button>`;
            }

            paginationDiv.innerHTML = html;
        }

        window.goToPage = function(page) {
            currentPage = page;
            applyFilter(false);
        };

        function formatDate(dateStr) { if (!dateStr) return '-'; const date = new Date(dateStr); return date.toLocaleString('id-ID', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }); }
        function getStatusBadge(status) { const badges = { 'success': '<span class="badge-status success">Berhasil</span>', 'error': '<span class="badge-status error">Gagal</span>', 'invalid': '<span class="badge-status warning">Invalid</span>', 'pending': '<span class="badge-status info">Pending</span>' }; return badges[status] || `<span class="badge-status">${status}</span>`; }
        function escapeHtml(text) { if (!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        function setActionBusy(button, isBusy, busyLabel = 'Memproses...') {
            if (!button) return;
            if (isBusy) {
                button.dataset.originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${busyLabel}`;
                return;
            }
            button.disabled = false;
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
        }

        function applyFilter(resetPage = true) {
            if (resetPage) {
                currentPage = 1;
            }
            const params = {};
            if (searchNumber.value) params.number = searchNumber.value;
            if (filterStatus.value && filterStatus.value !== 'All') params.status = filterStatus.value;
            if (filterDate.value) params.date_range = filterDate.value;
            loadLogs(params);
        }

        function resetFilter() {
            currentPage = 1;
            searchNumber.value = '';
            filterStatus.value = 'All';
            filterDate.value = '';
            loadLogs();
        }

        btnFilter.addEventListener('click', () => applyFilter(true));
        btnReset.addEventListener('click', resetFilter);
        searchNumber.addEventListener('keypress', (e) => { if (e.key === 'Enter') applyFilter(true); });

        const perPageSelect = document.getElementById('per-page');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                perPage = parseInt(this.value);
                currentPage = 1;
                applyFilter(true);
            });
        }

        async function checkStatus() {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                const res = await fetch(`${PROXY_URL}/wa-status`, {
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json' }
                });
                clearTimeout(timeoutId);

                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                console.log('Status data:', data);

                if (data.rateLimit) {
                    rateLimitBadge.textContent = `${data.rateLimit.messagesSent || 0}/${data.rateLimit.maxPerHour || 100} jam`;
                }

                const qrCanvasElem = document.getElementById('qr-code');
                const qrInstructionElem = document.getElementById('qr-instruction');
                const qrContainerElem = document.getElementById('qr-container');
                const statusDotElem = document.getElementById('status-dot');
                const waStatusTextElem = document.getElementById('wa-status-text');

                if (data.qr && qrCanvasElem) {
                    if (statusDotElem) statusDotElem.className = 'status-dot warning';
                    if (waStatusTextElem) waStatusTextElem.textContent = '📱 Scan QR Code';
                    if (qrContainerElem) qrContainerElem.style.display = 'block';

                    qrCanvasElem.innerHTML = '';
                    QRCode.toCanvas(qrCanvasElem, data.qr, { width: 250, margin: 2, color: { dark: '#1e3a8a', light: '#ffffff' } });

                    if (qrInstructionElem) {
                        qrInstructionElem.innerHTML = '1. Buka WhatsApp di HP<br>2. Tap menu > WhatsApp Web<br>3. Scan QR code di atas';
                    }
                    return;
                }

                const isConnected = data.connected === true || data.status === 'connected' || data.ready === true;

                if (isConnected) {
                    if (statusDotElem) statusDotElem.className = 'status-dot connected';
                    if (waStatusTextElem) waStatusTextElem.textContent = '✅ Connected - WhatsApp Siap';
                    if (qrContainerElem) qrContainerElem.style.display = 'none';
                } else {
                    if (statusDotElem) statusDotElem.className = 'status-dot';
                    if (waStatusTextElem) waStatusTextElem.textContent = data.message || '⏳ Menghubungkan ke WhatsApp...';
                    if (qrContainerElem) qrContainerElem.style.display = 'none';
                }

            } catch(err) {
                console.error('Status check error:', err);
                const statusDotElem = document.getElementById('status-dot');
                const waStatusTextElem = document.getElementById('wa-status-text');
                if (statusDotElem) statusDotElem.className = 'status-dot';
                if (waStatusTextElem) waStatusTextElem.textContent = '⚠️ Tidak dapat terhubung ke layanan WA';
                const qrContainerElem = document.getElementById('qr-container');
                if (qrContainerElem) qrContainerElem.style.display = 'none';
            }
        }

        async function sendBlast(numbers, message, imageUrl = null, title = null) {
            if (isSending) {
                alert('Masih ada pengiriman yang berjalan! Tunggu sampai selesai.');
                return;
            }

            isSending = true;
            setActionBusy(sendBtn, true, 'Mengirim...');
            setActionBusy(scheduleBtn, true, 'Menunggu...');
            loadingOverlay.style.display = 'flex';
            loadingText.textContent = 'Mengirim pesan...';
            loadingProgress.style.width = '0%';
            loadingStatus.textContent = `0 / ${numbers.length}`;
            let successCount = 0, failCount = 0;

            try {
                for (let i = 0; i < numbers.length; i++) {
                    const number = numbers[i];
                    const percent = ((i + 1) / numbers.length * 100).toFixed(1);
                    loadingProgress.style.width = `${percent}%`;
                    loadingStatus.textContent = `${i + 1} / ${numbers.length} (✅ ${successCount} | ❌ ${failCount})`;

                    try {
                        const response = await axios.post(`${PROXY_URL}/send`, {
                            number,
                            message,
                            image_url: imageUrl,
                            title: title
                        });
                        if (response.data.status === 'success') {
                            successCount++;
                            loadingText.textContent = `✅ Berhasil ke ${number}`;
                        } else {
                            failCount++;
                            loadingText.textContent = `❌ Gagal ke ${number}: ${response.data.error}`;
                        }
                        await axios.post('/wa-logs/store', {
                            number,
                            message,
                            title: title,
                            image_url: imageUrl,
                            status: response.data.status,
                            response: JSON.stringify(response.data)
                        });
                        await updateRateLimit();
                    } catch (err) {
                        failCount++;
                        loadingText.textContent = `❌ Error: ${err.message}`;
                        await axios.post('/wa-logs/store', {
                            number,
                            message,
                            title: title,
                            image_url: imageUrl,
                            status: 'error',
                            response: err.message
                        });
                    }
                    if (i < numbers.length - 1) await new Promise(r => setTimeout(r, 3000));
                }

            loadingText.textContent = `✅ Selesai! ${successCount} berhasil, ${failCount} gagal`;
            loadingProgress.style.width = '100%';
            loadLogs();
            numbersTextarea.value = '';
            messageTextarea.value = '';
            if (titleInput) titleInput.value = '';
            excelNumbers = [];
            excelPreview.style.display = 'none';
            excelFile.value = '';
            selectedContactsFromGroup = [];
            if (groupSelect) groupSelect.value = '';
            if (groupContactsContainer) groupContactsContainer.style.display = 'none';
            if (includeImageCheckbox) includeImageCheckbox.checked = false;
            if (imageSection) imageSection.style.display = 'none';
            if (imageUrlInput) imageUrlInput.value = '';
            if (imagePreview) imagePreview.style.display = 'none';
            updateNumberCount();
            updateCharCount();
            } finally {
                setActionBusy(sendBtn, false);
                setActionBusy(scheduleBtn, false);
                setTimeout(() => { loadingOverlay.style.display = 'none'; isSending = false; }, 1200);
            }
        }

        const form = document.getElementById('blast-form');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const message = messageTextarea.value.trim();
            const numbers = getCurrentNumbers();
            const title = titleInput ? titleInput.value.trim() : null;
            const includeImage = includeImageCheckbox ? includeImageCheckbox.checked : false;
            const imageUrl = includeImage && imageUrlInput ? imageUrlInput.value.trim() : null;

            if (numbers.length === 0) { alert('Masukkan minimal 1 nomor WA'); return; }
            if (!message) { alert('Pesan tidak boleh kosong'); return; }
            if (numbers.length > 500) { alert(`Maksimal 500 nomor per blast!`); return; }
            if (message.length > 2000) { alert('Pesan maksimal 2000 karakter'); return; }
            if (title && title.length > 100) { alert('Judul maksimal 100 karakter'); return; }
            if (includeImage && !imageUrl) { alert('Masukkan URL gambar'); return; }

            try {
                const statusRes = await axios.get(`${PROXY_URL}/wa-status`);
                if (!statusRes.data.connected) {
                    alert('WhatsApp belum terhubung! Klik Force Reset untuk memulai.');
                    return;
                }
            } catch (err) {
                alert('Tidak dapat terhubung ke WhatsApp Service. Klik Force Reset.');
                return;
            }

            let fullMessage = message;
            if (title) {
                fullMessage = `*${title}*\n\n${message}`;
            }

            await sendBlast(numbers, fullMessage, imageUrl, title);
        });

        async function checkNodeStatus() {
            try {
                const res = await fetch('/wa-node/status');
                const data = await res.json();

                const badge = document.getElementById('node-status-badge');
                const message = document.getElementById('node-status-message');
                const btnForce = document.getElementById('btn-force-reset');

                console.log('Node status:', data);

                const waRes = await fetch('/wa-proxy/wa-status');
                const waData = await waRes.json();
                const isWAConnected = waData.connected === true || waData.status === 'connected';

                if (data.running || isWAConnected) {
                    badge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Node.js: BERJALAN';
                    badge.style.background = '#d1fae5';
                    badge.style.color = '#065f46';
                    if (message) {
                        if (isWAConnected) {
                            message.innerHTML = '<i class="fas fa-check-circle me-1"></i> Service aktif. WhatsApp siap digunakan.';
                        } else {
                            message.innerHTML = '<i class="fas fa-info-circle me-1"></i> Service aktif. Scan QR code untuk menghubungkan WhatsApp.';
                        }
                    }
                    if (btnForce) btnForce.disabled = false;
                } else {
                    badge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Node.js: BERHENTI';
                    badge.style.background = '#fee2e2';
                    badge.style.color = '#dc2626';
                    if (message) {
                        message.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Service tidak aktif. Klik Force Reset untuk menjalankan.';
                    }
                    if (btnForce) btnForce.disabled = false;
                }
            } catch (err) {
                console.error('Error checking node status:', err);
            }
        }

        async function forceReset() {
            const resetSession = document.getElementById('reset-session-checkbox').checked;

            const confirmMsg = resetSession ?
                '⚠️ RESET SESSION AKTIF: Semua data WhatsApp akan dihapus. Anda harus scan QR code baru. Lanjutkan?' :
                'Restart WhatsApp service?';

            if (!confirm(confirmMsg)) return;

            const btn = document.getElementById('btn-force-reset');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

            loadingOverlay.style.display = 'flex';
            loadingText.textContent = 'Merestart WhatsApp service...';
            loadingProgress.style.width = '50%';
            loadingStatus.textContent = 'Memproses...';

            try {
                const res = await axios.post('/wa-node/force-reset', { reset_session: resetSession });

                console.log('Force reset response:', res.data);

                loadingText.textContent = 'Service restarting...';
                loadingProgress.style.width = '75%';

                await new Promise(r => setTimeout(r, 3000));

                if (res.data.success) {
                    loadingText.textContent = '✅ Service berhasil direstart!';
                    loadingProgress.style.width = '100%';

                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        alert(res.data.message || 'Force reset successful! QR code akan muncul.');

                        if (resetSession) {
                            document.getElementById('reset-session-checkbox').checked = false;
                        }

                        setTimeout(() => location.reload(), 1000);
                    }, 1000);
                } else {
                    loadingOverlay.style.display = 'none';
                    alert('Error: ' + (res.data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error('Force reset error:', err);
                loadingOverlay.style.display = 'none';

                if (err.message === 'Network Error') {
                    alert('Force reset initiated. Halaman akan refresh dalam beberapa detik.');
                    setTimeout(() => location.reload(), 5000);
                } else {
                    alert('Error: ' + (err.response?.data?.message || err.message));
                }
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        document.getElementById('btn-force-reset')?.addEventListener('click', forceReset);

        checkStatus();
        loadLogs();
        checkNodeStatus();
        updateRateLimit();

        statusCheckInterval = setInterval(checkStatus, 5000);
        setInterval(() => loadLogs(), 30000);
        setInterval(checkNodeStatus, 10000);
        setInterval(updateRateLimit, 60000);
    });
    </script>
</body>
</html>

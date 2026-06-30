<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KURMIGO - Contact Groups</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f5f7fa; 
            color: #1e293b;
            font-size: 13px;
        }
        
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
            padding: 0.5rem 1.25rem; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            border-bottom: 1px solid var(--k-gray-200); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            box-shadow: var(--k-shadow-sm); 
        }
        
        .page-title h1 { 
            font-size: 1rem; 
            font-weight: 700; 
            color: var(--k-blue-dark); 
            margin: 0; 
        }
        
        .page-title p { 
            font-size: 0.65rem; 
            color: var(--k-gray-500); 
            margin: 0; 
        }
        
        .logo-icon { 
            width: 30px; 
            height: 30px; 
            background: var(--k-white); 
            border: 1px solid var(--k-gray-200);
            border-radius: 8px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 0.6rem; 
        }
        
        .logo-icon i { color: var(--k-blue-dark); font-size: 0.9rem; }
        
        .nav-link-custom {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            background: var(--k-gray-50);
            border-radius: 20px;
            color: var(--k-gray-600);
            font-size: 0.65rem;
            text-decoration: none;
            border: 1px solid var(--k-gray-200);
            transition: all 0.2s;
        }
        
        .nav-link-custom:hover {
            background: var(--k-blue-soft);
            border-color: var(--k-blue-dark);
            color: var(--k-blue-dark);
        }
        
        .nav-link-custom.active {
            background: var(--k-blue-dark);
            border-color: var(--k-blue-dark);
            color: white;
        }
        
        .date-display { 
            display: flex; 
            align-items: center; 
            gap: 0.3rem; 
            padding: 0.25rem 0.7rem; 
            background: var(--k-gray-50); 
            border-radius: 20px; 
            color: var(--k-gray-600); 
            font-size: 0.65rem; 
            border: 1px solid var(--k-gray-200); 
        }
        
        .main-container { max-width: 1400px; margin: 0 auto; padding: 1rem; }
        
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
            padding: 0.6rem 1rem; 
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
            width: 50px;
            height: 2px;
            background: var(--k-orange);
            border-radius: 2px;
        }
        
        .k-card-header h3 { 
            font-size: 0.8rem; 
            font-weight: 600; 
            color: var(--k-blue-dark); 
            margin: 0; 
            display: flex; 
            align-items: center; 
            gap: 0.4rem; 
        }
        
        .k-card-header h3 i { font-size: 0.85rem; }
        
        .k-card-body { padding: 0; }
        
        .k-btn { 
            padding: 0.3rem 0.9rem; 
            border-radius: 1.25rem; 
            font-weight: 500; 
            font-size: 0.65rem; 
            border: none; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem; 
            transition: all 0.2s; 
        }
        
        .k-btn-primary { 
            background: var(--k-blue-dark); 
            color: var(--k-white); 
        }
        
        .k-btn-primary:hover { 
            background: var(--k-blue-darker);
            transform: translateY(-1px);
            box-shadow: var(--k-shadow-blue);
        }
        
        .k-btn-orange {
            background: var(--k-orange);
            color: white;
        }
        
        .k-btn-orange:hover {
            background: var(--k-orange-dark);
            transform: translateY(-1px);
            box-shadow: var(--k-shadow-orange);
        }
        
        .k-btn-outline { 
            background: transparent; 
            border: 1px solid var(--k-gray-300); 
            color: var(--k-gray-500); 
        }
        
        .k-btn-outline:hover { 
            border-color: var(--k-orange);
            color: var(--k-orange); 
            background: var(--k-orange-soft);
            transform: translateY(-1px);
        }
        
        .k-btn-sm { 
            padding: 0.2rem 0.5rem; 
            font-size: 0.6rem; 
        }
        
        .group-list {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }
        
        .group-item {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--k-gray-100);
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .group-item:hover {
            background: var(--k-orange-soft);
        }
        
        .group-item.active {
            background: var(--k-blue-soft);
            border-left: 3px solid var(--k-blue-dark);
        }
        
        .group-name {
            font-weight: 600;
            color: var(--k-gray-800);
            font-size: 0.75rem;
            margin-bottom: 0.1rem;
        }
        
        .group-count {
            font-size: 0.6rem;
            color: var(--k-gray-500);
            background: var(--k-gray-100);
            padding: 0.1rem 0.4rem;
            border-radius: 1rem;
            display: inline-block;
        }
        
        .group-info {
            flex: 1;
        }
        
        .group-actions {
            display: flex;
            gap: 0.25rem;
            opacity: 0;
            transition: opacity 0.15s;
        }
        
        .group-item:hover .group-actions {
            opacity: 1;
        }
        
        .table-container { 
            max-height: calc(100vh - 180px);
            overflow-y: auto; 
            overflow-x: auto; 
        }
        
        .k-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 0.7rem;
        }
        
        .k-table th { 
            text-align: left; 
            padding: 0.5rem 0.6rem; 
            color: var(--k-gray-500); 
            font-size: 0.6rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.3px;
            border-bottom: 1px solid var(--k-gray-200); 
            background: var(--k-gray-50); 
            position: sticky;
            top: 0;
        }
        
        .k-table td { 
            padding: 0.4rem 0.6rem; 
            border-bottom: 1px solid var(--k-gray-100); 
            color: var(--k-gray-700); 
            vertical-align: middle;
        }
        
        .k-table tbody tr:hover {
            background: var(--k-orange-soft);
        }
        
        .k-table code {
            font-size: 0.65rem;
            background: none;
            padding: 0;
        }
        
        .badge-status {
            padding: 0.15rem 0.45rem;
            border-radius: 1rem;
            font-size: 0.55rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-status.info { 
            background: var(--k-blue-light); 
            color: var(--k-blue-dark); 
        }
        
        .badge-status.orange {
            background: var(--k-orange-light);
            color: var(--k-orange-dark);
        }
        
        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--k-gray-400);
        }
        
        .empty-state i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 0.7rem;
            margin: 0;
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
            border-radius: 0.75rem;
            max-width: 450px;
            width: 90%;
            box-shadow: var(--k-shadow-lg);
        }
        
        .modal-custom-header {
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--k-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--k-blue-dark);
            color: white;
            border-radius: 0.75rem 0.75rem 0 0;
        }
        
        .modal-custom-header h4 {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .modal-custom-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .modal-custom-close:hover { opacity: 1; }
        
        .modal-custom-body {
            padding: 1rem 1.25rem;
        }
        
        .form-label {
            font-size: 0.65rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--k-gray-700);
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--k-gray-300);
            border-radius: 0.4rem;
            font-size: 0.7rem;
            transition: all 0.15s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--k-orange);
            box-shadow: 0 0 0 2px var(--k-orange-light);
        }
        
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10001;
            min-width: 240px;
        }
        
        .toast-notification .k-card {
            background: var(--k-blue-dark);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.7rem;
            border-radius: 0.5rem;
        }
        
        .toast-notification .k-card.error {
            background: var(--k-red);
        }
        
        .row-custom {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .row-custom {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }
        
        .text-muted { color: var(--k-gray-500) !important; }
        .text-danger { color: var(--k-red) !important; }
        .text-orange { color: var(--k-orange) !important; }
        .small { font-size: 0.6rem !important; }
        
        .group-list::-webkit-scrollbar,
        .table-container::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        
        .group-list::-webkit-scrollbar-track,
        .table-container::-webkit-scrollbar-track {
            background: var(--k-gray-100);
        }
        
        .group-list::-webkit-scrollbar-thumb,
        .table-container::-webkit-scrollbar-thumb {
            background: var(--k-gray-400);
            border-radius: 4px;
        }
        
        .group-list::-webkit-scrollbar-thumb:hover,
        .table-container::-webkit-scrollbar-thumb:hover {
            background: var(--k-orange);
        }
        
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
        
        /* Divider dengan aksen orange */
        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, var(--k-orange-light), transparent);
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="top-bar">
            <div class="page-title">
                <div style="display: flex; align-items: center;">
                    <div class="logo-icon"><i class="fas fa-address-book"></i></div>
                    <div>
                        <h1>KURMIGO - Contact Groups</h1>
                        <p>Manage your WhatsApp contacts & groups</p>
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
            <div class="row-custom">
                <!-- Left Panel: Groups -->
                <div class="k-card">
                    <div class="k-card-header">
                        <h3><i class="fas fa-layer-group"></i> Groups</h3>
                        <button class="k-btn k-btn-orange k-btn-sm" onclick="openGroupModal()">
                            <i class="fas fa-plus"></i> New
                        </button>
                    </div>
                    <div class="k-card-body">
                        <div class="group-list" id="group-list">
                            @forelse($groups as $group)
                                <div class="group-item {{ $selectedGroup && $selectedGroup->id == $group->id ? 'active' : '' }}" 
                                     data-group-id="{{ $group->id }}"
                                     onclick="selectGroup({{ $group->id }})">
                                    <div class="group-info">
                                        <div class="group-name">{{ $group->name }}</div>
                                        <span class="group-count">{{ $group->contacts_count }} contacts</span>
                                    </div>
                                    <div class="group-actions">
                                        <button class="k-btn k-btn-outline k-btn-sm" data-tooltip="Edit Group" onclick="event.stopPropagation(); editGroup({{ $group->id }}, '{{ addslashes($group->name) }}', '{{ addslashes($group->description) }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="k-btn k-btn-outline k-btn-sm text-danger" data-tooltip="Delete Group" onclick="event.stopPropagation(); deleteGroup({{ $group->id }}, '{{ addslashes($group->name) }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>No groups yet<br>Click "New Group" to start</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Contacts -->
                <div class="k-card">
                    <div class="k-card-header">
                        <h3>
                            <i class="fas fa-users"></i> Contacts
                            @if($selectedGroup)
                                <span class="badge-status orange">{{ $selectedGroup->name }}</span>
                            @endif
                        </h3>
                        @if($selectedGroup)
                            <div class="d-flex gap-1">
                                <button class="k-btn k-btn-outline k-btn-sm" onclick="importContacts()">
                                    <i class="fas fa-upload"></i> <span class="d-none d-md-inline">Import</span>
                                </button>
                                <button class="k-btn k-btn-outline k-btn-sm" onclick="exportContacts()">
                                    <i class="fas fa-download"></i> <span class="d-none d-md-inline">Export</span>
                                </button>
                                <button class="k-btn k-btn-primary k-btn-sm" onclick="openContactModal()">
                                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Add</span>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="k-card-body">
                        @if($selectedGroup)
                            <div class="table-container">
                                <table class="k-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 45px;">No</th>
                                            <th>Name</th>
                                            <th>WhatsApp Number</th>
                                            <th style="width: 60px;">Actions</th>
                                        </thead>
                                    <tbody id="contacts-table-body">
                                        @forelse($contacts as $index => $contact)
                                            <tr>
                                                <td class="text-muted">{{ $index + 1 }}</td>
                                                <td class="group-name">{{ $contact->name ?? '-' }}</td>
                                                <td><code>{{ $contact->number }}</code></td>
                                                <td class="text-nowrap">
                                                    <button class="k-btn k-btn-outline k-btn-sm" data-tooltip="Edit Contact" onclick="editContact({{ $contact->id }}, '{{ $contact->number }}', '{{ addslashes($contact->name) }}')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="k-btn k-btn-outline k-btn-sm text-danger" data-tooltip="Delete Contact" onclick="deleteContact({{ $contact->id }}, '{{ $contact->number }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="empty-state">
                                                    <i class="fas fa-user-plus"></i>
                                                    <p>No contacts yet<br>Click "Add Contact" or "Import" to add</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state" style="padding: 3rem 1rem;">
                                <i class="fas fa-arrow-left" style="color: var(--k-orange);"></i>
                                <p>Select a group from the left panel</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Group -->
    <div id="groupModal" class="modal-custom">
        <div class="modal-custom-content">
            <div class="modal-custom-header">
                <h4 id="groupModalTitle"><i class="fas fa-layer-group me-2"></i>New Group</h4>
                <button class="modal-custom-close" onclick="closeGroupModal()">&times;</button>
            </div>
            <div class="modal-custom-body">
                <form id="groupForm">
                    <input type="hidden" id="group_id">
                    <div class="mb-2">
                        <label class="form-label">Group Name *</label>
                        <input type="text" id="group_name" class="form-control" required placeholder="e.g., Customers, VIP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="group_description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="k-btn k-btn-outline" onclick="closeGroupModal()">Cancel</button>
                        <button type="submit" class="k-btn k-btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Contact -->
    <div id="contactModal" class="modal-custom">
        <div class="modal-custom-content">
            <div class="modal-custom-header">
                <h4 id="contactModalTitle"><i class="fas fa-user-plus me-2"></i>Add Contact</h4>
                <button class="modal-custom-close" onclick="closeContactModal()">&times;</button>
            </div>
            <div class="modal-custom-body">
                <form id="contactForm">
                    <input type="hidden" id="contact_id">
                    <input type="hidden" id="contact_group_id" value="{{ $selectedGroup->id ?? '' }}">
                    <div class="mb-2">
                        <label class="form-label">WhatsApp Number *</label>
                        <input type="text" id="contact_number" class="form-control" required placeholder="628123456789 or 08123456789">
                        <small class="text-muted" style="font-size: 0.55rem;">Format: 628xxx (auto-formatted)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-muted">(Optional)</span></label>
                        <input type="text" id="contact_name" class="form-control" placeholder="Contact name">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="k-btn k-btn-outline" onclick="closeContactModal()">Cancel</button>
                        <button type="submit" class="k-btn k-btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="modal-custom">
        <div class="modal-custom-content">
            <div class="modal-custom-header">
                <h4><i class="fas fa-upload me-2"></i>Import Contacts</h4>
                <button class="modal-custom-close" onclick="closeImportModal()">&times;</button>
            </div>
            <div class="modal-custom-body">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $selectedGroup->id ?? '' }}">
                    <div class="mb-2">
                        <label class="form-label">CSV File *</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required style="padding: 0.3rem 0.5rem;">
                        <small class="text-muted d-block mt-1" style="font-size: 0.55rem;">
                            Format: Column A = Phone Number, Column B = Name (optional)
                        </small>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="k-btn k-btn-outline" onclick="closeImportModal()">Cancel</button>
                        <button type="submit" class="k-btn k-btn-orange">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification" style="display: none;">
        <div class="k-card">
            <i class="fas fa-check-circle me-2"></i>
            <span id="toast-message"></span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            document.getElementById('current-date').textContent = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        });

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastCard = toast.querySelector('.k-card');
            
            toastMessage.textContent = message;
            toastCard.style.background = isError ? '#ef4444' : '#1e3a8a';
            toastCard.style.color = 'white';
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 2500);
        }

        function selectGroup(groupId) {
            window.location.href = '{{ route("contact-groups.index") }}?group_id=' + groupId;
        }

        function openGroupModal(groupId = null, name = '', description = '') {
            const modal = document.getElementById('groupModal');
            modal.classList.add('show');
            
            if (groupId) {
                document.getElementById('groupModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Group';
                document.getElementById('group_id').value = groupId;
                document.getElementById('group_name').value = name;
                document.getElementById('group_description').value = description;
            } else {
                document.getElementById('groupModalTitle').innerHTML = '<i class="fas fa-layer-group me-2"></i>New Group';
                document.getElementById('group_id').value = '';
                document.getElementById('group_name').value = '';
                document.getElementById('group_description').value = '';
            }
        }

        function editGroup(id, name, description) {
            openGroupModal(id, name, description);
        }

        function closeGroupModal() {
            document.getElementById('groupModal').classList.remove('show');
        }

        document.getElementById('groupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('group_id').value;
            const url = id ? '/contact-groups/group/' + id : '/contact-groups/group';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const response = await axios({
                    method: method,
                    url: url,
                    data: {
                        name: document.getElementById('group_name').value,
                        description: document.getElementById('group_description').value
                    }
                });
                
                if (response.data.success) {
                    showToast(id ? 'Group updated' : 'Group created');
                    setTimeout(() => location.reload(), 800);
                }
            } catch (error) {
                showToast(error.response?.data?.errors?.name?.[0] || 'Error saving group', true);
            }
        });

        function deleteGroup(id, name) {
            if (confirm(`Delete group "${name}"? All contacts will be deleted.`)) {
                axios.delete('/contact-groups/group/' + id)
                    .then(response => {
                        if (response.data.success) {
                            showToast('Group deleted');
                            setTimeout(() => location.reload(), 800);
                        }
                    })
                    .catch(() => showToast('Error deleting group', true));
            }
        }

        function openContactModal(id = null, number = '', name = '') {
            const groupId = document.getElementById('contact_group_id').value;
            if (!groupId) {
                showToast('Please select a group first', true);
                return;
            }
            
            const modal = document.getElementById('contactModal');
            modal.classList.add('show');
            
            if (id) {
                document.getElementById('contactModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Contact';
                document.getElementById('contact_id').value = id;
                document.getElementById('contact_number').value = number;
                document.getElementById('contact_name').value = name;
            } else {
                document.getElementById('contactModalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Add Contact';
                document.getElementById('contact_id').value = '';
                document.getElementById('contact_number').value = '';
                document.getElementById('contact_name').value = '';
            }
        }

        function editContact(id, number, name) {
            openContactModal(id, number, name);
        }

        function closeContactModal() {
            document.getElementById('contactModal').classList.remove('show');
        }

        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('contact_id').value;
            const groupId = document.getElementById('contact_group_id').value;
            const url = id ? '/contact-groups/contact/' + id : '/contact-groups/contact';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const response = await axios({
                    method: method,
                    url: url,
                    data: {
                        group_id: groupId,
                        number: document.getElementById('contact_number').value,
                        name: document.getElementById('contact_name').value
                    }
                });
                
                if (response.data.success) {
                    showToast(id ? 'Contact updated' : 'Contact added');
                    setTimeout(() => location.reload(), 800);
                }
            } catch (error) {
                showToast(error.response?.data?.errors?.number?.[0] || 'Error saving contact', true);
            }
        });

        function deleteContact(id, number) {
            if (confirm(`Delete contact "${number}"?`)) {
                axios.delete('/contact-groups/contact/' + id)
                    .then(response => {
                        if (response.data.success) {
                            showToast('Contact deleted');
                            setTimeout(() => location.reload(), 800);
                        }
                    })
                    .catch(() => showToast('Error deleting contact', true));
            }
        }

        function importContacts() {
            const groupId = document.querySelector('#contact_group_id')?.value;
            if (!groupId) {
                showToast('Please select a group first', true);
                return;
            }
            document.getElementById('importModal').classList.add('show');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.remove('show');
        }

        document.getElementById('importForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await axios.post('/contact-groups/import', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                
                if (response.data.success || response.request.responseURL) {
                    showToast('Import successful!');
                    setTimeout(() => location.reload(), 1200);
                }
            } catch (error) {
                showToast('Error importing contacts', true);
            }
        });

        function exportContacts() {
            const groupId = document.querySelector('#contact_group_id')?.value;
            if (groupId) {
                window.location.href = '/contact-groups/export/' + groupId;
            } else {
                showToast('Please select a group first', true);
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-custom')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
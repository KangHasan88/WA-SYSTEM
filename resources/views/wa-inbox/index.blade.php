<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KURMIGO - Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Emoji Picker CSS -->
    <link href="https://cdn.jsdelivr.net/npm/emoji-picker-element@1.18.0/index.css" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { max-width: 100%; overflow-x: hidden; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1e293b; }
        
        /* ================= BLUE DARK + ORANGE + WHITE THEME ================= */
        :root {
            --k-white: #ffffff;
            --k-blue-dark: #1e3a8a;
            --k-blue-darker: #172554;
            --k-blue-light: #dbeafe;
            --k-blue-soft: #eff6ff;
            --k-orange: #ea580c;
            --k-orange-dark: #c2410c;
            --k-orange-light: #fed7aa;
            --k-orange-soft: #fff7ed;
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
        
        .unread-badge {
            background: var(--k-orange);
            color: white;
            border-radius: 20px;
            padding: 0.2rem 0.5rem;
            font-size: 0.6rem;
            font-weight: 600;
            margin-left: 0.5rem;
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
            padding: 0.75rem 1.25rem; 
            border-bottom: 1px solid var(--k-gray-200); 
            background: var(--k-white);
            position: relative;
        }
        
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
        
        .inbox-container {
            display: flex;
            height: calc(100vh - 120px);
            min-height: 500px;
            min-width: 0;
        }
        
        .conversations-list {
            width: 350px;
            border-right: 1px solid var(--k-gray-200);
            overflow-y: auto;
            background: var(--k-white);
        }
        
        .conversation-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--k-gray-100);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .conversation-item:hover { background: var(--k-orange-soft); }
        .conversation-item.active { background: var(--k-blue-soft); border-left: 3px solid var(--k-orange); }
        
        .conversation-avatar {
            width: 48px;
            height: 48px;
            background: var(--k-orange-soft);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--k-orange);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .conversation-info { flex: 1; min-width: 0; }
        .conversation-number { font-weight: 600; font-size: 0.8rem; color: var(--k-gray-800); margin-bottom: 0.2rem; display: flex; justify-content: space-between; flex-wrap: wrap; align-items: center; }
        .conversation-preview { font-size: 0.7rem; color: var(--k-gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conversation-time { font-size: 0.6rem; color: var(--k-gray-400); }
        .unread-indicator { width: 10px; height: 10px; background: var(--k-orange); border-radius: 50%; display: inline-block; box-shadow: 0 0 0 2px var(--k-orange-light); }
        
        /* Status badge styles */
        .badge-status {
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .badge-status.info { background: #d1fae5; color: #065f46; }
        .badge-status.warning { background: #fed7aa; color: #c2410c; }
        .badge-status.success { background: #d1fae5; color: #065f46; }
        .badge-status.danger { background: #fee2e2; color: #dc2626; }
        
        /* Sales Panel */
        .sales-panel { border-top: 1px solid var(--k-gray-200); background: var(--k-white); padding: 0.75rem 1rem; margin-top: 0; }
        .form-label { font-size: 0.6rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--k-gray-600); }
        .form-control-sm, .form-select-sm { font-size: 0.7rem; border-radius: 0.5rem; }
        
        .chat-area { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #f8fafc; }
        .chat-header { padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--k-gray-200); background: var(--k-white); }
        .chat-header .chat-number { font-weight: 600; font-size: 0.9rem; color: var(--k-blue-dark); }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; }
        .message-item { display: flex; flex-direction: column; max-width: 70%; }
        .message-item.incoming { align-items: flex-start; }
        .message-item.outgoing { align-items: flex-end; margin-left: auto; }
        .message-bubble { padding: 0.5rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; line-height: 1.4; word-break: break-word; }
        .message-item.incoming .message-bubble { background: var(--k-white); border: 1px solid var(--k-gray-200); color: var(--k-gray-700); border-bottom-left-radius: 0.25rem; }
        .message-item.outgoing .message-bubble { background: var(--k-blue-dark); color: white; border-bottom-right-radius: 0.25rem; }
        .message-time { font-size: 0.55rem; color: var(--k-gray-400); margin-top: 0.25rem; }
        
        /* Media Preview Styles */
        .media-preview { margin-top: 8px; }
        .media-preview img { border-radius: 8px; cursor: pointer; transition: transform 0.2s; max-width: 200px; max-height: 200px; }
        .media-preview img:hover { transform: scale(1.02); }
        .media-preview video { max-width: 250px; max-height: 200px; border-radius: 8px; }
        .media-preview audio { width: 250px; }
        .media-preview .btn-outline-primary { font-size: 0.7rem; padding: 0.3rem 0.6rem; }
        
        /* Image Modal */
        .image-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; align-items: center; justify-content: center; }
        .image-modal.show { display: flex; }
        .image-modal-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
        .image-modal-container { position: relative; max-width: 90%; max-height: 90%; z-index: 10001; }
        .image-modal-container img { max-width: 100%; max-height: 90vh; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .image-modal-close { position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 30px; cursor: pointer; padding: 5px 10px; }
        
        /* File Attachment Button */
        .file-attachment-btn {
            background: transparent;
            border: 1px solid var(--k-gray-300);
            border-radius: 2rem;
            padding: 0.6rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--k-gray-500);
        }
        .file-attachment-btn:hover {
            background: var(--k-orange-soft);
            border-color: var(--k-orange);
            color: var(--k-orange);
        }
        #selected-file-name {
            font-size: 0.65rem;
            color: var(--k-gray-500);
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Chat Input Area dengan Emoji Button */
        .chat-input-area {
            padding: 1rem;
            border-top: 1px solid var(--k-gray-200);
            background: var(--k-white);
            display: flex;
            gap: 0.5rem;
            position: relative;
            flex-wrap: wrap;
        }
        
        .chat-input-wrapper {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .chat-input-wrapper input {
            flex: 1;
            padding: 0.6rem 2.5rem 0.6rem 1rem;
            border: 1px solid var(--k-gray-300);
            border-radius: 2rem;
            font-size: 0.75rem;
            outline: none;
            transition: all 0.2s;
            width: 100%;
        }
        
        .chat-input-wrapper input:focus {
            border-color: var(--k-orange);
            box-shadow: 0 0 0 2px var(--k-orange-light);
        }
        
        .emoji-btn-chat {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0;
            color: var(--k-gray-500);
            transition: all 0.2s;
            z-index: 10;
        }
        
        .emoji-btn-chat:hover {
            color: var(--k-orange);
            transform: translateY(-50%) scale(1.1);
        }
        
        .chat-input-area button {
            padding: 0.6rem 1.2rem;
            border-radius: 2rem;
            background: var(--k-orange);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .chat-input-area button:hover {
            background: var(--k-orange-dark);
            transform: translateY(-1px);
            box-shadow: var(--k-shadow-orange);
        }
        
        /* Emoji Picker Container */
        .emoji-picker-chat-container {
            position: absolute;
            bottom: 100%;
            left: 0;
            margin-bottom: 10px;
            z-index: 1000;
            display: none;
        }
        
        .emoji-picker-chat-container.show {
            display: block;
        }
        
        emoji-picker {
            --emoji-size: 1.2rem;
            --num-columns: 8;
            --background: var(--k-white);
            --border-color: var(--k-gray-200);
            --category-emoji-size: 1rem;
            --indicator-color: var(--k-orange);
            --input-border-color: var(--k-gray-300);
            --input-border-radius: 0.5rem;
            --font-size: 0.7rem;
            width: 320px;
            height: 400px;
            border-radius: 0.75rem;
            box-shadow: var(--k-shadow-lg);
            border: 1px solid var(--k-gray-200);
        }
        
        .empty-chat { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--k-gray-400); text-align: center; }
        .empty-chat i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; color: var(--k-orange); }
        
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

        @media (max-width: 768px) {
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

            .inbox-container {
                display: block;
                height: auto;
                min-height: 0;
            }

            .conversations-list {
                width: 100%;
                max-height: 420px;
                border-right: 0;
                border-bottom: 1px solid var(--k-gray-200);
            }

            .chat-area {
                min-height: 360px;
            }

            .empty-chat {
                min-height: 300px;
                padding: 2rem 1rem;
            }

            .message-item {
                max-width: 85%;
            }
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .message-item { animation: fadeIn 0.2s ease; }
        
        .message-time-sending { color: var(--k-orange); }
        .message-time-success { color: var(--k-green); }
        .message-time-error { color: var(--k-red); }
        
        .chat-number small { font-size: 0.6rem; font-weight: normal; }
        
        /* Scrollbar */
        .conversations-list::-webkit-scrollbar, .chat-messages::-webkit-scrollbar { width: 6px; height: 6px; }
        .conversations-list::-webkit-scrollbar-track, .chat-messages::-webkit-scrollbar-track { background: var(--k-gray-100); }
        .conversations-list::-webkit-scrollbar-thumb, .chat-messages::-webkit-scrollbar-thumb { background: var(--k-gray-400); border-radius: 3px; }
        .conversations-list::-webkit-scrollbar-thumb:hover, .chat-messages::-webkit-scrollbar-thumb:hover { background: var(--k-orange); }
        
        /* Modal Popup Styles */
        .modal-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10002;
            justify-content: center;
            align-items: center;
        }
        
        .modal-popup.show {
            display: flex;
        }
        
        .modal-popup-content {
            background: white;
            border-radius: 1rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: modalFadeIn 0.2s ease;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .modal-popup-header {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: var(--k-blue-dark);
            color: white;
            border-radius: 1rem 1rem 0 0;
        }
        
        .modal-popup-header h4 {
            margin: 0;
            font-size: 1rem;
        }
        
        .modal-popup-body {
            padding: 1.5rem;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Toast notification */
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
    </style>
</head>
<body>
    <div class="app-container">
        <div class="top-bar">
            <div class="page-title">
                <div style="display: flex; align-items: center;">
                    <div class="logo-icon"><i class="fas fa-inbox"></i></div>
                    <div><h1>KURMIGO - Inbox</h1><p>Customer messages & replies</p></div>
                </div>
            </div>
            <div class="top-bar-right">
                <a href="{{ route('dashboard.index') }}" class="nav-link-custom"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="{{ route('wa-blast.index') }}" class="nav-link-custom"><i class="fas fa-paper-plane"></i> WA Blast</a>
                <a href="{{ route('contact-groups.index') }}" class="nav-link-custom"><i class="fas fa-address-book"></i> Contact Groups</a>
                <a href="{{ route('wa-schedule.index') }}" class="nav-link-custom"><i class="fas fa-calendar-alt"></i> Schedules</a>
                <div class="date-display"><i class="fas fa-calendar"></i><span id="current-date"></span></div>
            </div>
        </div>

        <div class="main-container">
            <div class="k-card">
                <div class="k-card-header">
                    <h3><i class="fas fa-inbox"></i> Pesan Masuk
                        @if($unreadCount > 0)
                            <span class="unread-badge">{{ $unreadCount }} belum dibaca</span>
                        @endif
                    </h3>
                </div>
                <div class="k-card-body p-0">
                    <div class="inbox-container">
                        <div class="conversations-list" id="conversations-list">
                            @forelse($conversations as $conv)
                                <div class="conversation-item" data-number="{{ $conv->from_number }}" onclick="loadConversation('{{ $conv->from_number }}')">
                                    <div class="conversation-avatar"><i class="fas fa-user"></i></div>
                                    <div class="conversation-info">
                                        <div class="conversation-number">
                                            <span>
                                                <strong>{{ $conv->display_name ?? $conv->from_number }}</strong>
                                                @if($conv->lead_status && $conv->lead_status != 'new')
                                                    <span class="badge-status {{ $conv->lead_status_color }}" style="margin-left: 0.5rem; font-size: 0.55rem; padding: 0.15rem 0.4rem;">
                                                        {{ $conv->lead_status_icon }} {{ $conv->lead_status_label }}
                                                    </span>
                                                @endif
                                            </span>
                                            @if($conv->unread_count > 0)<span class="unread-indicator"></span>@endif
                                        </div>
                                        <div class="conversation-preview small">{{ $conv->from_number }}</div>
                                        <div class="conversation-time">
                                            @php
                                                $timeValue = $conv->received_at ?? $conv->last_message_at ?? $conv->created_at ?? null;
                                            @endphp
                                            @if($timeValue)
                                                {{ \Carbon\Carbon::parse($timeValue)->diffForHumans() }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2"></i><p class="small">Belum ada pesan masuk</p></div>
                            @endforelse
                        </div>

                        <div class="chat-area" id="chat-area">
                            <div class="empty-chat"><i class="fas fa-comment-dots"></i><p>Pilih percakapan dari sebelah kiri</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup untuk Status & Catatan -->
    <div id="statusModal" class="modal-popup">
        <div class="modal-popup-content">
            <div class="modal-popup-header" id="modal-header">
                <h4 id="modal-title"><i class="fas fa-check-circle"></i> Berhasil</h4>
            </div>
            <div class="modal-popup-body">
                <div style="font-size: 3rem; margin-bottom: 1rem;" id="modal-icon">✅</div>
                <p id="modal-message" style="font-size: 0.9rem; color: #1e293b; margin-bottom: 1.5rem;">Status berhasil diubah!</p>
                <button onclick="closeStatusModal()" class="k-btn k-btn-primary" style="background: var(--k-orange); border: none; padding: 0.5rem 1.5rem; border-radius: 2rem; cursor: pointer;">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- Emoji Picker JS - Module -->
    <script type="module">
        import { Picker } from 'https://cdn.jsdelivr.net/npm/emoji-picker-element@1.18.0/index.js';
        
        let chatPicker = null;
        
        window.initChatEmojiPicker = function(inputElement, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            if (chatPicker) {
                chatPicker.removeEventListener('emoji-click', window.emojiClickHandler);
                chatPicker = null;
            }
            
            chatPicker = new Picker({
                locale: 'id',
                categories: ['smileys-emotion', 'people-body', 'animals-nature', 'food-drink', 'travel-places', 'activities', 'objects', 'symbols', 'flags'],
                emojiSize: '1.2rem',
                emojiVersion: '14.0'
            });
            
            container.innerHTML = '';
            container.appendChild(chatPicker);
            
            window.emojiClickHandler = (event) => {
                const emoji = event.detail.unicode;
                if (inputElement) {
                    const start = inputElement.selectionStart;
                    const end = inputElement.selectionEnd;
                    const text = inputElement.value;
                    const newText = text.substring(0, start) + emoji + text.substring(end);
                    inputElement.value = newText;
                    inputElement.selectionStart = inputElement.selectionEnd = start + emoji.length;
                    inputElement.focus();
                    
                    const inputEvent = new Event('input', { bubbles: true });
                    inputElement.dispatchEvent(inputEvent);
                }
                container.classList.remove('show');
            };
            
            chatPicker.addEventListener('emoji-click', window.emojiClickHandler);
        };
    </script>
    <script>
        // ========== MAIN FUNCTIONS ==========
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            document.getElementById('current-date').textContent = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            
            const lastActiveNumber = sessionStorage.getItem('lastActiveConversation');
            if (lastActiveNumber) {
                setTimeout(function() {
                    loadConversation(lastActiveNumber);
                }, 100);
            }
        });

        let currentNumber = null;
        let currentMessageId = null;
        let isRefreshing = false;
        let selectedFile = null;

        // ========== MODAL POPUP FUNCTIONS ==========
        function showModal(title, icon, message, color = '#10b981') {
            const modal = document.getElementById('statusModal');
            const modalTitle = document.getElementById('modal-title');
            const modalIcon = document.getElementById('modal-icon');
            const modalMessage = document.getElementById('modal-message');
            const modalHeader = document.getElementById('modal-header');
            
            if (modalTitle) modalTitle.innerHTML = `<i class="fas ${icon === '✅' ? 'fa-check-circle' : (icon === '❌' ? 'fa-times-circle' : 'fa-info-circle')}"></i> ${title}`;
            if (modalIcon) modalIcon.innerHTML = icon;
            if (modalMessage) modalMessage.innerHTML = message;
            if (modalHeader) modalHeader.style.background = color;
            
            modal.style.display = 'flex';
            modal.classList.add('show');
            
            // Auto close setelah 3 detik
            setTimeout(() => {
                closeStatusModal();
            }, 3000);
        }

        function closeStatusModal() {
            const modal = document.getElementById('statusModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        // Tutup modal jika klik di luar
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('statusModal');
            if (e.target === modal) {
                closeStatusModal();
            }
        });

        function showToast(message, isError = false) {
            let toast = document.getElementById('toast-notification');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast-notification';
                toast.className = 'toast-notification';
                toast.innerHTML = '<div class="k-card"><i class="fas fa-check-circle me-2"></i><span id="toast-message"></span></div>';
                document.body.appendChild(toast);
            }
            
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

        // Image modal functions
        function openImageModal(imageUrl) {
            let modal = document.getElementById('image-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'image-modal';
                modal.className = 'image-modal';
                modal.innerHTML = `
                    <div class="image-modal-overlay" onclick="closeImageModal()"></div>
                    <div class="image-modal-container">
                        <button class="image-modal-close" onclick="closeImageModal()">&times;</button>
                        <img id="image-modal-img" src="" alt="Preview">
                    </div>
                `;
                document.body.appendChild(modal);
            }
            
            document.getElementById('image-modal-img').src = imageUrl;
            modal.classList.add('show');
        }

        function closeImageModal() {
            const modal = document.getElementById('image-modal');
            if (modal) {
                modal.classList.remove('show');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        // Handle file selection
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (file.size > 16 * 1024 * 1024) {
                showModal('Error', '❌', 'File terlalu besar. Maksimal 16MB', '#ef4444');
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
            if (!allowedTypes.includes(file.type) && !file.type.startsWith('image/')) {
                showModal('Error', '❌', 'Tipe file tidak didukung', '#ef4444');
                return;
            }
            
            selectedFile = file;
            const fileNameSpan = document.getElementById('selected-file-name');
            if (fileNameSpan) {
                fileNameSpan.textContent = file.name;
                fileNameSpan.title = file.name;
            }
        }

        // Convert file to base64
        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => {
                    const base64 = reader.result.split(',')[1];
                    resolve(base64);
                };
                reader.onerror = error => reject(error);
            });
        }

        // ========== SALES PIPELINE FUNCTIONS ==========
        async function loadLeadData(number) {
            try {
                const response = await axios.get(`/wa-inbox/api/messages/${number}`);
                const messages = response.data.messages.data;
                if (messages.length > 0) {
                    const latestMsg = messages[0];
                    currentMessageId = latestMsg.id;
                    const statusSelect = document.getElementById('lead-status-select');
                    const notesTextarea = document.getElementById('lead-notes');
                    const followUpInput = document.getElementById('follow-up-date');
                    
                    if (statusSelect) statusSelect.value = latestMsg.lead_status || 'new';
                    if (notesTextarea) notesTextarea.value = latestMsg.lead_notes || '';
                    if (followUpInput && latestMsg.follow_up_date) {
                        const followUpDate = new Date(latestMsg.follow_up_date);
                        followUpInput.value = followUpDate.toISOString().slice(0, 16);
                    }
                }
            } catch (error) {
                console.error('Error loading lead data:', error);
            }
        }

        // Update badge di conversation list
        function updateConversationBadge(number, newStatus) {
            const conversationItems = document.querySelectorAll('.conversation-item');
            for (let item of conversationItems) {
                if (item.dataset.number === number) {
                    const conversationNumberSpan = item.querySelector('.conversation-number span');
                    const existingBadge = item.querySelector('.badge-status');
                    
                    if (existingBadge) {
                        existingBadge.remove();
                    }
                    
                    if (newStatus !== 'new') {
                        let statusIcon = '';
                        let statusLabel = '';
                        let statusColor = '';
                        
                        if (newStatus === 'hot') {
                            statusIcon = '🔥';
                            statusLabel = 'Hot Lead';
                            statusColor = 'warning';
                        } else if (newStatus === 'deal') {
                            statusIcon = '✅';
                            statusLabel = 'Deal Won';
                            statusColor = 'success';
                        } else if (newStatus === 'lost') {
                            statusIcon = '❌';
                            statusLabel = 'Deal Lost';
                            statusColor = 'danger';
                        }
                        
                        const badge = document.createElement('span');
                        badge.className = `badge-status ${statusColor}`;
                        badge.style.marginLeft = '0.5rem';
                        badge.style.fontSize = '0.55rem';
                        badge.style.padding = '0.15rem 0.4rem';
                        badge.innerHTML = `${statusIcon} ${statusLabel}`;
                        
                        conversationNumberSpan.appendChild(badge);
                    }
                    break;
                }
            }
        }

        async function loadConversation(number) {
            currentNumber = number;
            sessionStorage.setItem('lastActiveConversation', number);
            
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.number === number) {
                    item.classList.add('active');
                }
            });
            
            const chatArea = document.getElementById('chat-area');
            chatArea.innerHTML = `
                <div class="chat-header">
                    <div class="chat-number">Loading...</div>
                </div>
                <div class="chat-messages">
                    <div class="text-center text-muted py-5">
                        <div class="spinner-border text-primary spinner-border-sm"></div> Memuat pesan...
                    </div>
                </div>
                <div class="chat-input-area">
                    <div class="chat-input-wrapper" style="flex: 1;">
                        <input type="text" id="reply-message" placeholder="Ketik balasan... (klik 😊 untuk emoji)" onkeypress="if(event.key === 'Enter') sendReply()">
                        <button type="button" class="emoji-btn-chat" id="emoji-chat-btn">
                            <i class="far fa-smile-wink"></i>
                        </button>
                        <div class="emoji-picker-chat-container" id="emoji-picker-chat"></div>
                    </div>
                    <label class="file-attachment-btn" style="display: inline-flex; align-items: center; gap: 0.3rem; margin: 0;">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" id="file-input" style="display: none;" onchange="handleFileSelect(event)">
                    </label>
                    <span id="selected-file-name" style="font-size: 0.65rem; color: #64748b; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                    <button onclick="sendReply()"><i class="fas fa-paper-plane"></i> Kirim</button>
                </div>
            `;
            
            // Setup emoji picker
            const messageInput = document.getElementById('reply-message');
            const emojiBtn = document.getElementById('emoji-chat-btn');
            const pickerContainer = document.getElementById('emoji-picker-chat');
            
            const fileInput = document.getElementById('file-input');
            if (fileInput) {
                fileInput.removeEventListener('change', handleFileSelect);
                fileInput.addEventListener('change', handleFileSelect);
            }
            
            if (messageInput && emojiBtn && pickerContainer) {
                if (typeof window.initChatEmojiPicker === 'function') {
                    window.initChatEmojiPicker(messageInput, 'emoji-picker-chat');
                }
                
                emojiBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    pickerContainer.classList.toggle('show');
                });
                
                document.addEventListener('click', (e) => {
                    if (pickerContainer && !pickerContainer.contains(e.target) && e.target !== emojiBtn) {
                        pickerContainer.classList.remove('show');
                    }
                });
            }
            
            try {
                const response = await axios.get(`/wa-inbox/api/messages/${number}`);
                const messages = response.data.messages.data;
                const displayName = response.data.display_name || number;
                
                let messagesHtml = '';
                messages.forEach(msg => {
                    const time = msg.received_at ? new Date(msg.received_at).toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }) : '-';
                    const isOutgoing = msg.direction === 'outgoing' || msg.from_name === 'Admin';
                    const messageClass = isOutgoing ? 'outgoing' : 'incoming';
                    
                    let messageContent = `<div class="message-bubble">`;
                    
                    if (msg.message && msg.message.trim()) {
                        messageContent += `<div class="message-text">${escapeHtml(msg.message)}</div>`;
                    }
                    
                    if (msg.has_media && msg.media_url) {
                        const mediaCategory = msg.media_category || 'other';
                        const fileIcon = msg.file_icon || 'fa-file';
                        const fileName = msg.media_filename || 'file';
                        const fileSize = msg.formatted_file_size || '';
                        
                        switch(mediaCategory) {
                            case 'image':
                                messageContent += `<div class="media-preview image-preview mt-2"><img src="${msg.media_url}" alt="Image" class="img-fluid rounded" onclick="openImageModal('${msg.media_url}')"></div>`;
                                break;
                            case 'video':
                                messageContent += `<div class="media-preview video-preview mt-2"><video controls><source src="${msg.media_url}" type="${msg.media_mime || 'video/mp4'}"></video><div class="small text-muted mt-1"><i class="fas ${fileIcon}"></i> ${escapeHtml(fileName)} ${fileSize ? '(' + fileSize + ')' : ''}</div></div>`;
                                break;
                            case 'audio':
                                messageContent += `<div class="media-preview audio-preview mt-2"><audio controls><source src="${msg.media_url}" type="${msg.media_mime || 'audio/mpeg'}"></audio><div class="small text-muted mt-1"><i class="fas ${fileIcon}"></i> ${escapeHtml(fileName)} ${fileSize ? '(' + fileSize + ')' : ''}</div></div>`;
                                break;
                            default:
                                messageContent += `<div class="media-preview document-preview mt-2"><a href="/wa-inbox/media/download/${msg.id}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas ${fileIcon} me-2"></i>${escapeHtml(fileName)}${fileSize ? '<span class="badge bg-secondary ms-2">' + fileSize + '</span>' : ''}</a></div>`;
                                break;
                        }
                    }
                    
                    messageContent += `</div>`;
                    messageContent += `<div class="message-time">${time}</div>`;
                    
                    messagesHtml += `<div class="message-item ${messageClass}" data-message-id="${msg.id}">${messageContent}</div>`;
                });
                
                if (messages.length === 0) {
                    messagesHtml = '<div class="text-center text-muted py-5"><i class="fas fa-comment"></i><p>Belum ada pesan dari nomor ini</p></div>';
                }
                
                chatArea.innerHTML = `
                    <div class="chat-header">
                        <div class="chat-number">
                            <strong>${escapeHtml(displayName)}</strong>
                            <small class="text-muted d-block">${escapeHtml(number)}</small>
                        </div>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        ${messagesHtml}
                    </div>
                    <div class="chat-input-area">
                        <div class="chat-input-wrapper" style="flex: 1;">
                            <input type="text" id="reply-message" placeholder="Ketik balasan... (klik 😊 untuk emoji)" onkeypress="if(event.key === 'Enter') sendReply()">
                            <button type="button" class="emoji-btn-chat" id="emoji-chat-btn">
                                <i class="far fa-smile-wink"></i>
                            </button>
                            <div class="emoji-picker-chat-container" id="emoji-picker-chat"></div>
                        </div>
                        <label class="file-attachment-btn" style="display: inline-flex; align-items: center; gap: 0.3rem; margin: 0; cursor: pointer;">
                            <i class="fas fa-paperclip"></i>
                            <input type="file" id="file-input" style="display: none;" onchange="handleFileSelect(event)">
                        </label>
                        <span id="selected-file-name" style="font-size: 0.65rem; color: #64748b; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                        <button onclick="sendReply()"><i class="fas fa-paper-plane"></i> Kirim</button>
                    </div>
                    <div class="sales-panel">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">STATUS LEAD</label>
                                <select id="lead-status-select" class="form-select form-select-sm">
                                    <option value="new">🆕 New Lead</option>
                                    <option value="hot">🔥 Hot Lead</option>
                                    <option value="deal">✅ Deal Won</option>
                                    <option value="lost">❌ Deal Lost</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">FOLLOW UP DATE</label>
                                <input type="datetime-local" id="follow-up-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button id="set-follow-up-btn" class="k-btn k-btn-primary k-btn-sm w-100" style="font-size: 0.65rem; background: var(--k-orange); color: white; border: none; border-radius: 2rem; padding: 0.35rem 0.5rem;">
                                    <i class="fas fa-bell"></i> Set Reminder
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label">CATATAN INTERNAL</label>
                            <textarea id="lead-notes" rows="2" class="form-control" style="font-size: 0.7rem;" placeholder="Catatan untuk tim sales..."></textarea>
                            <div class="text-end mt-1">
                                <button id="save-notes-btn" class="k-btn k-btn-outline k-btn-sm" style="font-size: 0.6rem; background: transparent; border: 1px solid var(--k-gray-300); border-radius: 2rem; padding: 0.25rem 0.75rem;">Simpan Catatan</button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Setup emoji picker again
                const newMessageInput = document.getElementById('reply-message');
                const newEmojiBtn = document.getElementById('emoji-chat-btn');
                const newPickerContainer = document.getElementById('emoji-picker-chat');
                
                const newFileInput = document.getElementById('file-input');
                if (newFileInput) {
                    newFileInput.removeEventListener('change', handleFileSelect);
                    newFileInput.addEventListener('change', handleFileSelect);
                }
                
                if (newMessageInput && newEmojiBtn && newPickerContainer) {
                    if (typeof window.initChatEmojiPicker === 'function') {
                        window.initChatEmojiPicker(newMessageInput, 'emoji-picker-chat');
                    }
                    
                    newEmojiBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        newPickerContainer.classList.toggle('show');
                    });
                }
                
                // Setup sales panel events
                const statusSelect = document.getElementById('lead-status-select');
                const saveNotesBtn = document.getElementById('save-notes-btn');
                const setFollowUpBtn = document.getElementById('set-follow-up-btn');
                
                if (statusSelect) {
                    statusSelect.addEventListener('change', async function() {
                        const newStatus = this.value;
                        const selectedOption = this.options[this.selectedIndex];
                        const statusText = selectedOption.textContent;
                        
                        if (!currentMessageId) {
                            showModal('Error', '❌', 'ID pesan tidak ditemukan!', '#ef4444');
                            return;
                        }
                        
                        try {
                            const response = await axios.post(`/wa-inbox/api/lead-status/${currentMessageId}`, { 
                                lead_status: newStatus 
                            });
                            
                            if (response.data.success) {
                                let icon = '';
                                let title = '';
                                if (newStatus === 'hot') { 
                                    icon = '🔥'; 
                                    title = 'Hot Lead';
                                } else if (newStatus === 'deal') { 
                                    icon = '✅'; 
                                    title = 'Deal Won';
                                } else if (newStatus === 'lost') { 
                                    icon = '❌'; 
                                    title = 'Deal Lost';
                                } else { 
                                    icon = '🆕'; 
                                    title = 'New Lead';
                                }
                                
                                showModal('Status Berhasil Diubah', icon, `Status lead berhasil diubah menjadi ${title}!`, '#10b981');
                                
                                updateConversationBadge(currentNumber, newStatus);
                                await updateConversationPreview();
                            } else {
                                showModal('Gagal', '❌', 'Gagal mengubah status lead!', '#ef4444');
                            }
                        } catch (error) {
                            console.error('Error updating status:', error);
                            showModal('Error', '❌', error.response?.data?.error || error.message, '#ef4444');
                        }
                    });
                }
                
                if (saveNotesBtn) {
                    saveNotesBtn.addEventListener('click', async function() {
                        const notes = document.getElementById('lead-notes').value;
                        if (!currentMessageId) {
                            showModal('Error', '❌', 'ID pesan tidak ditemukan!', '#ef4444');
                            return;
                        }
                        
                        try {
                            const response = await axios.post(`/wa-inbox/api/lead-notes/${currentMessageId}`, { 
                                lead_notes: notes 
                            });
                            
                            if (response.data.success) {
                                showModal('Berhasil', '✅', 'Catatan berhasil disimpan!', '#10b981');
                            } else {
                                showModal('Gagal', '❌', 'Gagal menyimpan catatan!', '#ef4444');
                            }
                        } catch (error) {
                            console.error('Error saving notes:', error);
                            showModal('Error', '❌', error.response?.data?.error || error.message, '#ef4444');
                        }
                    });
                }
                
                if (setFollowUpBtn) {
                    setFollowUpBtn.addEventListener('click', async function() {
                        const followUpDate = document.getElementById('follow-up-date').value;
                        const notes = document.getElementById('lead-notes').value;
                        
                        if (!followUpDate) { 
                            showModal('Perhatian', '⚠️', 'Pilih tanggal follow up terlebih dahulu!', '#ea580c');
                            return; 
                        }
                        
                        if (!currentMessageId) {
                            showModal('Error', '❌', 'ID pesan tidak ditemukan!', '#ef4444');
                            return;
                        }
                        
                        const formattedDate = new Date(followUpDate).toLocaleString('id-ID');
                        
                        try {
                            const response = await axios.post(`/wa-inbox/api/follow-up/${currentMessageId}`, { 
                                follow_up_date: followUpDate, 
                                reminder_note: notes 
                            });
                            
                            if (response.data.success) {
                                showModal('Jadwal Tersimpan', '📅', `Follow up dijadwalkan pada ${formattedDate}`, '#10b981');
                            } else {
                                showModal('Gagal', '❌', 'Gagal menjadwalkan follow up!', '#ef4444');
                            }
                        } catch (error) {
                            console.error('Error setting follow up:', error);
                            showModal('Error', '❌', error.response?.data?.error || error.message, '#ef4444');
                        }
                    });
                }
                
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
                
                document.getElementById('reply-message')?.focus();
                await loadLeadData(number);
                await axios.post(`/wa-inbox/api/mark-read/${number}`);
                
            } catch (error) {
                console.error('Error loading messages:', error);
                chatArea.innerHTML = `<div class="empty-chat"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i><p>Gagal memuat pesan</p></div>`;
            }
        }
        
        async function sendReply() {
            const messageInput = document.getElementById('reply-message');
            const message = messageInput?.value.trim() || '';
            const file = selectedFile;
            
            if (!message && !file) {
                showModal('Perhatian', '⚠️', 'Pesan atau file tidak boleh kosong!', '#ea580c');
                return;
            }
            
            if (!currentNumber) return;
            
            messageInput.disabled = true;
            const sendBtn = document.querySelector('.chat-input-area button:last-child');
            if (sendBtn) sendBtn.disabled = true;
            
            const messagesContainer = document.getElementById('chat-messages');
            const time = new Date().toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' });
            const messageId = 'temp-msg-' + Date.now();
            
            let outgoingMessageHtml = `
                <div class="message-item outgoing" id="${messageId}">
                    <div class="message-bubble">
            `;
            
            if (message) {
                outgoingMessageHtml += `<div class="message-text">${escapeHtml(message)}</div>`;
            }
            
            if (file) {
                const fileIcon = file.type.startsWith('image/') ? 'fa-image' : (file.type === 'application/pdf' ? 'fa-file-pdf' : 'fa-file');
                const fileName = file.name;
                outgoingMessageHtml += `
                    <div class="media-preview mt-2">
                        <i class="fas ${fileIcon} me-1"></i>
                        <span class="small">${escapeHtml(fileName)}</span>
                        <span class="badge bg-secondary ms-1">Mengirim...</span>
                    </div>
                `;
            }
            
            outgoingMessageHtml += `
                    </div>
                    <div class="message-time message-time-sending">
                        <i class="fas fa-spinner fa-spin"></i> Mengirim...
                    </div>
                </div>
            `;
            
            messagesContainer.insertAdjacentHTML('beforeend', outgoingMessageHtml);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            try {
                let payload = { message: message };
                
                if (file) {
                    const base64 = await fileToBase64(file);
                    payload.media_base64 = base64;
                    payload.media_mime = file.type;
                    payload.media_filename = file.name;
                }
                
                const response = await axios.post(`/wa-inbox/api/reply/${currentNumber}`, payload);
                
                const tempMsg = document.getElementById(messageId);
                if (tempMsg) {
                    if (response.data.success) {
                        tempMsg.querySelector('.message-time').innerHTML = `<i class="fas fa-check-circle"></i> ${time} • Terkirim`;
                        tempMsg.querySelector('.message-time').classList.remove('message-time-sending');
                        tempMsg.querySelector('.message-time').classList.add('message-time-success');
                        messageInput.value = '';
                        selectedFile = null;
                        const fileNameSpan = document.getElementById('selected-file-name');
                        if (fileNameSpan) fileNameSpan.textContent = '';
                        const fileInput = document.getElementById('file-input');
                        if (fileInput) fileInput.value = '';
                        tempMsg.removeAttribute('id');
                        updateConversationPreview();
                        await refreshCurrentMessages();
                        
                        setTimeout(() => {
                            if (tempMsg) tempMsg.querySelector('.message-time').style.opacity = '0.7';
                        }, 3000);
                        showModal('Berhasil', '✅', 'Pesan berhasil terkirim!', '#10b981');
                    } else {
                        tempMsg.querySelector('.message-bubble').style.background = '#fee2e2';
                        tempMsg.querySelector('.message-bubble').style.color = '#dc2626';
                        tempMsg.querySelector('.message-time').innerHTML = `<i class="fas fa-exclamation-circle"></i> ${time} • Gagal: ${response.data.error || 'Unknown error'}`;
                        tempMsg.querySelector('.message-time').classList.remove('message-time-sending');
                        tempMsg.querySelector('.message-time').classList.add('message-time-error');
                        showModal('Gagal', '❌', 'Gagal mengirim pesan!', '#ef4444');
                    }
                }
            } catch (error) {
                const tempMsg = document.getElementById(messageId);
                if (tempMsg) {
                    tempMsg.querySelector('.message-bubble').style.background = '#fee2e2';
                    tempMsg.querySelector('.message-bubble').style.color = '#dc2626';
                    tempMsg.querySelector('.message-time').innerHTML = `<i class="fas fa-exclamation-circle"></i> ${time} • Gagal: ${error.response?.data?.error || error.message}`;
                    tempMsg.querySelector('.message-time').classList.remove('message-time-sending');
                    tempMsg.querySelector('.message-time').classList.add('message-time-error');
                }
                showModal('Error', '❌', error.response?.data?.error || error.message, '#ef4444');
                console.error('Error sending reply:', error);
            } finally {
                messageInput.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                messageInput.focus();
            }
        }
        
        async function refreshCurrentMessages() {
            if (!currentNumber) return;
            
            try {
                const response = await axios.get(`/wa-inbox/api/messages/${currentNumber}`);
                const messages = response.data.messages.data;
                const displayName = response.data.display_name || currentNumber;
                
                let messagesHtml = '';
                messages.forEach(msg => {
                    const time = msg.received_at ? new Date(msg.received_at).toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }) : '-';
                    const isOutgoing = msg.direction === 'outgoing' || msg.from_name === 'Admin';
                    const messageClass = isOutgoing ? 'outgoing' : 'incoming';
                    
                    let messageContent = `<div class="message-bubble">`;
                    
                    if (msg.message && msg.message.trim()) {
                        messageContent += `<div class="message-text">${escapeHtml(msg.message)}</div>`;
                    }
                    
                    if (msg.has_media && msg.media_url) {
                        const mediaCategory = msg.media_category || 'other';
                        const fileIcon = msg.file_icon || 'fa-file';
                        const fileName = msg.media_filename || 'file';
                        const fileSize = msg.formatted_file_size || '';
                        
                        switch(mediaCategory) {
                            case 'image':
                                messageContent += `<div class="media-preview image-preview mt-2"><img src="${msg.media_url}" alt="Image" onclick="openImageModal('${msg.media_url}')"></div>`;
                                break;
                            case 'video':
                                messageContent += `<div class="media-preview video-preview mt-2"><video controls><source src="${msg.media_url}" type="${msg.media_mime || 'video/mp4'}"></video><div class="small text-muted mt-1"><i class="fas ${fileIcon}"></i> ${escapeHtml(fileName)} ${fileSize ? '(' + fileSize + ')' : ''}</div></div>`;
                                break;
                            case 'audio':
                                messageContent += `<div class="media-preview audio-preview mt-2"><audio controls><source src="${msg.media_url}" type="${msg.media_mime || 'audio/mpeg'}"></audio><div class="small text-muted mt-1"><i class="fas ${fileIcon}"></i> ${escapeHtml(fileName)} ${fileSize ? '(' + fileSize + ')' : ''}</div></div>`;
                                break;
                            default:
                                messageContent += `<div class="media-preview document-preview mt-2"><a href="/wa-inbox/media/download/${msg.id}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas ${fileIcon} me-2"></i>${escapeHtml(fileName)}${fileSize ? '<span class="badge bg-secondary ms-2">' + fileSize + '</span>' : ''}</a></div>`;
                                break;
                        }
                    }
                    
                    messageContent += `</div>`;
                    messageContent += `<div class="message-time">${time}</div>`;
                    
                    messagesHtml += `<div class="message-item ${messageClass}" data-message-id="${msg.id}">${messageContent}</div>`;
                });
                
                if (messages.length === 0) {
                    messagesHtml = '<div class="text-center text-muted py-5"><i class="fas fa-comment"></i><p>Belum ada pesan dari nomor ini</p></div>';
                }
                
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) {
                    const chatHeader = document.querySelector('.chat-header');
                    if (chatHeader) {
                        chatHeader.innerHTML = `
                            <div class="chat-number">
                                <strong>${escapeHtml(displayName)}</strong>
                                <small class="text-muted d-block">${escapeHtml(currentNumber)}</small>
                            </div>
                        `;
                    }
                    
                    messagesContainer.innerHTML = messagesHtml;
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
                
                await loadLeadData(currentNumber);
                await axios.post(`/wa-inbox/api/mark-read/${currentNumber}`).catch(e => console.log('Mark read error:', e));
                
            } catch (error) {
                console.error('Error refreshing current messages:', error);
            }
        }
        
        async function updateConversationPreview() {
            try {
                const response = await axios.get('/wa-inbox/api/conversations');
                const conversations = response.data.conversations.data;
                const unreadCount = response.data.unread_count;
                
                const unreadBadge = document.querySelector('.unread-badge');
                if (unreadCount > 0) {
                    if (unreadBadge) {
                        unreadBadge.textContent = `${unreadCount} belum dibaca`;
                    } else {
                        const header = document.querySelector('.k-card-header h3');
                        if (header && !document.querySelector('.unread-badge')) {
                            header.innerHTML += `<span class="unread-badge">${unreadCount} belum dibaca</span>`;
                        }
                    }
                } else if (unreadBadge) {
                    unreadBadge.remove();
                }
                
                const listContainer = document.getElementById('conversations-list');
                if (!conversations || conversations.length === 0) {
                    listContainer.innerHTML = `<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2"></i><p class="small">Belum ada pesan masuk</p></div>`;
                    return;
                }
                
                let html = '';
                conversations.forEach(conv => {
                    const isActive = currentNumber === conv.from_number;
                    const displayName = conv.display_name || conv.from_number;
                    const timeValue = conv.received_at || conv.last_message_at || conv.created_at;
                    let timeText = '-';
                    if (timeValue) {
                        try { timeText = new Date(timeValue).toLocaleString(); } catch(e) { timeText = '-'; }
                    }
                    
                    let statusBadge = '';
                    if (conv.lead_status && conv.lead_status !== 'new') {
                        let statusIcon = conv.lead_status === 'hot' ? '🔥' : (conv.lead_status === 'deal' ? '✅' : (conv.lead_status === 'lost' ? '❌' : '🆕'));
                        let statusLabel = conv.lead_status === 'hot' ? 'Hot Lead' : (conv.lead_status === 'deal' ? 'Deal Won' : (conv.lead_status === 'lost' ? 'Deal Lost' : 'New Lead'));
                        let statusColor = conv.lead_status === 'hot' ? 'warning' : (conv.lead_status === 'deal' ? 'success' : (conv.lead_status === 'lost' ? 'danger' : 'info'));
                        statusBadge = `<span class="badge-status ${statusColor}" style="margin-left: 0.5rem; font-size: 0.55rem; padding: 0.15rem 0.4rem;">${statusIcon} ${statusLabel}</span>`;
                    }
                    
                    html += `
                        <div class="conversation-item ${isActive ? 'active' : ''}" data-number="${conv.from_number}" onclick="loadConversation('${conv.from_number}')">
                            <div class="conversation-avatar"><i class="fas fa-user"></i></div>
                            <div class="conversation-info">
                                <div class="conversation-number">
                                    <span><strong>${escapeHtml(displayName)}</strong>${statusBadge}</span>
                                    ${conv.unread_count > 0 ? '<span class="unread-indicator"></span>' : ''}
                                </div>
                                <div class="conversation-preview small">${escapeHtml(conv.from_number)}</div>
                                <div class="conversation-time">${timeText}</div>
                            </div>
                        </div>
                    `;
                });
                
                listContainer.innerHTML = html;
                
            } catch (error) {
                console.error('Error updating conversation preview:', error);
            }
        }
        
        async function refreshConversations(keepActive = true) {
            if (isRefreshing) return;
            isRefreshing = true;
            
            try {
                const response = await axios.get('/wa-inbox/api/conversations');
                const conversations = response.data.conversations.data;
                const unreadCount = response.data.unread_count;
                
                const unreadBadge = document.querySelector('.unread-badge');
                if (unreadCount > 0) {
                    if (unreadBadge) {
                        unreadBadge.textContent = `${unreadCount} belum dibaca`;
                    }
                } else if (unreadBadge) {
                    unreadBadge.remove();
                }
                
                const listContainer = document.getElementById('conversations-list');
                if (!conversations || conversations.length === 0) {
                    listContainer.innerHTML = `<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2"></i><p class="small">Belum ada pesan masuk</p></div>`;
                    return;
                }
                
                let html = '';
                conversations.forEach(conv => {
                    const isActive = keepActive && (currentNumber === conv.from_number);
                    const displayName = conv.display_name || conv.from_number;
                    const timeValue = conv.received_at || conv.last_message_at || conv.created_at;
                    let timeText = '-';
                    if (timeValue) {
                        try { timeText = new Date(timeValue).toLocaleString(); } catch(e) { timeText = '-'; }
                    }
                    
                    let statusBadge = '';
                    if (conv.lead_status && conv.lead_status !== 'new') {
                        let statusIcon = conv.lead_status === 'hot' ? '🔥' : (conv.lead_status === 'deal' ? '✅' : (conv.lead_status === 'lost' ? '❌' : '🆕'));
                        let statusLabel = conv.lead_status === 'hot' ? 'Hot Lead' : (conv.lead_status === 'deal' ? 'Deal Won' : (conv.lead_status === 'lost' ? 'Deal Lost' : 'New Lead'));
                        let statusColor = conv.lead_status === 'hot' ? 'warning' : (conv.lead_status === 'deal' ? 'success' : (conv.lead_status === 'lost' ? 'danger' : 'info'));
                        statusBadge = `<span class="badge-status ${statusColor}" style="margin-left: 0.5rem; font-size: 0.55rem; padding: 0.15rem 0.4rem;">${statusIcon} ${statusLabel}</span>`;
                    }
                    
                    html += `
                        <div class="conversation-item ${isActive ? 'active' : ''}" data-number="${conv.from_number}" onclick="loadConversation('${conv.from_number}')">
                            <div class="conversation-avatar"><i class="fas fa-user"></i></div>
                            <div class="conversation-info">
                                <div class="conversation-number">
                                    <span><strong>${escapeHtml(displayName)}</strong>${statusBadge}</span>
                                    ${conv.unread_count > 0 ? '<span class="unread-indicator"></span>' : ''}
                                </div>
                                <div class="conversation-preview small">${escapeHtml(conv.from_number)}</div>
                                <div class="conversation-time">${timeText}</div>
                            </div>
                        </div>
                    `;
                });
                
                listContainer.innerHTML = html;
                
                if (keepActive && currentNumber) {
                    await axios.post(`/wa-inbox/api/mark-read/${currentNumber}`).catch(e => console.log('Mark read error:', e));
                }
                
            } catch (error) {
                console.error('Error refreshing conversations:', error);
            } finally {
                isRefreshing = false;
            }
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        window.addEventListener('beforeunload', function() {
            if (currentNumber) {
                sessionStorage.setItem('lastActiveConversation', currentNumber);
            }
        });
        
        setInterval(() => {
            refreshConversations(true);
        }, 10000);
    </script>
</body>
</html>

@php
    $getCurrentTranslation = getCurrentTranslation();
    $isChatPage = request()->routeIs('chat.*') || request()->is('chat', 'chat/*');
@endphp
<style>
    #chat-widget-toggle {
        position: fixed !important;
        bottom: 0.5rem;
        right: 0.5rem;
    }
    @media (min-width: 768px) {
        #chat-widget-toggle { bottom: 1rem; right: 1rem; }
    }
    #chat-widget-panel.chat-widget-panel-responsive { width: calc(100vw - 2rem); max-width: 380px; height: 520px; max-height: 80vh; }
    @media (min-width: 400px) { #chat-widget-panel.chat-widget-panel-responsive { width: 380px; } }
    #chat-widget-input-wrap {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.35rem;
        align-items: end;
        width: 100%;
        min-width: 0;
    }
    #chat-widget-input-wrap .chat-widget-input-field { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; }

    /* Prevent horizontal scrollbar in conversation list (long names/status/badges/images) */
    #chat-widget-conversation-list {
        overflow-x: hidden !important;
        overflow-y: auto !important;
    }
    #chat-widget-conversation-list .chat-conv-item {
        max-width: 100%;
    }
    #chat-widget-conversation-list .chat-conv-item .symbol img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        max-width: 100%;
    }
    .chat-reply-preview-bar {
        background: rgba(0, 0, 0, 0.05);
        border-left: 3px solid var(--kt-primary);
        border-radius: 8px 8px 0 0;
        margin-bottom: -1px;
        opacity: 0.92;
    }
    .chat-reply-preview-label { font-size: 0.7rem; font-weight: 600; color: var(--kt-primary); opacity: 0.9; flex-shrink: 0; }
    .chat-reply-preview-body { font-size: 0.75rem; color: var(--kt-gray-700); opacity: 0.85; }
    .chat-reply-preview-close { opacity: 0.7; color: var(--kt-gray-600); }
    .chat-reply-preview-close:hover { opacity: 1; color: var(--kt-danger); }
    @keyframes chat-reply-target-bounce {
        0%, 100% { box-shadow: 0 0 0 2px var(--kt-primary); transform: scale(1); }
        25% { box-shadow: 0 0 0 4px var(--kt-primary); transform: scale(1.02); }
        50% { box-shadow: 0 0 0 3px var(--kt-primary); transform: scale(1); }
        75% { box-shadow: 0 0 0 4px var(--kt-primary); transform: scale(1.01); }
    }
    .chat-msg-row-reply-target .chat-msg-bubble { animation: chat-reply-target-bounce 2s ease-in-out; box-shadow: 0 0 0 2px var(--kt-primary); }
    .chat-reply-quote-wrap { display: flex; align-items: stretch; margin-bottom: 8px; border-radius: 4px; min-height: 36px; }
    .chat-reply-quote-bar { width: 4px; min-width: 4px; flex-shrink: 0; align-self: stretch; border-radius: 2px; background: var(--kt-primary); opacity: 0.9; }
    .chat-msg-bubble.bg-primary .chat-reply-quote-bar { background: rgba(255,255,255,0.85); }
    .chat-reply-quote-inner { padding: 4px 8px 6px 8px; flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; gap: 2px; }
    .chat-reply-quote-sender { font-weight: 600; font-size: 0.8em; opacity: 0.95; display: block; }
    .chat-reply-quote-sender:empty { display: none; }
    .chat-reply-quote-body { font-size: 0.85em; opacity: 0.9; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-msg-reply-text { margin-top: 2px; }

    /* Chat widget: smooth show/hide animation (panel) */
    #chat-widget-panel.chat-widget-panel-animate {
        transform-origin: bottom right;
        transition: opacity 180ms ease, transform 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
        will-change: opacity, transform;
    }
    #chat-widget-panel.chat-widget-panel-closed {
        opacity: 0;
        transform: translateY(14px) scale(0.98);
        pointer-events: none;
    }
    #chat-widget-panel.chat-widget-panel-open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    @media (prefers-reduced-motion: reduce) {
        #chat-widget-panel.chat-widget-panel-animate {
            transition: none !important;
        }
        #chat-widget-panel.chat-widget-panel-closed,
        #chat-widget-panel.chat-widget-panel-open {
            transform: none !important;
        }
    }

    /* Emoji picker (full dataset via emoji-picker-element) */
    .chat-emoji-picker-popover {
        padding: 0 !important;
        overflow: hidden !important;
        border-radius: 12px;
    }
    .chat-emoji-picker-popover emoji-picker {
        width: min(320px, 88vw);
        height: 340px;
        display: block;
        --num-columns: 9;
        --emoji-size: 1.3rem;
        --emoji-padding: 0.45rem;
        --emoji-font-family: "Twemoji Country Flags","Twemoji Mozilla","Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji","EmojiOne Color","Android Emoji",sans-serif;
    }
    #chat-widget-emoji-picker {
        max-width: min(320px, 88vw);
    }
</style>
<div id="chat-widget" class="position-fixed bottom-0 end-0 me-2 me-md-4 mb-2 mb-md-4 {{ $isChatPage ? 'd-none' : '' }}" style="z-index: 1050;">
    <div id="chat-widget-panel" class="d-none card shadow-lg border-0 chat-widget-panel-responsive chat-widget-panel-animate chat-widget-panel-closed" style="z-index: 1051;">
        <div class="card-header py-3 d-flex align-items-center justify-content-between bg-primary">
            <span class="text-white fw-bold">{{ $getCurrentTranslation['messages'] ?? 'Messages' }}</span>
            <div>
                <a href="{{ route('chat.index') }}" id="chat-widget-view-all" class="btn btn-sm btn-light btn-active-light-primary me-1" title="{{ $getCurrentTranslation['view_all'] ?? 'View all' }}"><i class="fa-solid fa-expand"></i></a>
                <button type="button" class="btn btn-sm btn-light btn-active-light-primary" id="chat-widget-minimize" title="{{ $getCurrentTranslation['minimize'] ?? 'Minimize' }}"><i class="fa-solid fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body p-0 d-flex flex-column overflow-hidden" style="height: calc(100% - 52px);">
            <div id="chat-widget-search-wrap" class="p-2 border-bottom bg-white flex-shrink-0">
                <input type="text" class="form-control form-control-sm form-control-solid" id="chat-widget-search-user" placeholder="{{ $getCurrentTranslation['search'] ?? 'Search' }}..." autocomplete="off">
            </div>
            <div id="chat-widget-conversation-list" class="flex-grow-1 overflow-auto"></div>
            <div id="chat-widget-thread-panel" class="d-none flex-grow-1 d-flex flex-column overflow-hidden min-w-0">
                <div class="p-2 border-bottom bg-light d-flex align-items-center flex-shrink-0">
                    <button type="button" class="btn btn-icon btn-sm me-2" id="chat-widget-back" title="{{ $getCurrentTranslation['back'] ?? 'Back' }}"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="symbol symbol-35px me-2 flex-shrink-0"><img id="chat-widget-thread-avatar" src="" alt=""><span class="symbol-label bg-primary text-white fw-bold" id="chat-widget-thread-avatar-initial"></span></div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="fw-bold text-gray-800 d-block text-truncate" id="chat-widget-thread-name"></span>
                        <span class="d-flex align-items-center fs-8 text-muted">
                            <span id="chat-widget-thread-status"></span>
                            <span id="chat-widget-thread-syncing" class="chat-thread-syncing d-none ms-1" title="Syncing"><i class="fa-solid fa-arrows-rotate fa-spin fa-sm text-primary"></i></span>
                        </span>
                    </div>
                    <div class="dropdown flex-shrink-0 ms-1">
                        <button class="btn btn-icon btn-sm btn-light-primary" type="button" id="chat-widget-thread-menu-btn" data-bs-toggle="dropdown" aria-expanded="false" title=""><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="d-none" id="chat-widget-group-info-item"><a class="dropdown-item" href="javascript:void(0)" id="chat-widget-group-info"><i class="fa-solid fa-info-circle me-2"></i>{{ $getCurrentTranslation['group_info'] ?? 'Group info' }}</a></li>
                            <li class="d-none" id="chat-widget-set-nicknames-item"><a class="dropdown-item" href="javascript:void(0)" id="chat-widget-set-nicknames"><i class="fa-solid fa-tag me-2"></i>{{ $getCurrentTranslation['set_nicknames'] ?? 'Set nicknames' }}</a></li>
                            <li class="d-none" id="chat-widget-leave-group-item"><a class="dropdown-item text-warning" href="javascript:void(0)" id="chat-widget-leave-group"><i class="fa-solid fa-right-from-bracket me-2"></i>{{ $getCurrentTranslation['leave_group'] ?? 'Leave group' }}</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" id="chat-widget-close-thread"><i class="fa-solid fa-xmark me-2"></i>{{ $getCurrentTranslation['close_chat'] ?? 'Close Chat' }}</a></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" id="chat-widget-delete-conversation"><i class="fa-solid fa-trash me-2"></i>{{ $getCurrentTranslation['delete_chat'] ?? 'Delete Chat' }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto p-2 min-h-0" id="chat-widget-messages"></div>
                <div class="p-2 border-top bg-white flex-shrink-0 w-100" style="min-width: 0;">
                    <div id="chat-widget-reply-preview" class="chat-reply-preview-bar d-none">
                        <div class="d-flex align-items-center gap-2 py-1 px-2">
                            <span class="chat-reply-preview-label">{{ $getCurrentTranslation['replying_to'] ?? 'Replying to' }}</span>
                            <span id="chat-widget-reply-preview-body" class="chat-reply-preview-body text-truncate flex-grow-1"></span>
                            <button type="button" class="btn btn-icon btn-sm p-0 min-w-auto chat-reply-preview-close" id="chat-widget-reply-cancel" title="{{ $getCurrentTranslation['cancel'] ?? 'Cancel' }}"><i class="fa-solid fa-xmark fa-sm"></i></button>
                        </div>
                    </div>
                    <div id="chat-widget-input-wrap">
                        <textarea class="form-control form-control-sm form-control-solid chat-widget-input-field" id="chat-widget-message-input" rows="2" placeholder="{{ $getCurrentTranslation['type_message'] ?? 'Type a message' }}..." maxlength="10000" style="min-height: 38px; resize: none;"></textarea>
                        <div class="d-flex align-items-center gap-1">
                            <div class="position-relative">
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary" id="chat-widget-emoji-btn" title="{{ $getCurrentTranslation['emoji'] ?? 'Emoji' }}"><i class="fa-regular fa-face-smile"></i></button>
                                <div id="chat-widget-emoji-picker" class="d-none position-absolute bottom-100 end-0 mb-1 bg-white border shadow-lg" style="z-index: 1060;"></div>
                            </div>
                            <input type="file" id="chat-widget-file-input" class="d-none" accept="*">
                            <button type="button" class="btn btn-icon btn-sm btn-light-primary" id="chat-widget-attach" title="{{ $getCurrentTranslation['attach_file'] ?? 'Attach' }}"><i class="fa-solid fa-paperclip"></i></button>
                            <button type="button" class="btn btn-sm btn-primary" id="chat-widget-send"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="button" id="chat-widget-toggle" class="btn btn-primary btn-icon position-relative" style="width: 56px; height: 56px; border-radius: 50%; z-index: 1049;">
        <i class="fa-solid fa-comment-dots fa-lg"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger d-none" id="chat-widget-badge">0</span>
    </button>
</div>
@include('common.chat.partials.chat-scripts', ['isWidget' => true])

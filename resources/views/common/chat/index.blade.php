@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
@endphp
@extends($layout)
@section('content')
<style>
    /* Fixed message layout: no page scroll, only messages area scrolls */
    .chat-page-wrap {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
        height: 100%;
    }
    .chat-page-wrap #kt_app_content {
        flex: 1 1 0;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-page-wrap #kt_app_content_container {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-page-wrap #chat-app {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-page-wrap #chat-app .card-body {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-page-wrap #chat-app-row {
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    .chat-page-wrap #chat-conversation-list-wrap {
        min-width: 0;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-page-wrap #chat-conversation-list {
        overflow-x: hidden;
        min-width: 0;
    }
    .chat-page-wrap .chat-conv-item {
        min-width: 0;
    }
    .chat-page-wrap .chat-conv-item .flex-grow-1 {
        min-width: 0;
    }
    .chat-page-wrap #chat-thread-wrap,
    .chat-page-wrap #chat-thread-panel {
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }
    .chat-page-wrap #chat-thread-panel .chat-input-bar {
        flex-shrink: 0;
    }
    .chat-page-wrap #chat-messages-container {
        flex: 1 1 0;
        min-height: 0;
        overflow-y: auto;
    }
    #chat-message-input-wrap {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.5rem;
        align-items: end;
        width: 100%;
        min-width: 0;
    }
    #chat-message-input-wrap .chat-input-field { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box;}

    .chat-input-bar { padding-bottom: calc(0.5rem + 5px); flex-shrink: 0; }
    @media (min-width: 768px) {
        .chat-input-bar { padding-bottom: calc(1rem + 5px); }
    }
    @media (max-width: 767.98px) {
        #chat-message-input-wrap { gap: 0.35rem; }
    }
    .chat-reply-preview-bar {
        background: rgba(0, 0, 0, 0.05);
        border-left: 3px solid var(--kt-primary);
        border-radius: 8px 8px 0 0;
        margin-bottom: -1px;
        opacity: 0.92;
    }
    .chat-reply-preview-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--kt-primary);
        opacity: 0.9;
        flex-shrink: 0;
    }
    .chat-reply-preview-body {
        font-size: 0.75rem;
        color: var(--kt-gray-700);
        opacity: 0.85;
    }
    .chat-reply-preview-close {
        opacity: 0.7;
        color: var(--kt-gray-600);
    }
    .chat-reply-preview-close:hover {
        opacity: 1;
        color: var(--kt-danger);
    }
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
    .chat-reaction-badge { cursor: pointer; font-size: 20px; }
    .chat-reaction-badge:hover { opacity: 0.9; }
    .chat-msg-reactions .badge { padding: 0.15em 0.4em; }
    /* Emoji picker (full dataset via emoji-picker-element) */
    .chat-emoji-picker-popover {
        padding: 0 !important;
        overflow: hidden !important;
        border-radius: 12px;
    }
    .chat-emoji-picker-popover emoji-picker {
        width: min(360px, 92vw);
        height: 360px;
        display: block;
        --num-columns: 9;
        --emoji-size: 1.35rem;
        --emoji-padding: 0.45rem;
        --emoji-font-family: "Twemoji Country Flags","Twemoji Mozilla","Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji","EmojiOne Color","Android Emoji",sans-serif;
    }
    #chat-emoji-picker {
        max-width: min(360px, 92vw);
    }
</style>
<div class="chat-page-wrap d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 flex-shrink-0">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{ $getCurrentTranslation['messages'] ?? 'Messages' }}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted"><a href="{{ route(Auth::user()->user_type == 'admin' ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'Dashboard' }}</a></li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ $getCurrentTranslation['messages'] ?? 'Messages' }}</li>
                </ul>
            </div>
        </div>
    </div>
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div id="chat-app" class="card h-100">
                <div class="card-body p-0 h-100">
                    <div class="row g-0 overflow-hidden h-100" id="chat-app-row">
                        <div class="col-12 col-md-4 border-end bg-light" id="chat-conversation-list-wrap">
                            <div class="p-3 border-bottom bg-white" id="chat-search-wrap">
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-solid flex-grow-1" id="chat-search-user" placeholder="{{ $getCurrentTranslation['search'] ?? 'Search' }}..." autocomplete="off">
                                    <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="chat-create-group-btn" title="{{ $getCurrentTranslation['create_group'] ?? 'Create group' }}"><i class="fa-solid fa-users"></i></button>
                                </div>
                            </div>
                            <div id="chat-conversation-list" style="overflow-y: auto;"></div>
                        </div>
                        <div class="col-12 col-md-8" id="chat-thread-wrap">
                            <div id="chat-thread-placeholder" class="text-muted text-center py-5">
                                <i class="fa-solid fa-comments fa-3x mb-3 opacity-50"></i>
                                <p class="mb-0">{{ $getCurrentTranslation['select_conversation'] ?? 'Select a conversation or start a new chat' }}</p>
                            </div>
                            <div id="chat-thread-panel" class="d-none overflow-hidden">
                                <div id="chat-thread-header" class="p-3 border-bottom bg-white d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn btn-icon btn-sm d-md-none me-2" id="chat-back-to-list" title="{{ $getCurrentTranslation['back'] ?? 'Back' }}"><i class="fa-solid fa-arrow-left"></i></a>
                                    <div class="symbol symbol-40px me-3"><img id="chat-thread-avatar" src="" alt=""><span class="symbol-label bg-primary text-white fw-bold" id="chat-thread-avatar-initial"></span></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <span class="fw-bold text-gray-800" id="chat-thread-name"></span>
                                        <span class="d-flex align-items-center fs-7 text-muted">
                                            <span id="chat-thread-status"></span>
                                            <span id="chat-thread-syncing" class="chat-thread-syncing d-none ms-1" title="Syncing"><i class="fa-solid fa-arrows-rotate fa-spin fa-sm text-primary"></i></span>
                                        </span>
                                    </div>
                                    <div class="dropdown ms-2">
                                        <button class="btn btn-icon btn-sm btn-light-primary" type="button" id="chat-thread-menu-btn" data-bs-toggle="dropdown" aria-expanded="false" title=""><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li class="d-none" id="chat-group-info-item"><a class="dropdown-item" href="javascript:void(0)" id="chat-group-info"><i class="fa-solid fa-info-circle me-2"></i>{{ $getCurrentTranslation['group_info'] ?? 'Group info' }}</a></li>
                                            <li class="d-none" id="chat-set-nicknames-item"><a class="dropdown-item" href="javascript:void(0)" id="chat-set-nicknames"><i class="fa-solid fa-tag me-2"></i>{{ $getCurrentTranslation['set_nicknames'] ?? 'Set nicknames' }}</a></li>
                                            <li class="d-none" id="chat-leave-group-item"><a class="dropdown-item text-warning" href="javascript:void(0)" id="chat-leave-group"><i class="fa-solid fa-right-from-bracket me-2"></i>{{ $getCurrentTranslation['leave_group'] ?? 'Leave group' }}</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" id="chat-close-thread"><i class="fa-solid fa-xmark me-2"></i>{{ $getCurrentTranslation['close_chat'] ?? 'Close Chat' }}</a></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" id="chat-delete-conversation"><i class="fa-solid fa-trash me-2"></i>{{ $getCurrentTranslation['delete_chat'] ?? 'Delete Chat' }}</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div id="chat-messages-container" class="p-3" style="overflow-y: auto;"></div>
                                <div class="chat-input-bar p-2 p-md-3 border-top bg-white w-100">
                                    <div id="chat-reply-preview" class="chat-reply-preview-bar d-none">
                                        <div class="d-flex align-items-center gap-2 py-1 px-2">
                                            <span class="chat-reply-preview-label">{{ $getCurrentTranslation['replying_to'] ?? 'Replying to' }}</span>
                                            <span id="chat-reply-preview-body" class="chat-reply-preview-body text-truncate flex-grow-1"></span>
                                            <button type="button" class="btn btn-icon btn-sm p-0 min-w-auto chat-reply-preview-close" id="chat-reply-cancel" title="{{ $getCurrentTranslation['cancel'] ?? 'Cancel' }}"><i class="fa-solid fa-xmark fa-sm"></i></button>
                                        </div>
                                    </div>
                                    <div id="chat-message-input-wrap">
                                        <textarea class="form-control form-control-solid chat-input-field" id="chat-message-input" rows="2" placeholder="{{ $getCurrentTranslation['type_message'] ?? 'Type a message' }}..." maxlength="10000" style="min-height: 42px; resize: none;"></textarea>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="position-relative">
                                                <button type="button" class="btn btn-icon btn-light-primary" id="chat-emoji-btn" title="{{ $getCurrentTranslation['emoji'] ?? 'Emoji' }}"><i class="fa-regular fa-face-smile"></i></button>
                                                <div id="chat-emoji-picker" class="d-none position-absolute bottom-100 end-0 mb-1 bg-white border shadow-lg" style="z-index: 1060;"></div>
                                            </div>
                                            <input type="file" id="chat-file-input" class="d-none" accept="*">
                                            <button type="button" class="btn btn-icon btn-light-primary" id="chat-attach-btn" title="{{ $getCurrentTranslation['attach_file'] ?? 'Attach file' }}"><i class="fa-solid fa-paperclip"></i></button>
                                            <button type="button" class="btn btn-primary" id="chat-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
                                        </div>
                                    </div>
                                    @if(config('chat.max_file_size_kb', 0) > 0)
                                    <div class="fs-8 text-muted mt-1">{{ $getCurrentTranslation['max_file_size'] ?? 'Max file size' }}: {{ round(config('chat.max_file_size_kb') / 1024, 1) }} MB</div>
                                    @else
                                    <div class="fs-8 text-muted mt-1">{{ $getCurrentTranslation['max_file_size'] ?? 'Max file size' }}: {{ $getCurrentTranslation['unlimited'] ?? 'Unlimited' }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('common.chat.partials.chat-scripts', ['isWidget' => false])
@endsection

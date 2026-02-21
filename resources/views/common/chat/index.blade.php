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
        grid-template-columns: 1fr auto auto;
        gap: 0.5rem;
        align-items: end;
        width: 100%;
        min-width: 0;
    }
    #chat-message-input-wrap .chat-input-field { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; }
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
                                <input type="text" class="form-control form-control-solid" id="chat-search-user" placeholder="{{ $getCurrentTranslation['search'] ?? 'Search' }}..." autocomplete="off">
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
                                        <input type="file" id="chat-file-input" class="d-none" accept="*">
                                        <button type="button" class="btn btn-icon btn-light-primary" id="chat-attach-btn" title="{{ $getCurrentTranslation['attach_file'] ?? 'Attach file' }}"><i class="fa-solid fa-paperclip"></i></button>
                                        <button type="button" class="btn btn-primary" id="chat-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
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

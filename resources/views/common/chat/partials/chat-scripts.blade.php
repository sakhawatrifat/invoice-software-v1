@php
    $isWidget = $isWidget ?? false;
    $prefix = $isWidget ? 'chat-widget-' : 'chat-';
    $wrap = $isWidget ? 'chat-widget' : 'chat-app';
    $getCurrentTranslation = $getCurrentTranslation ?? (function_exists('getCurrentTranslation') ? getCurrentTranslation() : []);
@endphp
<script>
(function() {
    const isWidget = @json($isWidget);
    const prefix = @json($prefix);
    const POLL_MS = {{ (int) config('chat.poll_interval_seconds', 15) * 1000 }};
    const POLL_MS_THREAD_OPEN = 4000;
    const POLL_MS_HIDDEN = 30000;
    const routes = {
        activity: @json(route('chat.activity')),
        conversations: @json(route('chat.conversations')),
        messages: (id) => @json(route('chat.messages', ['otherUserId' => ':id'])).replace(':id', id),
        send: @json(route('chat.send')),
        sendFile: @json(route('chat.sendFile')),
        markRead: @json(route('chat.markRead')),
        deleteForMe: @json(route('chat.deleteForMe')),
        deleteForAll: @json(route('chat.deleteForAll')),
        deleteConversation: @json(route('chat.deleteConversation')),
        history: (id) => @json(route('chat.history', ['messageId' => ':id'])).replace(':id', id),
        lastSeen: @json(route('chat.lastSeen')),
        download: (id) => @json(route('chat.download', ['messageId' => ':id'])).replace(':id', id),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute?.('content') || '';
    const CHAT_FILE_MAX_SIZE_KB = {{ (int) config('chat.max_file_size_kb', 0) }};
    const CHAT_FILE_SIZE_LIMITED = CHAT_FILE_MAX_SIZE_KB > 0;
    const CHAT_FILE_MAX_SIZE_BYTES = CHAT_FILE_MAX_SIZE_KB * 1024;
    const CHAT_FILE_MAX_SIZE_MB = (CHAT_FILE_MAX_SIZE_KB / 1024).toFixed(1);
    const CHAT_STR = {
        youDeletedTheMessage: @json($getCurrentTranslation['you_deleted_the_message'] ?? 'You deleted the message'),
        thisMessageWasDeleted: @json($getCurrentTranslation['this_message_was_deleted'] ?? 'This message was deleted'),
        removeForMe: @json($getCurrentTranslation['remove_for_me'] ?? 'Remove for me'),
        removeForEveryone: @json($getCurrentTranslation['remove_for_everyone'] ?? 'Remove for everyone'),
        closeChat: @json($getCurrentTranslation['close_chat'] ?? 'Close Chat'),
        deleteChat: @json($getCurrentTranslation['delete_chat'] ?? 'Delete Chat'),
        deleteChatConfirmTitle: @json($getCurrentTranslation['delete_chat_confirm_title'] ?? 'Delete Chat?'),
        deleteChatConfirmText: @json($getCurrentTranslation['delete_chat_confirm_text'] ?? 'All messages and files in this chat will be permanently deleted. This cannot be undone.'),
        removeForMeConfirmTitle: @json($getCurrentTranslation['remove_for_me_confirm_title'] ?? $getCurrentTranslation['remove_for_you_confirm'] ?? 'Remove for you?'),
        removeForMeConfirmText: @json($getCurrentTranslation['remove_for_me_confirm_text'] ?? $getCurrentTranslation['remove_for_you_text'] ?? 'This message will be removed from your view only.'),
        removeForEveryoneConfirmTitle: @json($getCurrentTranslation['remove_for_everyone_confirm_title'] ?? $getCurrentTranslation['remove_for_everyone_confirm'] ?? 'Remove for everyone?'),
        removeForEveryoneConfirmText: @json($getCurrentTranslation['remove_for_everyone_confirm_text'] ?? $getCurrentTranslation['remove_for_everyone_text'] ?? 'The message will show as deleted for everyone. This cannot be undone.'),
        yesRemove: @json($getCurrentTranslation['yes_remove'] ?? 'Yes, remove'),
        noConversations: @json($getCurrentTranslation['no_conversations'] ?? 'No conversations'),
        deletedAt: @json($getCurrentTranslation['deleted_at'] ?? 'Deleted at'),
        updatedAt: @json($getCurrentTranslation['updated_at_label'] ?? 'Updated at'),
    };

    let currentOtherUserId = null;
    let replyToMessage = null;
    let pollTimer = null;
    let conversationsCache = [];
    let messagesCache = [];
    let hasMoreMessages = false;
    let loadingMoreMessages = false;

    function el(id) { return document.getElementById(prefix + id) || document.getElementById(id); }

    function fetchJson(url, options = {}) {
        const opts = { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...options.headers } };
        if (options.method && options.method !== 'GET') {
            opts.headers['X-CSRF-TOKEN'] = csrf;
            opts.headers['Content-Type'] = options.contentType || 'application/json';
        }
        return fetch(url, { ...options, ...opts }).then(r => r.json());
    }

    function hidePreloader() {
        var el = document.querySelector('.r-preloader');
        if (el) el.style.display = 'none';
        if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
    }
    function showPreloader() {
        var el = document.querySelector('.r-preloader');
        if (el) el.style.display = 'flex';
        if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').css('display', 'flex');
    }

    function poll() {
        fetchJson(routes.activity).then(data => {
            if (data.chat && data.chat.conversations) {
                conversationsCache = data.chat.conversations;
                renderConversationList(conversationsCache);
                if (isWidget) updateWidgetBadge(data.chat.conversations);
                if (currentOtherUserId && messagesCache.length > 0) {
                    const conv = (data.chat.conversations || []).find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                    if (conv && conv.last_message && conv.last_message.is_sent) {
                        const lm = conv.last_message;
                        const idx = messagesCache.findIndex(function(m) { return m.id === lm.id; });
                        if (idx !== -1) {
                            const existing = messagesCache[idx];
                            const statusChanged = (existing.status || '') !== (lm.status || '');
                            const readAtChanged = String(existing.read_at || '') !== String(lm.read_at || '');
                            if (statusChanged || readAtChanged) {
                                messagesCache[idx] = Object.assign({}, existing, { status: lm.status, read_at: lm.read_at });
                                const cont = el(isWidget ? 'messages' : 'messages-container');
                                if (cont && !cont.querySelector('.dropdown-menu.show')) {
                                    renderMessages(messagesCache, cont, { scrollToBottom: false });
                                }
                            }
                        }
                    }
                }
            }
        }).catch(() => {});
        if (currentOtherUserId) {
            const syncingEl = el('thread-syncing');
            if (syncingEl) syncingEl.classList.remove('d-none');
            const url = routes.messages(currentOtherUserId) + '?limit=50';
            fetchJson(url).then(data => {
                const cont = el(isWidget ? 'messages' : 'messages-container');
                if (!cont || document.getElementById(prefix + 'thread-panel')?.classList.contains('d-none') === true) return;
                fetchJson(routes.markRead, { method: 'POST', body: JSON.stringify({ other_user_id: currentOtherUserId }) }).catch(function() {});
                const incoming = data.messages || [];
                const cacheById = {};
                messagesCache.forEach(function(m) { cacheById[m.id] = m; });
                let hasNewMessages = false;
                let hasStatusUpdates = false;
                incoming.forEach(function(inMsg) {
                    const existing = cacheById[inMsg.id];
                    if (!existing) {
                        hasNewMessages = true;
                        cacheById[inMsg.id] = inMsg;
                    } else {
                        if ((String(existing.status || '') !== String(inMsg.status || '')) || (String(existing.read_at || '') !== String(inMsg.read_at || ''))) hasStatusUpdates = true;
                        cacheById[inMsg.id] = inMsg;
                    }
                });
                const hasAnyUpdates = hasNewMessages || hasStatusUpdates;
                if (!hasAnyUpdates) return;
                const pollIds = new Set(incoming.map(function(m) { return m.id; }));
                const older = messagesCache.filter(function(m) { return !pollIds.has(m.id); });
                messagesCache = older.concat(incoming);
                if (messagesCache.length <= 50) hasMoreMessages = !!data.has_more;
                if (cont.querySelector('.dropdown-menu.show')) return;
                renderMessages(messagesCache, cont, { scrollToBottom: false });
            }).catch(() => {}).finally(function() {
                if (syncingEl) syncingEl.classList.add('d-none');
            });
        }
    }

    function updateWidgetBadge(conversations) {
        const total = conversations.reduce((s, c) => s + (c.unread_count || 0), 0);
        const badge = document.getElementById('chat-widget-badge');
        if (badge) {
            badge.textContent = total > 99 ? '99+' : total;
            badge.classList.toggle('d-none', total === 0);
        }
    }

    function renderConversationList(list) {
        const container = el(isWidget ? 'conversation-list' : 'conversation-list');
        if (!container) return;
        const search = (el('search-user') || el('search-user'))?.value?.toLowerCase() || '';
        let html = '';
        (list || []).forEach(c => {
            const name = (c.user?.name || '').toLowerCase();
            if (search && !name.includes(search)) return;
            const last = c.last_message;
            const unread = c.unread_count || 0;
            const avatar = c.user?.image_url ? `<img src="${escapeHtml(c.user.image_url)}" alt="">` : '';
            const initial = (c.user?.name || '?').charAt(0).toUpperCase();
            const lastText = last ? (last.deleted_for_everyone ? (CHAT_STR.thisMessageWasDeleted || 'This message was deleted') : (last.type === 'file' ? (last.file_name || 'File') : (last.body || '').substring(0, 40))) : 'No messages yet';
            const time = last ? formatTime(last.created_at) : '';
            const status = c.user?.last_seen_at ? (isRecent(c.user.last_seen_at) ? 'Active' : ('Last seen ' + formatTime(c.user.last_seen_at))) : '';
            const nameWithStatus = status === 'Active' ? (escapeHtml(c.user?.name || '') + ' <span class="badge badge-sm badge-success ms-1">Active</span>') : (status ? (escapeHtml(c.user?.name || '') + ' · ' + status) : (escapeHtml(c.user?.name || '')));
            html += `<div class="chat-conv-item p-3 border-bottom cursor-pointer d-flex align-items-center" data-user-id="${c.user?.id}" data-unread="${unread}">
                <div class="symbol symbol-45px me-3">${avatar || `<span class="symbol-label bg-primary text-inverse-primary fw-bold">${initial}</span>`}</div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-gray-800 text-truncate me-2">${nameWithStatus}</span>
                        <div class="d-flex align-items-center flex-shrink-0 gap-2">
                            <span class="fs-8 text-muted">${time}</span>
                            ${unread ? `<span class="badge badge-sm badge-danger">${unread}</span>` : ''}
                        </div>
                    </div>
                    <div class="fs-7 text-muted text-truncate">${escapeHtml(lastText)}</div>
                </div>
            </div>`;
        });
        container.innerHTML = html || '<div class="p-3 text-muted text-center">' + (CHAT_STR.noConversations || 'No conversations') + '</div>';
        container.querySelectorAll('.chat-conv-item').forEach(node => {
            node.addEventListener('click', () => openThread(parseInt(node.dataset.userId, 10)));
        });
    }

    function openThread(otherUserId) {
        currentOtherUserId = otherUserId;
        clearReply();
        const conv = conversationsCache.find(c => c.user?.id === otherUserId);
        if (conv && (conv.unread_count || 0) > 0) {
            conv.unread_count = 0;
            renderConversationList(conversationsCache);
            if (isWidget) updateWidgetBadge(conversationsCache);
        }
        const name = conv?.user?.name || 'User';
        const avatar = conv?.user?.image_url || '';
        const status = conv?.user?.last_seen_at ? (isRecent(conv.user.last_seen_at) ? 'Active now' : 'Last seen ' + formatTime(conv.user.last_seen_at)) : '';

        if (isWidget) {
            document.getElementById('chat-widget-search-wrap')?.classList.add('d-none');
            document.getElementById('chat-widget-conversation-list').classList.add('d-none');
            const panel = document.getElementById('chat-widget-thread-panel');
            panel.classList.remove('d-none');
            document.getElementById('chat-widget-thread-name').textContent = name;
            var statusEl = document.getElementById('chat-widget-thread-status');
            if (status === 'Active now') statusEl.innerHTML = '<span class="badge badge-sm badge-success">Active now</span>';
            else statusEl.textContent = status || '';
            document.getElementById('chat-widget-thread-avatar').src = avatar;
            document.getElementById('chat-widget-thread-avatar').style.display = avatar ? 'block' : 'none';
            document.getElementById('chat-widget-thread-avatar-initial').textContent = name.charAt(0).toUpperCase();
            document.getElementById('chat-widget-thread-avatar-initial').style.display = avatar ? 'none' : 'flex';
        } else {
            document.getElementById('chat-thread-placeholder').classList.add('d-none');
            const panel = document.getElementById('chat-thread-panel');
            panel.classList.remove('d-none');
            document.getElementById('chat-thread-name').textContent = name;
            var statusEl = document.getElementById('chat-thread-status');
            if (status === 'Active now') statusEl.innerHTML = '<span class="badge badge-sm badge-success">Active now</span>';
            else statusEl.textContent = status || '';
            document.getElementById('chat-thread-avatar').src = avatar;
            document.getElementById('chat-thread-avatar').style.display = avatar ? 'block' : 'none';
            document.getElementById('chat-thread-avatar-initial').textContent = name.charAt(0).toUpperCase();
            document.getElementById('chat-thread-avatar-initial').style.display = avatar ? 'none' : 'flex';
        }

        if (!isWidget && typeof $ !== 'undefined') {
            setTimeout(function() { applyChatThreadHeights(); }, 50);
        }
        messagesCache = [];
        hasMoreMessages = true;
        const cont = el(isWidget ? 'messages' : 'messages-container');
        fetchJson(routes.messages(otherUserId) + '?limit=50').then(data => {
            messagesCache = data.messages || [];
            hasMoreMessages = !!data.has_more;
            if (cont) renderMessages(messagesCache, cont);
        });
        fetchJson(routes.markRead, { method: 'POST', body: JSON.stringify({ other_user_id: otherUserId }) });
        if (!isWidget && window.history && window.history.replaceState) {
            const openParam = (conv?.user?.uid != null && conv.user.uid !== '') ? conv.user.uid : String(otherUserId);
            const params = new URLSearchParams(window.location.search);
            params.set('open', openParam);
            const url = window.location.pathname + '?' + params.toString();
            window.history.replaceState({ open: openParam }, '', url);
        }
        if (typeof startPollTimer === 'function') startPollTimer();
    }

    function applyChatHeights() {
        if (isWidget || typeof $ === 'undefined') return;
        if (!$('#chat-app').length) return;
        var toolbarH = $('#kt_app_toolbar').outerHeight(true) || 0;
        var footerEl = $('#kt_app_footer, .app-footer, [id*="footer"]').first();
        var footerH = footerEl.length ? footerEl.outerHeight(true) : 0;
        var gap = 32;
        var availableH = $(window).height() - toolbarH - footerH - gap;
        if (availableH < 300) availableH = 300;

        $('#chat-app').outerHeight(availableH);
        var cardBodyH = $('#chat-app').innerHeight();
        $('#chat-app .card-body').height(cardBodyH);
        $('#chat-app-row').height(cardBodyH);
        $('#chat-conversation-list-wrap').height(cardBodyH);
        $('#chat-thread-wrap').height(cardBodyH);

        var searchWrapH = $('#chat-search-wrap').outerHeight(true) || 0;
        $('#chat-conversation-list').height(Math.max(100, cardBodyH - searchWrapH));

        $('#chat-thread-placeholder').height(cardBodyH);

        var $panel = $('#chat-thread-panel');
        if ($panel.length && !$panel.hasClass('d-none')) {
            $panel.height(cardBodyH);
            var chatHeaderH = $('#chat-thread-header').outerHeight(true) || 0;
            var inputBarH = $('.chat-input-bar').outerHeight(true) || 0;
            var messagesH = cardBodyH - chatHeaderH - inputBarH - 10;
            if (messagesH < 100) messagesH = 100;
            $('#chat-messages-container').height(messagesH).css('overflow-y', 'auto');
        }
    }

    function applyChatThreadHeights() {
        if (isWidget || typeof $ === 'undefined') return;
        applyChatHeights();
    }

    function isImageFile(fileName) {
        if (!fileName) return false;
        const ext = (fileName.split('.').pop() || '').toLowerCase();
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1;
    }
    function isVideoFile(fileName) {
        if (!fileName) return false;
        const ext = (fileName.split('.').pop() || '').toLowerCase();
        return ['mp4', 'webm', 'ogg', 'mov', 'avi'].indexOf(ext) !== -1;
    }
    function isAudioFile(fileName) {
        if (!fileName) return false;
        const ext = (fileName.split('.').pop() || '').toLowerCase();
        return ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'weba', 'flac'].indexOf(ext) !== -1;
    }

    function getMessagesInner(container) {
        if (!container) return null;
        let inner = container.querySelector('.chat-messages-inner');
        if (!inner) {
            container.innerHTML = '<div class="chat-messages-inner"></div>';
            inner = container.querySelector('.chat-messages-inner');
        }
        return inner;
    }

    function showMessagesLoadMorePreloader(container) {
        if (!container || container.querySelector('.chat-messages-loading')) return;
        container.insertAdjacentHTML('afterbegin', '<div class="chat-messages-loading py-3 text-center text-muted small"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading older messages...</div>');
    }
    function hideMessagesLoadMorePreloader(container) {
        if (!container) return;
        container.querySelector('.chat-messages-loading')?.remove();
    }

    function renderMessages(messages, container, options) {
        if (!container) return;
        options = options || {};
        const scrollToBottom = options.scrollToBottom !== false;
        const loadMorePrepend = !!options.loadMorePrepend;
        const inner = getMessagesInner(container);
        if (!inner) return;
        const prevScrollTop = container.scrollTop;
        const prevScrollHeight = container.scrollHeight;
        const prevClientHeight = container.clientHeight;
        const wasAtBottom = prevScrollHeight - (prevScrollTop + prevClientHeight) < 30;
        let html = '';
        messages.forEach(m => {
            const isSent = m.is_sent;
            const deletedForEveryone = !!m.deleted_for_everyone;
            const statusIcon = m.status === 'seen'
                ? '<span class="chat-msg-seen-double-circle" title="Read"><i class="fa-solid fa-circle"></i><i class="fa-solid fa-circle"></i></span>'
                : (m.status === 'delivered' ? '<i class="fa-solid fa-check-double"></i>' : '<i class="fa-solid fa-check"></i>');
            const bubbleClass = isSent ? 'bg-primary text-white' : 'bg-light';
            let body = '';
            if (deletedForEveryone) {
                const deletedText = isSent ? (CHAT_STR.youDeletedTheMessage || 'You deleted the message') : (CHAT_STR.thisMessageWasDeleted || 'This message was deleted');
                body = '<span class="fst-italic"><i class="fa-solid fa-ban me-1 opacity-75"></i>' + escapeHtml(deletedText) + '</span>';
            } else if (m.type === 'file' && m.file_url) {
                const fileUrlInline = m.file_url + (m.file_url.indexOf('?') !== -1 ? '&' : '?') + 'inline=1';
                if (isImageFile(m.file_name)) {
                    body = `<span class="chat-img-preview mf-prev d-inline-block cursor-pointer" data-src="${fileUrlInline}" role="button" title="View full size"><img src="${fileUrlInline}" alt="${escapeHtml(m.file_name || '')}" class="rounded" style="max-width: 100%; max-height: 200px; object-fit: contain; pointer-events: none;"></span><small class="d-block mt-1 opacity-75">${escapeHtml(m.file_name || '')}</small>`;
                } else if (isVideoFile(m.file_name)) {
                    body = `<video src="${fileUrlInline}" controls class="rounded" style="max-width: 100%; max-height: 200px;"></video><small class="d-block mt-1 opacity-75">${escapeHtml(m.file_name || '')}</small>`;
                } else if (isAudioFile(m.file_name)) {
                    body = `<audio src="${fileUrlInline}" controls class="rounded" style="max-width: 100%;"></audio><small class="d-block mt-1 opacity-75">${escapeHtml(m.file_name || '')}</small>`;
                } else {
                    body = `<a href="${m.file_url}" target="_blank" class="text-decoration-none">${escapeHtml(m.file_name || 'File')}</a>`;
                }
            } else if (m.type === 'file') {
                body = escapeHtml(m.file_name || 'File');
            } else {
                body = escapeHtml(m.body || '');
            }
            const replyToId = (m.reply_to && m.reply_to.id) ? m.reply_to.id : '';
            const replyQuote = !deletedForEveryone && (m.reply_to && (m.reply_to.sender_name || m.reply_to.body)) ? `<div class="chat-reply-quote mb-1 pb-1 opacity-75 cursor-pointer" style="font-size: 0.85em; border-bottom: 1px solid currentColor;"${replyToId ? ' data-reply-to-msg-id="' + replyToId + '"' : ''}>${escapeHtml(m.reply_to.sender_name ? m.reply_to.sender_name + ': ' : '')}${escapeHtml((m.reply_to.body || '').substring(0, 80))}${(m.reply_to.body || '').length > 80 ? '…' : ''}</div>` : '';
            const replyPreview = (m.type === 'file' ? (m.file_name || 'File') : (m.body || '')).substring(0, 60) + ((m.type === 'file' ? (m.file_name || 'File') : (m.body || '')).length > 60 ? '…' : '');
            const replySender = m.sender?.name || '';
            const menuItems = deletedForEveryone
                ? '<li><a class="dropdown-item chat-action" href="#" data-action="history" data-id="' + m.id + '">History</a></li>'
                : '<li><a class="dropdown-item chat-action" href="#" data-action="reply" data-id="' + m.id + '" data-reply-preview="' + escapeHtml(replyPreview) + '" data-reply-sender="' + escapeHtml(replySender) + '">Reply</a></li><li><a class="dropdown-item chat-action" href="#" data-action="history" data-id="' + m.id + '">History</a></li><li><a class="dropdown-item chat-action" href="#" data-action="deleteForMe" data-id="' + m.id + '">' + (CHAT_STR.removeForMe || 'Remove for me') + '</a></li>' + (isSent ? '<li><a class="dropdown-item chat-action text-danger" href="#" data-action="deleteForAll" data-id="' + m.id + '">' + (CHAT_STR.removeForEveryone || 'Remove for everyone') + '</a></li>' : '');
            const canReply = deletedForEveryone ? '0' : '1';
            html += `<div class="d-flex ${isSent ? 'justify-content-end' : 'justify-content-start'} align-items-end mb-2 chat-msg-row" data-msg-id="${m.id}" data-reply-id="${m.id}" data-reply-preview="${escapeHtml(replyPreview)}" data-reply-sender="${escapeHtml(replySender)}" data-can-reply="${canReply}" data-is-sent="${isSent ? '1' : '0'}">
                <div class="d-flex align-items-end gap-1 ${isSent ? 'flex-row-reverse' : ''}" style="max-width: 85%;">
                    <div class="p-2 rounded ${bubbleClass} chat-msg-bubble" style="max-width: 280px; touch-action: pan-y;">
                        <div class="chat-msg-body">${replyQuote}${body}</div>
                        <div class="d-flex align-items-center justify-content-end gap-1 mt-1">
                            <span class="opacity-75" style="font-size: 10px;">${formatTime(m.created_at)}</span>
                            ${isSent && !deletedForEveryone ? `<span class="chat-msg-status-icon" style="font-size: 10px;">${statusIcon}</span>` : ''}
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-icon btn-sm btn-light-primary p-1 min-w-auto opacity-50" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">${menuItems}</ul>
                    </div>
                </div>
            </div>`;
        });
        inner.innerHTML = html;
        if (loadMorePrepend) {
            container.scrollTop = prevScrollTop + (container.scrollHeight - prevScrollHeight);
        } else if (scrollToBottom || wasAtBottom) {
            container.scrollTop = container.scrollHeight;
        } else {
            container.scrollTop = prevScrollTop;
        }
        inner.querySelectorAll('.chat-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const action = this.dataset.action;
                const id = parseInt(this.dataset.id, 10);
                if (action === 'history') showHistory(id);
                else if (action === 'deleteForMe') deleteForMe(id);
                else if (action === 'deleteForAll') deleteForAll(id);
                else if (action === 'reply') setReplyTo({ id, body: this.dataset.replyPreview || '', sender_name: this.dataset.replySender || '' });
            });
        });
        inner.querySelectorAll('.chat-reply-quote[data-reply-to-msg-id]').forEach(quoteEl => {
            quoteEl.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const msgId = quoteEl.getAttribute('data-reply-to-msg-id');
                if (!msgId) return;
                const row = inner.querySelector('.chat-msg-row[data-msg-id="' + msgId + '"]');
                if (!row || !container) return;
                row.classList.remove('chat-msg-row-reply-target');
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        row.classList.add('chat-msg-row-reply-target');
                        setTimeout(function() {
                            row.classList.remove('chat-msg-row-reply-target');
                        }, 1200);
                    });
                });
            });
        });
        initSlideToReply(inner);
    }

    var slideToReplyState = { row: null, bubble: null, startX: 0, startY: 0, isSliding: false, docBound: false };
    const SLIDE_THRESHOLD = 45;
    const SLIDE_MAX = 72;

    function initSlideToReply(container) {
        if (!container) return;
        function getX(e) { return e.touches ? e.touches[0].clientX : e.clientX; }
        function getY(e) { return e.touches ? e.touches[0].clientY : e.clientY; }
        function onPointerStart(e) {
            if (e.target.closest('.dropdown') || e.target.closest('a') || e.target.closest('button') || e.target.closest('.chat-action')) return;
            var row = e.target.closest('.chat-msg-row');
            if (!row || row.dataset.canReply !== '1') return;
            var bubble = row.querySelector('.chat-msg-bubble');
            if (!bubble) return;
            slideToReplyState.row = row;
            slideToReplyState.bubble = bubble;
            slideToReplyState.startX = getX(e);
            slideToReplyState.startY = getY(e);
            slideToReplyState.isSliding = true;
            if (e.type === 'touchstart') e.preventDefault();
            if (e.type === 'mousedown') { e.preventDefault(); e.stopPropagation(); }
        }
        function onPointerMove(e) {
            var s = slideToReplyState;
            if (!s.isSliding || !s.bubble || !s.row) return;
            var x = getX(e);
            var deltaX = x - s.startX;
            var deltaY = getY(e) - s.startY;
            var isSent = s.row.dataset.isSent === '1';
            var horizontal = Math.abs(deltaX) > Math.abs(deltaY);
            var slideRight = isSent ? (deltaX < 0) : (deltaX > 0);
            var slideLeft = deltaX < 0;
            if (horizontal && (slideRight || slideLeft)) {
                if (e.type === 'touchmove') e.preventDefault();
                var tx = isSent ? Math.max(deltaX, -SLIDE_MAX) : (deltaX > 0 ? Math.min(deltaX, SLIDE_MAX) : Math.max(deltaX, -SLIDE_MAX));
                s.bubble.style.transition = 'none';
                s.bubble.style.transform = 'translateX(' + tx + 'px)';
            }
        }
        function onPointerEnd(e) {
            var s = slideToReplyState;
            if (!s.isSliding || !s.bubble || !s.row) return;
            var x = (e.type === 'touchend' && e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].clientX : e.clientX;
            var deltaX = x - s.startX;
            var isSent = s.row.dataset.isSent === '1';
            var triggered = isSent ? (deltaX <= -SLIDE_THRESHOLD) : (Math.abs(deltaX) >= SLIDE_THRESHOLD);
            var replyId = parseInt(s.row.dataset.replyId, 10);
            var replyBody = s.row.dataset.replyPreview || '';
            var replySender = s.row.dataset.replySender || '';
            s.bubble.style.transition = 'transform 0.2s ease';
            s.bubble.style.transform = '';
            slideToReplyState.row = null;
            slideToReplyState.bubble = null;
            slideToReplyState.isSliding = false;
            if (triggered) {
                var payload = { id: replyId, body: replyBody, sender_name: replySender };
                setTimeout(function() { setReplyTo(payload); }, 0);
            }
        }
        container.querySelectorAll('.chat-msg-row[data-can-reply="1"]').forEach(function(row) {
            var bubble = row.querySelector('.chat-msg-bubble');
            if (!bubble) return;
            bubble.addEventListener('touchstart', onPointerStart, { passive: false });
            bubble.addEventListener('touchmove', onPointerMove, { passive: false });
            bubble.addEventListener('touchend', onPointerEnd, { passive: true });
            bubble.addEventListener('mousedown', onPointerStart);
        });
        if (!slideToReplyState.docBound) {
            slideToReplyState.docBound = true;
            document.addEventListener('mousemove', onPointerMove);
            document.addEventListener('mouseup', onPointerEnd);
        }
    }

    function loadMoreMessages() {
        if (!currentOtherUserId || loadingMoreMessages || !hasMoreMessages || messagesCache.length === 0) return;
        loadingMoreMessages = true;
        const cont = el(isWidget ? 'messages' : 'messages-container');
        showMessagesLoadMorePreloader(cont);
        const beforeId = messagesCache[0].id;
        const url = routes.messages(currentOtherUserId) + '?before_id=' + beforeId + '&limit=50';
        fetchJson(url).then(function(data) {
            const older = data.messages || [];
            messagesCache = older.concat(messagesCache);
            hasMoreMessages = !!data.has_more;
            if (cont) renderMessages(messagesCache, cont, { scrollToBottom: false, loadMorePrepend: true });
        }).catch(function() {}).finally(function() {
            loadingMoreMessages = false;
            hideMessagesLoadMorePreloader(cont);
        });
    }

    function onMessagesScroll(ev) {
        const cont = ev.target;
        const contId = isWidget ? 'chat-widget-messages' : 'chat-messages-container';
        if (cont.id !== contId || !currentOtherUserId || loadingMoreMessages || !hasMoreMessages) return;
        if (cont.scrollTop < 80) loadMoreMessages();
    }

    function setReplyTo(msg) {
        replyToMessage = msg;
        const previewWrap = el('reply-preview');
        const previewBody = el('reply-preview-body');
        if (previewWrap) previewWrap.classList.remove('d-none');
        if (previewBody) previewBody.textContent = (msg.sender_name ? msg.sender_name + ': ' : '') + (msg.body || '');
        const input = el('message-input');
        if (input) input.focus();
    }

    function clearReply() {
        replyToMessage = null;
        const previewWrap = el('reply-preview');
        if (previewWrap) previewWrap.classList.add('d-none');
    }

    function showReplyPreview() { setReplyTo(replyToMessage); }
    function hideReplyPreview() { clearReply(); }

    function showHistory(messageId) {
        fetchJson(routes.history(messageId)).then(data => {
            const msg = data.message || {};
            const sentStr = formatHistoryDateTime(msg.created_at);
            const hasDeletedAt = !!msg.deleted_for_everyone_at;
            const hasUpdatedAt = !!(msg.created_at && msg.updated_at && msg.created_at !== msg.updated_at);
            const changedAt = hasDeletedAt ? formatHistoryDateTime(msg.deleted_for_everyone_at) : (hasUpdatedAt ? formatHistoryDateTime(msg.updated_at) : null);
            const changedLabel = hasDeletedAt ? (CHAT_STR.deletedAt || 'Deleted at') : (CHAT_STR.updatedAt || 'Updated at');
            const readBy = (data.read_by || []).map(function(r) { return (r.name || '') + ' at ' + formatHistoryDateTime(r.read_at); }).join('<br>');
            let html = '<div class="text-start">' +
                '<p><strong>Sent:</strong> ' + sentStr + '</p>';
            if (changedAt) html += '<p><strong>' + changedLabel + ':</strong> ' + changedAt + '</p>';
            if (readBy) html += '<p><strong>Read by:</strong><br>' + readBy + '</p>';
            html += '</div>';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Message History', html: html, width: 400, confirmButtonText: 'OK' });
            } else {
                let alertText = 'Message History\nSent: ' + sentStr;
                if (changedAt) alertText += '\n' + changedLabel + ': ' + changedAt;
                if (readBy) alertText += '\n\nRead by:\n' + readBy.replace(/<br>/g, '\n');
                alert(alertText);
            }
        });
    }

    function deleteForMe(messageId) {
        const doDelete = () => {
            showPreloader();
            fetch(routes.deleteForMe, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ message_id: messageId }) })
                .then(r => r.json()).then(() => { if (currentOtherUserId) openThread(currentOtherUserId); poll(); }).catch(() => {}).finally(() => { hidePreloader(); });
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: CHAT_STR.removeForMeConfirmTitle || 'Remove for you?', text: CHAT_STR.removeForMeConfirmText || 'This message will be removed from your view only.', icon: 'question', showCancelButton: true, confirmButtonText: CHAT_STR.yesRemove || 'Yes, remove' }).then((result) => { if (result.isConfirmed) doDelete(); });
        } else {
            if (confirm(CHAT_STR.removeForMeConfirmTitle || 'Remove this message for you?')) doDelete();
        }
    }

    function deleteForAll(messageId) {
        const doDelete = () => {
            showPreloader();
            fetch(routes.deleteForAll, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ message_id: messageId }) })
                .then(r => r.json())
                .then((data) => {
                    if (data.message) {
                        const idx = messagesCache.findIndex(function(m) { return m.id === data.message.id; });
                        if (idx !== -1) messagesCache[idx] = data.message;
                        else messagesCache.push(data.message);
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        if (cont) renderMessages(messagesCache, cont, { scrollToBottom: false });
                    }
                    if (currentOtherUserId) poll();
                })
                .catch(() => {})
                .finally(() => { hidePreloader(); });
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: CHAT_STR.removeForEveryoneConfirmTitle || 'Remove for everyone?', text: CHAT_STR.removeForEveryoneConfirmText || 'The message will show as deleted for everyone. This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: CHAT_STR.yesRemove || 'Yes, remove', confirmButtonColor: '#d33' }).then((result) => { if (result.isConfirmed) doDelete(); });
        } else {
            if (confirm(CHAT_STR.removeForEveryoneConfirmTitle || 'Remove this message for everyone? This cannot be undone.')) doDelete();
        }
    }

    function deleteConversation() {
        if (!currentOtherUserId || !routes.deleteConversation) return;
        const doDelete = () => {
            showPreloader();
            fetch(routes.deleteConversation, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ other_user_id: currentOtherUserId }) })
                .then(r => r.json())
                .then(() => { if (isWidget && typeof closeWidgetThread === 'function') closeWidgetThread(); else if (typeof closeChatThread === 'function') closeChatThread(); poll(); })
                .catch(() => {})
                .finally(() => { hidePreloader(); });
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: CHAT_STR.deleteChatConfirmTitle || 'Delete Chat?', text: CHAT_STR.deleteChatConfirmText || 'All messages and files in this chat will be permanently deleted. This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: CHAT_STR.deleteChat || 'Delete Chat', confirmButtonColor: '#d33' }).then((result) => { if (result.isConfirmed) doDelete(); });
        } else {
            if (confirm(CHAT_STR.deleteChatConfirmTitle || 'Delete this chat permanently? All messages and files will be removed.')) doDelete();
        }
    }

    function onMessageInputKeydown(e) {
        if (e.key !== 'Enter') return;
        if (e.ctrlKey || e.shiftKey) return;
        e.preventDefault();
        sendMessage();
    }

    function sendMessage() {
        const input = el(isWidget ? 'message-input' : 'message-input');
        if (!input || !currentOtherUserId) return;
        const body = (input.value || '').trim();
        if (!body) return;
        input.value = '';
        const payload = { recipient_id: currentOtherUserId, body, reply_to_message_id: replyToMessage ? replyToMessage.id : null };
        clearReply();
        showPreloader();
        fetch(routes.send, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
            .then(r => r.json())
            .then(data => {
                if (data.message) {
                    const cont = el(isWidget ? 'messages' : 'messages-container');
                    messagesCache = messagesCache.concat(data.message);
                    if (cont) renderMessages(messagesCache, cont);
                }
                poll();
            })
            .catch(() => {})
            .finally(() => { hidePreloader(); });
    }

    function sendFile(inputEl) {
        if (!inputEl?.files?.length || !currentOtherUserId) return;
        const file = inputEl.files[0];
        if (CHAT_FILE_SIZE_LIMITED && file.size > CHAT_FILE_MAX_SIZE_BYTES) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'File too large', text: 'Maximum file size is ' + CHAT_FILE_MAX_SIZE_MB + ' MB.' });
            } else {
                alert('File too large. Maximum file size is ' + CHAT_FILE_MAX_SIZE_MB + ' MB.');
            }
            inputEl.value = '';
            return;
        }
        const fd = new FormData();
        fd.append('recipient_id', currentOtherUserId);
        fd.append('file', file);
        fd.append('_token', csrf);
        inputEl.value = '';
        showPreloader();
        fetch(routes.sendFile, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to send file.' });
                    } else {
                        alert(data.error || 'Failed to send file.');
                    }
                    return;
                }
                if (data.message) {
                    const cont = el(isWidget ? 'messages' : 'messages-container');
                    messagesCache = messagesCache.concat(data.message);
                    if (cont) renderMessages(messagesCache, cont);
                }
                poll();
            })
            .catch(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to send file.' });
                }
            })
            .finally(() => { hidePreloader(); });
    }

    function formatTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        const now = new Date();
        const diff = now - d;
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function formatHistoryDateTime(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return '-';
        const hours = d.getHours();
        const mins = d.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const h12 = hours % 12 || 12;
        const timeStr = h12 + ':' + String(mins).padStart(2, '0') + ' ' + ampm;
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return timeStr + ' (' + day + '-' + month + '-' + year + ')';
    }

    function isRecent(iso) {
        const d = new Date(iso);
        return (new Date() - d) < 120000;
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    // Init
    if (isWidget) {
        function closeWidgetThread() {
            document.getElementById('chat-widget-thread-panel').classList.add('d-none');
            document.getElementById('chat-widget-conversation-list').classList.remove('d-none');
            document.getElementById('chat-widget-search-wrap')?.classList.remove('d-none');
            currentOtherUserId = null;
            clearReply();
            if (typeof startPollTimer === 'function') startPollTimer();
        }
        function closeWidgetPanel() {
            closeWidgetThread();
            document.getElementById('chat-widget-panel').classList.add('d-none');
        }
        document.getElementById('chat-widget-toggle').addEventListener('click', () => {
            var panel = document.getElementById('chat-widget-panel');
            if (panel.classList.contains('d-none')) {
                panel.classList.remove('d-none');
                poll();
            } else {
                closeWidgetPanel();
            }
        });
        document.getElementById('chat-widget-minimize').addEventListener('click', closeWidgetPanel);
        document.getElementById('chat-widget-back').addEventListener('click', () => {
            closeWidgetThread();
        });
        document.getElementById('chat-widget-close-thread')?.addEventListener('click', closeWidgetThread);
        document.getElementById('chat-widget-delete-conversation')?.addEventListener('click', function(e) {
            e.preventDefault();
            deleteConversation();
        });
        document.getElementById('chat-widget-send').addEventListener('click', sendMessage);
        document.getElementById('chat-widget-attach').addEventListener('click', () => document.getElementById('chat-widget-file-input').click());
        document.getElementById('chat-widget-file-input').addEventListener('change', function() { sendFile(this); });
        document.getElementById('chat-widget-message-input').addEventListener('keydown', onMessageInputKeydown);
        document.getElementById('chat-widget-reply-cancel')?.addEventListener('click', clearReply);
        document.getElementById('chat-widget-messages')?.addEventListener('scroll', onMessagesScroll);
        document.getElementById('chat-widget-search-user')?.addEventListener('input', function() { renderConversationList(conversationsCache); });
        document.getElementById('chat-widget-view-all')?.addEventListener('click', function(e) {
            if (currentOtherUserId) {
                e.preventDefault();
                const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                const openParam = (conv?.user?.uid != null && conv.user.uid !== '') ? conv.user.uid : currentOtherUserId;
                const base = this.getAttribute('href');
                window.location.href = base + (base.indexOf('?') !== -1 ? '&' : '?') + 'open=' + encodeURIComponent(openParam);
            }
        });
    } else {
        function closeChatThread() {
            document.getElementById('chat-thread-placeholder').classList.remove('d-none');
            document.getElementById('chat-thread-panel').classList.add('d-none');
            currentOtherUserId = null;
            clearReply();
            if (typeof startPollTimer === 'function') startPollTimer();
            if (window.history && window.history.replaceState) {
                const params = new URLSearchParams(window.location.search);
                params.delete('open');
                const url = params.toString() ? (window.location.pathname + '?' + params.toString()) : window.location.pathname;
                window.history.replaceState({}, '', url);
            }
        }
        document.getElementById('chat-back-to-list')?.addEventListener('click', closeChatThread);
        document.getElementById('chat-close-thread')?.addEventListener('click', closeChatThread);
        document.getElementById('chat-delete-conversation')?.addEventListener('click', function(e) { e.preventDefault(); deleteConversation(); });
        document.getElementById('chat-reply-cancel')?.addEventListener('click', clearReply);
        document.getElementById('chat-messages-container')?.addEventListener('scroll', onMessagesScroll);
        document.getElementById('chat-send-btn').addEventListener('click', sendMessage);
        document.getElementById('chat-attach-btn')?.addEventListener('click', () => document.getElementById('chat-file-input').click());
        document.getElementById('chat-file-input')?.addEventListener('change', function() { sendFile(this); });
        document.getElementById('chat-message-input')?.addEventListener('keydown', onMessageInputKeydown);
        document.getElementById('chat-search-user')?.addEventListener('input', () => renderConversationList(conversationsCache));
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (typeof $ !== 'undefined') applyChatHeights();
            }, 100);
        });
        if (typeof $ !== 'undefined') {
            $(document).ready(function() { applyChatHeights(); });
        } else {
            document.addEventListener('DOMContentLoaded', function runOnce() {
                if (typeof $ !== 'undefined') applyChatHeights();
            });
        }
    }

    function startPollTimer() {
        if (pollTimer) clearInterval(pollTimer);
        var ms = (typeof document !== 'undefined' && document.hidden) ? POLL_MS_HIDDEN : (currentOtherUserId ? POLL_MS_THREAD_OPEN : POLL_MS);
        pollTimer = setInterval(poll, ms);
    }
    function onVisibilityChange() {
        startPollTimer();
        if (typeof document !== 'undefined' && !document.hidden) poll();
    }
    if (typeof document !== 'undefined' && document.addEventListener) {
        document.addEventListener('visibilitychange', onVisibilityChange);
    }

    if (isWidget) {
        poll();
        startPollTimer();
    } else {
        showPreloader();
        fetchJson(routes.activity).then(function(data) {
            if (data.chat && data.chat.conversations) {
                conversationsCache = data.chat.conversations;
                renderConversationList(conversationsCache);
                const params = new URLSearchParams(window.location.search);
                const openParam = params.get('open');
                if (openParam) {
                    const conv = conversationsCache.find(function(c) {
                        if (!c.user) return false;
                        if (c.user.uid != null && c.user.uid !== '' && String(c.user.uid) === String(openParam)) return true;
                        return String(c.user.id) === String(openParam);
                    });
                    if (conv && conv.user) {
                        openThread(conv.user.id);
                    }
                }
            }
        }).catch(function() {}).finally(function() {
            hidePreloader();
            startPollTimer();
        });
    }
})();
</script>

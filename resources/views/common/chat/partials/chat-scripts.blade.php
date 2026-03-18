@php
    $isWidget = $isWidget ?? false;
    $prefix = $isWidget ? 'chat-widget-' : 'chat-';
    $wrap = $isWidget ? 'chat-widget' : 'chat-app';
    $getCurrentTranslation = $getCurrentTranslation ?? (function_exists('getCurrentTranslation') ? getCurrentTranslation() : []);
@endphp
<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
<script type="module">
    import { polyfillCountryFlagEmojis } from "/assets/js/country-flag-emoji-polyfill.js";
    polyfillCountryFlagEmojis();
</script>
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
        react: @json(route('chat.react')),
        removeReaction: @json(route('chat.removeReaction')),
        edit: @json(route('chat.edit')),
        forward: @json(route('chat.forward')),
        groups: @json(route('chat.groups')),
        groupCreate: @json(route('chat.groupCreate')),
        groupAddMembers: @json(route('chat.groupAddMembers')),
        groupMessages: (gid) => @json(route('chat.groupMessages', ['groupId' => ':id'])).replace(':id', gid),
        sendGroup: @json(route('chat.sendGroup')),
        sendGroupFile: @json(route('chat.sendGroupFile')),
        groupUpdate: @json(route('chat.groupUpdate')),
        groupSetMemberRole: @json(route('chat.groupSetMemberRole')),
        groupRemoveMember: @json(route('chat.groupRemoveMember')),
        groupLeave: @json(route('chat.groupLeave')),
        groupDelete: @json(route('chat.groupDelete')),
        setNickname: @json(route('chat.setNickname')),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute?.('content') || '';
    const currentUserId = @json(auth()->id());
    const currentUserName = @json(auth()->user()->name ?? 'Me');
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
        chatbot: @json($getCurrentTranslation['chatbot'] ?? 'Chatbot'),
        forward: @json($getCurrentTranslation['forward'] ?? 'Forward'),
        react: @json($getCurrentTranslation['react'] ?? 'React'),
        replyingTo: @json($getCurrentTranslation['replying_to'] ?? 'Replying to'),
        edit: @json($getCurrentTranslation['edit'] ?? 'Edit'),
        edited: @json($getCurrentTranslation['edited'] ?? 'Edited'),
        editingMessage: @json($getCurrentTranslation['editing_message'] ?? 'Editing message'),
        createGroup: @json($getCurrentTranslation['create_group'] ?? 'Create group'),
        groupName: @json($getCurrentTranslation['group_name'] ?? 'Group name'),
        addMembers: @json($getCurrentTranslation['add_members'] ?? 'Add members'),
        admin: @json($getCurrentTranslation['admin'] ?? 'Admin'),
        admins: @json($getCurrentTranslation['admins'] ?? 'Admins'),
        members: @json($getCurrentTranslation['members'] ?? 'Members'),
        member: @json($getCurrentTranslation['member'] ?? 'Member'),
        owner: @json($getCurrentTranslation['owner'] ?? 'Owner'),
        groupInfo: @json($getCurrentTranslation['group_info'] ?? 'Group info'),
        changeGroupPhoto: @json($getCurrentTranslation['change_group_photo'] ?? 'Change group photo'),
        makeAdmin: @json($getCurrentTranslation['make_admin'] ?? 'Make admin'),
        removeAdmin: @json($getCurrentTranslation['remove_admin'] ?? 'Remove admin'),
        makeAdminSuccess: @json($getCurrentTranslation['make_admin_success'] ?? 'Member is now an admin.'),
        removeAdminSuccess: @json($getCurrentTranslation['remove_admin_success'] ?? 'Admin role removed from member.'),
        removeFromGroup: @json($getCurrentTranslation['remove_from_group'] ?? 'Remove from group'),
        deleteGroup: @json($getCurrentTranslation['delete_group'] ?? 'Delete group'),
        deleteGroupConfirm: @json($getCurrentTranslation['delete_group_confirm'] ?? 'Permanently delete this group and all messages? This cannot be undone.'),
        leaveGroup: @json($getCurrentTranslation['leave_group'] ?? 'Leave group'),
        leaveGroupConfirm: @json($getCurrentTranslation['leave_group_confirm'] ?? 'Leave this group? You can be added again by a member.'),
        leaveGroupSuccess: @json($getCurrentTranslation['leave_group_success'] ?? 'You have left the group.'),
        nickname: @json($getCurrentTranslation['nickname'] ?? 'Nickname'),
        setNickname: @json($getCurrentTranslation['set_nickname'] ?? 'Set nickname'),
        clearNickname: @json($getCurrentTranslation['clear_nickname'] ?? 'Clear nickname'),
        setMyNickname: @json($getCurrentTranslation['set_my_nickname'] ?? 'Set my nickname'),
        clearMyNickname: @json($getCurrentTranslation['clear_my_nickname'] ?? 'Clear my nickname'),
        setNicknames: @json($getCurrentTranslation['set_nicknames'] ?? 'Set nicknames'),
        originalName: @json($getCurrentTranslation['original_name'] ?? 'Original'),
        me: @json($getCurrentTranslation['me'] ?? 'Me'),
        clearNicknameConfirmTitle: @json($getCurrentTranslation['clear_nickname_confirm_title'] ?? 'Clear nickname?'),
        clearNicknameConfirmText: @json($getCurrentTranslation['clear_nickname_confirm_text'] ?? 'Clear the nickname for %s?'),
        clearMyNicknameConfirmText: @json($getCurrentTranslation['clear_my_nickname_confirm_text'] ?? 'Clear your nickname?'),
        nicknameSaved: @json($getCurrentTranslation['nickname_saved'] ?? 'Nickname saved'),
        nicknameCleared: @json($getCurrentTranslation['nickname_cleared'] ?? 'Nickname cleared.'),
        removeFromGroupConfirm: @json($getCurrentTranslation['remove_from_group_confirm'] ?? 'Remove this member from the group?'),
        confirm: @json($getCurrentTranslation['confirm'] ?? 'Confirm'),
        cancel: @json($getCurrentTranslation['cancel'] ?? 'Cancel'),
        close: @json($getCurrentTranslation['close'] ?? 'Close'),
    };

    let currentOtherUserId = null;
    let currentGroupId = null;
    let currentGroupData = null;
    let replyToMessage = null;
    let editingMessageId = null;
    let pollTimer = null;
    let conversationsCache = [];
    let messagesCache = [];
    let groupEventsCache = [];
    let contactEventsCache = [];
    let hasMoreMessages = false;
    let loadingMoreMessages = false;
    const EMOJI_LIST = ['😀','😃','😄','😁','😅','😂','🤣','😊','😇','🙂','😉','😍','🥰','😘','😗','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👋','🤚','🖐️','✋','🖖','👏','🙌','🤲','🤝','🙏','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟'];
    const QUICK_REACT_EMOJIS = ['👍','❤️','😂','😮','😢','🙏'];

    function el(id) { return document.getElementById(prefix + id) || document.getElementById(id); }

    function fetchJson(url, options = {}) {
        const opts = { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...options.headers } };
        if (options.method && options.method !== 'GET') {
            opts.headers['X-CSRF-TOKEN'] = csrf;
            opts.headers['Content-Type'] = options.contentType || 'application/json';
        }
        return fetch(url, { ...options, ...opts }).then(r => r.json());
    }

    /** Display name for the contact/recipient in 1-to-1: only "my nickname for them" or their real name. Never use their_nickname_for_me (that is the name they set for me, not for them). */
    function getRecipientDisplayName(user) {
        if (!user || user.id === currentUserId) return '';
        const contactNick = (user.contact_nickname && String(user.contact_nickname).trim()) ? String(user.contact_nickname).trim() : '';
        const name = (user.name && String(user.name).trim()) ? String(user.name).trim() : '';
        return contactNick || name || '';
    }

    function update1to1ThreadHeader() {
        if (!currentOtherUserId || currentGroupId) return;
        const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
        if (!conv || !conv.user) return;
        const displayName = getRecipientDisplayName(conv.user) || 'User';
        const el1 = document.getElementById('chat-thread-name');
        const el2 = document.getElementById('chat-widget-thread-name');
        if (el1) el1.textContent = displayName;
        if (el2) el2.textContent = displayName;
        const initial = (displayName || 'U').charAt(0).toUpperCase();
        const ai1 = document.getElementById('chat-thread-avatar-initial');
        const ai2 = document.getElementById('chat-widget-thread-avatar-initial');
        if (ai1) ai1.textContent = initial;
        if (ai2) ai2.textContent = initial;
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
                if (currentGroupId) {
                    var groupConv = (data.chat.conversations || []).find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                    if (groupConv && groupConv.group) {
                        currentGroupData = groupConv.group;
                        var name = groupConv.group.name || 'Group';
                        var avatar = groupConv.group.image_url || '';
                        if (isWidget) {
                            var avatarEl = document.getElementById('chat-widget-thread-avatar');
                            var initialEl = document.getElementById('chat-widget-thread-avatar-initial');
                            if (avatarEl) { avatarEl.src = avatar; avatarEl.style.display = avatar ? 'block' : 'none'; }
                            if (initialEl) { initialEl.textContent = name.charAt(0).toUpperCase(); initialEl.style.display = avatar ? 'none' : 'flex'; }
                            document.getElementById('chat-widget-thread-name').textContent = name;
                        } else {
                            var avatarEl = document.getElementById('chat-thread-avatar');
                            var initialEl = document.getElementById('chat-thread-avatar-initial');
                            if (avatarEl) { avatarEl.src = avatar; avatarEl.style.display = avatar ? 'block' : 'none'; }
                            if (initialEl) { initialEl.textContent = name.charAt(0).toUpperCase(); initialEl.style.display = avatar ? 'none' : 'flex'; }
                            document.getElementById('chat-thread-name').textContent = name;
                        }
                    } else {
                        // Current group no longer in conversations (deleted or left) – close the open thread and any related modals.
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            document.querySelectorAll('.modal.show').forEach(function(m) {
                                var inst = bootstrap.Modal.getInstance(m);
                                if (inst) inst.hide();
                            });
                        }
                        if (isWidget && typeof closeWidgetThread === 'function') closeWidgetThread();
                        else if (typeof closeChatThread === 'function') closeChatThread();
                    }
                }
                if (currentOtherUserId && !currentGroupId) {
                    update1to1ThreadHeader();
                }
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
                                    renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false }));
                                }
                            }
                        }
                    }
                }
            }
        }).catch(() => {});
        const activeId = currentGroupId ? ('g' + currentGroupId) : (currentOtherUserId ? String(currentOtherUserId) : null);
        if (activeId && messagesCache.length > 0) {
            const syncingEl = el('thread-syncing');
            if (syncingEl) syncingEl.classList.remove('d-none');
            const url = currentGroupId ? (routes.groupMessages(currentGroupId) + '?limit=50') : (routes.messages(currentOtherUserId) + '?limit=50');
            const markReadPayload = currentGroupId ? { group_id: currentGroupId } : { other_user_id: currentOtherUserId };
            fetchJson(url).then(data => {
                const cont = el(isWidget ? 'messages' : 'messages-container');
                if (!cont || document.getElementById(prefix + 'thread-panel')?.classList.contains('d-none') === true) return;
                fetchJson(routes.markRead, { method: 'POST', body: JSON.stringify(markReadPayload) }).catch(function() {});
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
                        if ((String(existing.status || '') !== String(inMsg.status || '')) || (String(existing.read_at || '') !== String(inMsg.read_at || '')) || (JSON.stringify(existing.reactions || []) !== JSON.stringify(inMsg.reactions || []))) hasStatusUpdates = true;
                        cacheById[inMsg.id] = inMsg;
                    }
                });
                let hasEventsUpdates = false;
                if (currentGroupId && (data.group_events || []).length >= 0) {
                    const prevEventIds = (groupEventsCache || []).map(function(e) { return e.id; }).join(',');
                    const newEvents = data.group_events || [];
                    groupEventsCache = newEvents;
                    const newEventIds = newEvents.map(function(e) { return e.id; }).join(',');
                    if (prevEventIds !== newEventIds) hasEventsUpdates = true;
                }
                if (currentOtherUserId && (data.contact_events || []).length >= 0) {
                    const prevContactIds = (contactEventsCache || []).map(function(e) { return e.id; }).join(',');
                    const newContactEvents = data.contact_events || [];
                    contactEventsCache = newContactEvents;
                    const newContactIds = newContactEvents.map(function(e) { return e.id; }).join(',');
                    if (prevContactIds !== newContactIds) hasEventsUpdates = true;
                }
                const hasAnyUpdates = hasNewMessages || hasStatusUpdates || hasEventsUpdates;
                if (!hasAnyUpdates) return;
                const pollIds = new Set(incoming.map(function(m) { return m.id; }));
                const older = messagesCache.filter(function(m) { return !pollIds.has(m.id); });
                messagesCache = older.concat(incoming);
                if (messagesCache.length <= 50) hasMoreMessages = !!data.has_more;
                if (cont.querySelector('.dropdown-menu.show')) return;
                renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false }));
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
            const isGroup = c.type === 'group';
            if (!isGroup && c.user && c.user.id === currentUserId) return;
            const userDisplayName = !isGroup && c.user ? (getRecipientDisplayName(c.user) || '') : '';
            const name = (isGroup ? (c.group?.name || '') : userDisplayName).toLowerCase();
            if (search && !name.includes(search)) return;
            const isChatbot = !!(c.user?.is_automation_chatbot);
            const last = c.last_message;
            const unread = c.unread_count || 0;
            let avatar = '', initial = '?';
            if (isGroup) {
                initial = (c.group?.name || 'G').charAt(0).toUpperCase();
                avatar = c.group?.image_url ? `<img src="${escapeHtml(c.group.image_url)}" alt="">` : '';
            } else {
                avatar = c.user?.image_url ? `<img src="${escapeHtml(c.user.image_url)}" alt="">` : '';
                initial = (userDisplayName || '?').charAt(0).toUpperCase();
            }
            const lastText = last ? (last.deleted_for_everyone ? (CHAT_STR.thisMessageWasDeleted || 'This message was deleted') : (last.type === 'file' ? (last.file_name || 'File') : (last.body || '').substring(0, 40))) : 'No messages yet';
            const time = last ? formatConversationListTime(last.created_at) : '';
            let nameWithStatus = escapeHtml(isGroup ? (c.group?.name || 'Group') : (userDisplayName || ''));
            if (!isGroup) {
                const isActive = !!(c.user?.last_seen_at && isRecent(c.user.last_seen_at));
                nameWithStatus = escapeHtml(userDisplayName || '');
                if (isActive) nameWithStatus += ' <span class="chat-active-dot ms-1" title="Active"></span>';
                if (isChatbot) nameWithStatus = '<i class="fa-solid fa-thumbtack text-primary me-1" title="' + (CHAT_STR.chatbot || 'Chatbot') + '"></i>' + nameWithStatus + ' <span class="badge badge-sm badge-info ms-1">' + (CHAT_STR.chatbot || 'Chatbot') + '</span>';
            } else {
                nameWithStatus = '<i class="fa-solid fa-users me-1 text-muted"></i>' + nameWithStatus;
                const memCount = c.group?.member_count || 0;
                const admCount = c.group?.admin_count || 0;
                if (memCount > 0 || admCount > 0) nameWithStatus += ' <span class="badge badge-sm badge-light ms-1" title="' + (CHAT_STR.members || 'Members') + '">' + memCount + ' ' + (CHAT_STR.members || 'members') + (admCount > 0 ? ' · ' + admCount + ' ' + (CHAT_STR.admins || 'admins') : '') + '</span>';
            }
            const dataAttrs = isGroup ? `data-group-id="${c.group?.id}" data-user-id=""` : `data-user-id="${c.user?.id}" data-group-id=""`;
            html += `<div class="chat-conv-item p-3 border-bottom cursor-pointer d-flex align-items-center ${isChatbot ? 'chat-conv-item-chatbot' : ''}" ${dataAttrs} data-unread="${unread}">
                <div class="symbol symbol-45px me-3 flex-shrink-0">${avatar || `<span class="symbol-label bg-secondary text-white fw-bold">${initial}</span>`}</div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="fw-bold text-gray-800 text-truncate me-2 flex-grow-1 min-w-0">${nameWithStatus}</span>
                        <div class="d-flex align-items-center flex-shrink-0 gap-2">
                            <span class="fs-8 text-muted">${time}</span>
                            ${unread ? `<span class="badge badge-sm badge-danger">${unread}</span>` : ''}
                        </div>
                    </div>
                    <div class="fs-7 text-muted text-truncate mt-1">${escapeHtml(lastText)}</div>
                </div>
            </div>`;
        });
        container.innerHTML = html || '<div class="p-3 text-muted text-center">' + (CHAT_STR.noConversations || 'No conversations') + '</div>';
        container.querySelectorAll('.chat-conv-item').forEach(node => {
            node.addEventListener('click', () => {
                const gid = node.dataset.groupId ? parseInt(node.dataset.groupId, 10) : null;
                const uid = node.dataset.userId ? parseInt(node.dataset.userId, 10) : null;
                if (gid) openThread(null, gid); else openThread(uid, null);
            });
        });
    }

    function openThread(otherUserId, groupId) {
        currentOtherUserId = otherUserId || null;
        currentGroupId = groupId || null;
        clearReply();
        clearEdit();
        const conv = conversationsCache.find(c => (groupId && c.type === 'group' && c.group?.id === groupId) || (otherUserId && c.user?.id === otherUserId));
        if (conv && (conv.unread_count || 0) > 0) {
            conv.unread_count = 0;
            renderConversationList(conversationsCache);
            if (isWidget) updateWidgetBadge(conversationsCache);
        }
        let name = 'User', avatar = '', status = '';
        if (currentGroupId && conv?.group) {
            currentGroupData = conv.group;
            name = conv.group.name || 'Group';
            avatar = conv.group.image_url || '';
            const parts = [];
            if (conv.group.creator_name) parts.push('Created by ' + conv.group.creator_name);
            const admins = (conv.group.members || []).filter(function(m) { return (m.role || '') === 'admin'; });
            if (admins.length > 0) parts.push((CHAT_STR.admins || 'Admins') + ': ' + admins.map(function(a) { return a.name || ''; }).filter(Boolean).join(', '));
            if (conv.group.member_count != null) parts.push(conv.group.member_count + ' ' + (CHAT_STR.members || 'members'));
            status = parts.join(' · ');
            const groupInfoItem = document.getElementById(isWidget ? 'chat-widget-group-info-item' : 'chat-group-info-item');
            if (groupInfoItem) groupInfoItem.classList.remove('d-none');
            const setNicknamesItem = document.getElementById(isWidget ? 'chat-widget-set-nicknames-item' : 'chat-set-nicknames-item');
            if (setNicknamesItem) setNicknamesItem.classList.add('d-none');
            const leaveGroupItem = document.getElementById(isWidget ? 'chat-widget-leave-group-item' : 'chat-leave-group-item');
            const deleteConversationBtn = document.getElementById(isWidget ? 'chat-widget-delete-conversation' : 'chat-delete-conversation');
            const deleteConversationItem = deleteConversationBtn ? (deleteConversationBtn.closest('li') || deleteConversationBtn) : null;
            const isCreator = conv.group && conv.group.created_by_user_id != null && conv.group.created_by_user_id === currentUserId;
            if (leaveGroupItem) leaveGroupItem.classList.toggle('d-none', !!isCreator);
            if (deleteConversationItem) deleteConversationItem.classList.add('d-none');
        } else if (conv?.user) {
            currentGroupData = null;
            name = getRecipientDisplayName(conv.user) || 'User';
            avatar = conv.user.id !== currentUserId ? (conv.user.image_url || '') : '';
            status = conv.user.last_seen_at ? (isRecent(conv.user.last_seen_at) ? 'Active now' : 'Last seen ' + formatTime(conv.user.last_seen_at)) : '';
            const groupInfoItem = document.getElementById(isWidget ? 'chat-widget-group-info-item' : 'chat-group-info-item');
            if (groupInfoItem) groupInfoItem.classList.add('d-none');
            const setNicknamesItem = document.getElementById(isWidget ? 'chat-widget-set-nicknames-item' : 'chat-set-nicknames-item');
            if (setNicknamesItem) setNicknamesItem.classList.remove('d-none');
            const leaveGroupItem = document.getElementById(isWidget ? 'chat-widget-leave-group-item' : 'chat-leave-group-item');
            if (leaveGroupItem) leaveGroupItem.classList.add('d-none');
            const deleteConversationBtn = document.getElementById(isWidget ? 'chat-widget-delete-conversation' : 'chat-delete-conversation');
            const deleteConversationItem = deleteConversationBtn ? (deleteConversationBtn.closest('li') || deleteConversationBtn) : null;
            if (deleteConversationItem) deleteConversationItem.classList.remove('d-none');
            if (deleteConversationBtn) deleteConversationBtn.innerHTML = '<i class="fa-solid fa-trash me-2"></i>' + (CHAT_STR.deleteChat || 'Delete Chat');
        }

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
        if (currentGroupId) {
            contactEventsCache = [];
            fetchJson(routes.groupMessages(currentGroupId) + '?limit=50').then(data => {
                messagesCache = data.messages || [];
                groupEventsCache = data.group_events || [];
                hasMoreMessages = !!data.has_more;
                if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
            });
            fetchJson(routes.markRead, { method: 'POST', body: JSON.stringify({ group_id: currentGroupId }) });
        } else {
            groupEventsCache = [];
            fetchJson(routes.messages(currentOtherUserId) + '?limit=50').then(data => {
                messagesCache = data.messages || [];
                contactEventsCache = data.contact_events || [];
                hasMoreMessages = !!data.has_more;
                if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
            });
            fetchJson(routes.markRead, { method: 'POST', body: JSON.stringify({ other_user_id: currentOtherUserId }) });
        }
        if (!isWidget && window.history && window.history.replaceState) {
            let openParam = '';
            if (currentGroupId) openParam = 'g' + currentGroupId;
            else if (conv?.user) openParam = (conv.user.uid != null && conv.user.uid !== '') ? conv.user.uid : String(currentOtherUserId);
            const params = new URLSearchParams(window.location.search);
            if (openParam) params.set('open', openParam); else params.delete('open');
            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
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

    function mergeMessagesWithEvents(messages, events) {
        const list = (messages || []).map(function(m) { return { type: 'message', created_at: m.created_at, sortKey: new Date(m.created_at).getTime(), data: m }; });
        (events || []).forEach(function(e) { list.push({ type: 'event', created_at: e.created_at, sortKey: new Date(e.created_at).getTime(), data: e }); });
        list.sort(function(a, b) { return a.sortKey - b.sortKey; });
        return list.map(function(x) { return x.type === 'event' ? Object.assign({ type: 'event' }, x.data) : x.data; });
    }
    function getMessagesToRender() {
        if (currentGroupId) return mergeMessagesWithEvents(messagesCache, groupEventsCache);
        if (currentOtherUserId) return mergeMessagesWithEvents(messagesCache, contactEventsCache);
        return messagesCache;
    }
    function getRenderOptions(opts) {
        opts = opts || {};
        if (currentGroupId) opts.isGroup = true;
        return opts;
    }
    function refetchGroupMessagesIfOpen() {
        if (!currentGroupId) return;
        const cont = el(isWidget ? 'messages' : 'messages-container');
        fetchJson(routes.groupMessages(currentGroupId) + '?limit=50').then(function(data) {
            messagesCache = data.messages || [];
            groupEventsCache = data.group_events || [];
            if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false }));
        });
    }

    function renderMessages(messages, container, options) {
        if (!container) return;
        options = options || {};
        const scrollToBottom = options.scrollToBottom !== false;
        const loadMorePrepend = !!options.loadMorePrepend;
        const isGroup = !!options.isGroup;
        const inner = getMessagesInner(container);
        if (!inner) return;
        const prevScrollTop = container.scrollTop;
        const prevScrollHeight = container.scrollHeight;
        const prevClientHeight = container.clientHeight;
        const wasAtBottom = prevScrollHeight - (prevScrollTop + prevClientHeight) < 30;
        let html = '';
        messages.forEach(m => {
            if (m.type === 'event') {
                html += '<div class="d-flex justify-content-center mb-2 chat-msg-row chat-event-row" data-event-id="' + escapeHtml(m.id || '') + '"><span class="badge badge-light-secondary px-3 py-2 text-dark" style="font-size: 0.8rem;">' + escapeHtml(m.text || '') + '</span></div>';
                return;
            }
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
                body = makeLinksClickable(m.body || '');
            }
            const replyToRef = m.reply_to || {};
            const replyToId = (replyToRef.id != null && replyToRef.id !== '') ? String(replyToRef.id) : (m.reply_to_message_id != null ? String(m.reply_to_message_id) : '');
            const replyToBody = (replyToRef.body || replyToRef.file_name || '').trim();
            const replyToPreview = (replyToBody || 'Message').substring(0, 80) + (replyToBody.length > 80 ? '…' : '');
            const replyToSender = (replyToRef.sender_name || '').trim();
            const replyQuote = !deletedForEveryone && replyToId ? `<div class="chat-reply-quote-wrap cursor-pointer" data-reply-to-msg-id="${escapeHtml(replyToId)}" title="Jump to message"><span class="chat-reply-quote-bar"></span><div class="chat-reply-quote-inner"><span class="chat-reply-quote-sender">${escapeHtml(replyToSender)}</span><span class="chat-reply-quote-body">${escapeHtml(replyToPreview)}</span></div></div>` : '';
            const replyPreview = (m.type === 'file' ? (m.file_name || 'File') : (m.body || '')).substring(0, 60) + ((m.type === 'file' ? (m.file_name || 'File') : (m.body || '')).length > 60 ? '…' : '');
            const replySender = m.sender?.name || '';
            const canEditMessage = isSent && !deletedForEveryone && m.type === 'text' && (function() {
                if (!m.created_at) return false;
                const createdMs = new Date(m.created_at).getTime();
                return (Date.now() - createdMs) <= 15 * 60 * 1000;
            })();
            const editMenuItem = canEditMessage ? '<li><a class="dropdown-item chat-action" href="#" data-action="edit" data-id="' + m.id + '">' + (CHAT_STR.edit || 'Edit') + '</a></li>' : '';
            const menuItems = deletedForEveryone
                ? '<li><a class="dropdown-item chat-action" href="#" data-action="history" data-id="' + m.id + '">History</a></li>'
                : '<li><a class="dropdown-item chat-action" href="#" data-action="reply" data-id="' + m.id + '" data-reply-preview="' + escapeHtml(replyPreview) + '" data-reply-sender="' + escapeHtml(replySender) + '">Reply</a></li>' + editMenuItem + '<li><a class="dropdown-item chat-action chat-forward-btn" href="#" data-action="forward" data-id="' + m.id + '">' + (CHAT_STR.forward || 'Forward') + '</a></li><li><a class="dropdown-item chat-action" href="#" data-action="history" data-id="' + m.id + '">History</a></li><li><a class="dropdown-item chat-action" href="#" data-action="deleteForMe" data-id="' + m.id + '">' + (CHAT_STR.removeForMe || 'Remove for me') + '</a></li>' + (isSent ? '<li><a class="dropdown-item chat-action text-danger" href="#" data-action="deleteForAll" data-id="' + m.id + '">' + (CHAT_STR.removeForEveryone || 'Remove for everyone') + '</a></li>' : '');
            const canReply = deletedForEveryone ? '0' : '1';
            const isGroup = !!(m.group_id);
            const senderDisplayName = (m.sender_display_name && m.sender_display_name.trim()) ? m.sender_display_name.trim() : (m.sender ? m.sender.name : '');
            const senderLabel = isGroup && (m.sender || senderDisplayName) ? '<div class="chat-msg-sender-name mb-1" style="font-size: 11px; opacity: 0.9;">' + escapeHtml(senderDisplayName) + '</div>' : '';
            const forwardedLabel = !deletedForEveryone && m.forwarded_from ? '<div class="chat-msg-forwarded mb-1" style="font-size: 10px; opacity: 0.8;"><i class="fa-solid fa-share me-1"></i>' + (m.forwarded_from.sender_name ? escapeHtml(m.forwarded_from.sender_name) : '') + '</div>' : '';
            let reactionsHtml = '';
            if (!deletedForEveryone && (m.reactions || []).length > 0) {
                reactionsHtml = '<div class="chat-msg-reactions mt-1 d-flex flex-wrap gap-1 align-items-center" data-msg-id="' + m.id + '">';
                (m.reactions || []).forEach(function(r) {
                    const isMine = (r.user_ids || []).indexOf(currentUserId) !== -1;
                    const namesList = (r.users || []).map(function(u) { return (u.display_name && u.display_name.trim()) ? u.display_name.trim() : (u.name || ''); }).filter(Boolean).join(', ');
                    reactionsHtml += '<span class="badge badge-light-primary chat-reaction-badge' + (isMine ? ' chat-reaction-mine' : '') + '" data-emoji="' + escapeHtml(r.emoji) + '" data-msg-id="' + m.id + '" data-my-reaction="' + (isMine ? '1' : '0') + '" data-bs-toggle="tooltip" data-bs-trigger="hover click" data-bs-placement="top" title="' + escapeHtml(namesList) + '">' + r.emoji + ' ' + (r.count > 1 ? r.count : '') + '</span>';
                });
                reactionsHtml += '</div>';
            }
            let myReactionEmoji = null;
            (m.reactions || []).forEach(function(r) {
                if ((r.user_ids || []).indexOf(currentUserId) !== -1) myReactionEmoji = r.emoji;
            });
            const reactionBtn = deletedForEveryone ? '' : '<div class="dropdown chat-reaction-trigger"><button class="btn btn-icon btn-sm p-0 min-w-auto opacity-50" type="button" data-bs-toggle="dropdown" data-msg-id="' + m.id + '" title="' + (CHAT_STR.react || 'React') + '"><i class="fa-regular fa-face-smile" style="font-size: 14px;"></i></button><ul class="dropdown-menu dropdown-menu-end chat-quick-react-menu">' + QUICK_REACT_EMOJIS.map(function(emo) { const isSelected = emo === myReactionEmoji; return '<li><a class="dropdown-item chat-quick-react' + (isSelected ? ' chat-quick-react-selected' : '') + '" href="#" data-msg-id="' + m.id + '" data-emoji="' + emo + '">' + emo + '</a></li>'; }).join('') + '</ul></div>';
            html += `<div class="d-flex ${isSent ? 'justify-content-end' : 'justify-content-start'} align-items-end mb-2 chat-msg-row" data-msg-id="${m.id}" data-reply-id="${m.id}" data-reply-preview="${escapeHtml(replyPreview)}" data-reply-sender="${escapeHtml(replySender)}" data-can-reply="${canReply}" data-is-sent="${isSent ? '1' : '0'}">
                <div class="d-flex align-items-end gap-1 ${isSent ? 'flex-row-reverse' : ''}" style="max-width: 85%;">
                    <div class="p-2 rounded ${bubbleClass} chat-msg-bubble" style="max-width: 280px; touch-action: pan-y;">
                        ${senderLabel}${forwardedLabel}<div class="chat-msg-body">${replyQuote}${replyQuote ? '<div class="chat-msg-reply-text">' + body + '</div>' : body}</div>
                        <div class="d-flex align-items-center justify-content-end gap-1 mt-1 flex-wrap">
                            <span class="opacity-75" style="font-size: 10px;">${formatTime(m.created_at)}</span>
                            ${isSent && !deletedForEveryone ? `<span class="chat-msg-status-icon" style="font-size: 10px;">${statusIcon}</span>` : ''}
                        </div>
                        ${(m.is_edited) ? '<div class="opacity-75 mt-0" style="font-size: 9px;">' + (CHAT_STR.edited || 'Edited') + '</div>' : ''}
                        ${reactionsHtml}
                    </div>
                    ${reactionBtn}
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
        inner.querySelectorAll('.chat-quick-react').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const msgId = parseInt(this.dataset.msgId, 10);
                const isSelected = this.classList.contains('chat-quick-react-selected');
                if (isSelected) {
                    removeReaction(msgId);
                } else {
                    const emoji = this.dataset.emoji || '👍';
                    addReaction(msgId, emoji);
                }
            });
        });
        inner.querySelectorAll('.chat-reaction-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                const msgId = parseInt(this.dataset.msgId, 10);
                const emoji = this.dataset.emoji || '';
                const isMine = this.dataset.myReaction === '1';
                if (isMine) removeReaction(msgId); else addReaction(msgId, emoji);
            });
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                new bootstrap.Tooltip(badge, { trigger: 'hover click', placement: 'top' });
            }
        });
        inner.querySelectorAll('.chat-reply-quote-wrap[data-reply-to-msg-id]').forEach(quoteEl => {
            quoteEl.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var msgId = quoteEl.getAttribute('data-reply-to-msg-id');
                if (!msgId) return;
                scrollToMessageIdOrLoadMore(msgId);
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
        const active = currentGroupId || currentOtherUserId;
        if (!active || loadingMoreMessages || !hasMoreMessages || messagesCache.length === 0) return Promise.resolve();
        loadingMoreMessages = true;
        const cont = el(isWidget ? 'messages' : 'messages-container');
        showMessagesLoadMorePreloader(cont);
        const beforeId = messagesCache[0].id;
        const url = currentGroupId ? (routes.groupMessages(currentGroupId) + '?before_id=' + beforeId + '&limit=50') : (routes.messages(currentOtherUserId) + '?before_id=' + beforeId + '&limit=50');
        return fetchJson(url).then(function(data) {
            const older = data.messages || [];
            messagesCache = older.concat(messagesCache);
            hasMoreMessages = !!data.has_more;
            if (currentOtherUserId && data.contact_events) contactEventsCache = data.contact_events || [];
            if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false, loadMorePrepend: true }));
        }).catch(function() {}).finally(function() {
            loadingMoreMessages = false;
            hideMessagesLoadMorePreloader(cont);
        });
    }

    function scrollToMessageRow(msgId) {
        var cont = el(isWidget ? 'messages' : 'messages-container');
        if (!cont) return false;
        var inner = getMessagesInner(cont);
        if (!inner) return false;
        var row = inner.querySelector('.chat-msg-row[data-msg-id="' + msgId + '"]');
        if (!row) return false;
        row.classList.remove('chat-msg-row-reply-target');
        if (cont.scrollHeight > cont.clientHeight) {
            var rowRect = row.getBoundingClientRect();
            var contRect = cont.getBoundingClientRect();
            var scrollTop = cont.scrollTop + (rowRect.top - contRect.top) - (cont.clientHeight / 2) + (row.offsetHeight / 2);
            scrollTop = Math.max(0, Math.min(scrollTop, cont.scrollHeight - cont.clientHeight));
            cont.scrollTo({ top: scrollTop, behavior: 'smooth' });
        } else {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                row.classList.add('chat-msg-row-reply-target');
                setTimeout(function() { row.classList.remove('chat-msg-row-reply-target'); }, 2500);
            });
        });
        return true;
    }

    function scrollToMessageIdOrLoadMore(msgId, retryCount) {
        retryCount = retryCount || 0;
        var targetId = parseInt(msgId, 10);
        if (scrollToMessageRow(msgId)) return;
        var oldestId = messagesCache.length ? Math.min.apply(null, messagesCache.map(function(m) { return m.id; })) : null;
        if (oldestId === null || targetId >= oldestId || !hasMoreMessages || retryCount > 20) return;
        loadMoreMessages().then(function() {
            scrollToMessageIdOrLoadMore(msgId, retryCount + 1);
        });
    }

    function onMessagesScroll(ev) {
        const cont = ev.target;
        const contId = isWidget ? 'chat-widget-messages' : 'chat-messages-container';
        const active = currentGroupId || currentOtherUserId;
        if (cont.id !== contId || !active || loadingMoreMessages || !hasMoreMessages) return;
        if (cont.scrollTop < 80) loadMoreMessages();
    }

    function setReplyTo(msg) {
        editingMessageId = null;
        replyToMessage = msg;
        const previewWrap = el('reply-preview');
        const previewBody = el('reply-preview-body');
        const labelEl = previewWrap ? previewWrap.querySelector('.chat-reply-preview-label') : null;
        if (labelEl) labelEl.textContent = (typeof CHAT_STR !== 'undefined' && CHAT_STR.replyingTo) ? CHAT_STR.replyingTo : 'Replying to';
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

    function setEditMessage(msgId) {
        const msg = (messagesCache || []).find(function(m) { return m.id === msgId && m.type === 'text'; });
        if (!msg) return;
        editingMessageId = msgId;
        replyToMessage = null;
        const previewWrap = el('reply-preview');
        const previewBody = el('reply-preview-body');
        const labelEl = previewWrap ? previewWrap.querySelector('.chat-reply-preview-label') : null;
        if (labelEl) labelEl.textContent = CHAT_STR.editingMessage || 'Editing message';
        if (previewBody) previewBody.textContent = ((msg.body || '').substring(0, 80)) + ((msg.body || '').length > 80 ? '\u2026' : '');
        if (previewWrap) previewWrap.classList.remove('d-none');
        const input = el('message-input');
        if (input) {
            input.value = msg.body || '';
            input.focus();
        }
    }

    function clearEdit() {
        editingMessageId = null;
        const previewWrap = el('reply-preview');
        if (previewWrap) previewWrap.classList.add('d-none');
        const labelEl = previewWrap ? previewWrap.querySelector('.chat-reply-preview-label') : null;
        if (labelEl) labelEl.textContent = (typeof CHAT_STR !== 'undefined' && CHAT_STR.replyingTo) ? CHAT_STR.replyingTo : 'Replying to';
        const input = el('message-input');
        if (input) input.value = '';
    }

    function cancelReplyOrEdit() {
        if (editingMessageId) clearEdit();
        else clearReply();
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
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false }));
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

    function addReaction(messageId, emoji) {
        fetch(routes.react, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ message_id: messageId, emoji: emoji }) })
            .then(r => r.json())
            .then(data => {
                if (data.reactions !== undefined) {
                    const idx = messagesCache.findIndex(m => m.id === messageId);
                    if (idx !== -1) {
                        messagesCache[idx] = Object.assign({}, messagesCache[idx], { reactions: data.reactions });
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false }));
                    }
                }
            })
            .catch(() => {});
    }

    function removeReaction(messageId) {
        fetch(routes.removeReaction, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ message_id: messageId }) })
            .then(r => r.json())
            .then(data => {
                if (data.reactions !== undefined) {
                    const idx = messagesCache.findIndex(m => m.id === messageId);
                    if (idx !== -1) {
                        messagesCache[idx] = Object.assign({}, messagesCache[idx], { reactions: data.reactions });
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions({ scrollToBottom: false }));
                    }
                }
            })
            .catch(() => {});
    }

    let forwardMessageId = null;
    function openForwardModal(messageId) {
        forwardMessageId = messageId;
        const modal = document.getElementById('chat-forward-modal');
        if (!modal) {
            const m = document.createElement('div');
            m.id = 'chat-forward-modal';
            m.className = 'modal fade';
            m.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">' + (CHAT_STR.forward || 'Forward') + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted small">Select one or more chats or groups to forward to:</p><div id="chat-forward-list" class="list-group list-group-flush"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="chat-forward-send-btn">Send</button></div></div></div>';
            document.body.appendChild(m);
            m.addEventListener('hidden.bs.modal', function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });
            document.getElementById('chat-forward-send-btn').addEventListener('click', function(e) {
                e.preventDefault();
                const list = document.getElementById('chat-forward-list');
                const checked = list.querySelectorAll('input.chat-forward-check:checked');
                if (!forwardMessageId || !checked.length) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', text: 'Select at least one chat or group.' });
                    else alert('Select at least one chat or group.');
                    return;
                }
                const modalEl = document.getElementById('chat-forward-modal');
                const modalInst = bootstrap.Modal.getInstance(modalEl);
                if (modalInst) modalInst.hide();
                showPreloader();
                let done = 0;
                const total = checked.length;
                function onOneDone(data) {
                    // Forward API returns the message created in the target conversation - do not add to current chat
                    done++;
                    if (done >= total) {
                        forwardMessageId = null;
                        poll();
                        hidePreloader();
                    }
                }
                checked.forEach(function(cb) {
                    const id = parseInt(cb.dataset.forwardId, 10);
                    const type = cb.dataset.forwardType;
                    const payload = { message_id: forwardMessageId };
                    if (type === 'user') payload.recipient_id = id;
                    else payload.group_id = id;
                    fetch(routes.forward, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                        .then(r => r.json())
                        .then(data => onOneDone(data))
                        .catch(() => { done++; if (done >= total) { forwardMessageId = null; hidePreloader(); } });
                });
            });
        }
        const list = document.getElementById('chat-forward-list');
        list.innerHTML = '';
        (conversationsCache || []).forEach(c => {
            if (c.type === 'group') {
                const img = (c.group?.image_url) ? '<img src="' + escapeHtml(c.group.image_url) + '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">' : '<span class="symbol-label bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;font-size:1rem;">' + (c.group?.name || 'G').charAt(0) + '</span>';
                list.innerHTML += '<label class="list-group-item d-flex align-items-center cursor-pointer mb-0 border"><input type="checkbox" class="form-check-input chat-forward-check me-2" data-forward-id="' + (c.group?.id || '') + '" data-forward-type="group"><span class="symbol symbol-40px me-2 flex-shrink-0">' + img + '</span><span><i class="fa-solid fa-users me-1 text-muted"></i>' + escapeHtml(c.group?.name || 'Group') + '</span></label>';
            } else if (c.user && !(c.user.is_automation_chatbot === 1 || c.user.is_automation_chatbot === true)) {
                const img = (c.user?.image_url) ? '<img src="' + escapeHtml(c.user.image_url) + '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">' : '<span class="symbol-label bg-primary text-white d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;">' + (c.user?.name || '?').charAt(0) + '</span>';
                list.innerHTML += '<label class="list-group-item d-flex align-items-center cursor-pointer mb-0 border"><input type="checkbox" class="form-check-input chat-forward-check me-2" data-forward-id="' + (c.user?.id || '') + '" data-forward-type="user"><span class="symbol symbol-40px me-2 flex-shrink-0">' + img + '</span><span>' + escapeHtml(c.user?.name || '') + '</span></label>';
            }
        });
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = document.getElementById('chat-forward-modal');
            let modalInst = bootstrap.Modal.getInstance(modalEl);
            if (!modalInst) modalInst = new bootstrap.Modal(modalEl);
            modalInst.show();
        }
    }

    function isCurrentUserGroupAdmin() {
        if (!currentGroupData || !currentGroupData.members) return false;
        const me = (currentGroupData.members || []).find(function(m) { return m.user_id === currentUserId; });
        return me && (me.role || '') === 'admin';
    }
    function isCurrentUserGroupCreator() {
        return currentGroupData && currentGroupData.created_by_user_id != null && currentGroupData.created_by_user_id === currentUserId;
    }
    function doLeaveGroup() {
        if (!currentGroupId || !routes.groupLeave) return;
        const doIt = () => {
            showPreloader();
            fetch(routes.groupLeave, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ group_id: currentGroupId }) })
                .then(r => r.json())
                .then(function(data) {
                    if (data.error) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                        else alert(data.error);
                        return;
                    }
                    const gid = currentGroupId;
                    currentGroupId = null;
                    currentGroupData = null;
                    conversationsCache = conversationsCache.filter(function(c) { return c.type !== 'group' || !c.group || c.group.id !== gid; });
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        document.querySelectorAll('.modal.show').forEach(function(m) {
                            const inst = bootstrap.Modal.getInstance(m);
                            if (inst) inst.hide();
                        });
                    }
                    if (isWidget && typeof closeWidgetThread === 'function') closeWidgetThread();
                    else if (typeof closeChatThread === 'function') closeChatThread();
                    poll();
                    renderConversationList(conversationsCache);
                    if (isWidget) updateWidgetBadge(conversationsCache);
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', text: CHAT_STR.leaveGroupSuccess || 'You have left the group.' });
                    else alert(CHAT_STR.leaveGroupSuccess || 'You have left the group.');
                })
                .catch(function() {})
                .finally(function() { hidePreloader(); });
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: CHAT_STR.leaveGroup || 'Leave group', text: CHAT_STR.leaveGroupConfirm || 'Leave this group? You can be added again by a member.', icon: 'question', showCancelButton: true, confirmButtonText: CHAT_STR.leaveGroup || 'Leave group', confirmButtonColor: '#f59e0b' }).then(function(res) { if (res && res.isConfirmed) doIt(); });
        } else if (confirm(CHAT_STR.leaveGroupConfirm || 'Leave this group?')) doIt();
    }
    function doGroupDelete() {
        if (!currentGroupId) return;
        showPreloader();
        fetch(routes.groupDelete, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ group_id: currentGroupId }) })
            .then(r => r.json())
            .then(function(data) {
                if (data.error) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                    else alert(data.error);
                    return;
                }
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const m = document.getElementById('chat-group-info-modal');
                    if (m) { const inst = bootstrap.Modal.getInstance(m); if (inst) inst.hide(); }
                }
                const gid = currentGroupId;
                currentGroupId = null;
                currentGroupData = null;
                conversationsCache = conversationsCache.filter(function(c) { return c.type !== 'group' || !c.group || c.group.id !== gid; });
                if (isWidget && typeof closeWidgetThread === 'function') closeWidgetThread();
                else if (typeof closeChatThread === 'function') closeChatThread();
                poll();
                renderConversationList(conversationsCache);
                if (isWidget) updateWidgetBadge(conversationsCache);
            })
            .catch(function() {})
            .finally(function() { hidePreloader(); });
    }
    function doGroupSetMemberRole(memberUserId, role) {
        if (!currentGroupId) return;
        showPreloader();
        fetch(routes.groupSetMemberRole, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ group_id: currentGroupId, user_id: memberUserId, role: role }) })
            .then(r => r.json())
            .then(function(data) {
                if (data.error) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                    else alert(data.error);
                    return;
                }
                var newRole = (data.role || role) === 'admin' ? 'admin' : 'member';
                if (currentGroupData && currentGroupData.members) {
                    var mem = currentGroupData.members.find(function(m) { return m.user_id === memberUserId; });
                    if (mem) mem.role = newRole;
                }
                var conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                if (conv && conv.group && conv.group.members) {
                    var mem = conv.group.members.find(function(m) { return m.user_id === memberUserId; });
                    if (mem) mem.role = newRole;
                }
                refreshGroupInfoModalContent();
                refetchGroupMessagesIfOpen();
                poll().then(function() {
                    var c = conversationsCache.find(function(x) { return x.type === 'group' && x.group && x.group.id === currentGroupId; });
                    if (c && c.group) currentGroupData = c.group;
                    refreshGroupInfoModalContent();
                    refetchGroupMessagesIfOpen();
                });
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var modalEl = document.getElementById('chat-group-info-modal');
                    if (modalEl) {
                        var inst = bootstrap.Modal.getInstance(modalEl);
                        if (inst) inst.hide();
                    }
                }
                var successText;
                if (newRole === 'admin') {
                    successText = (CHAT_STR.makeAdminSuccess || 'Member is now an admin.');
                } else {
                    successText = (CHAT_STR.removeAdminSuccess || 'Admin role removed from member.');
                }
                if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: 'success', text: successText });
                else alert(successText);
            })
            .catch(function() {})
            .finally(function() { hidePreloader(); });
    }
    function openSetNicknamesModal() {
        if (!currentOtherUserId || currentGroupId) return;
        const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
        if (!conv || !conv.user) return;
        const contactName = (getRecipientDisplayName(conv.user) || 'Contact').trim();
        const contactNick = (conv.user.contact_nickname && conv.user.contact_nickname.trim) ? conv.user.contact_nickname.trim() : (conv.user.contact_nickname ? String(conv.user.contact_nickname).trim() : '');
        const myNick = (conv.user.their_nickname_for_me && conv.user.their_nickname_for_me.trim) ? conv.user.their_nickname_for_me.trim() : (conv.user.their_nickname_for_me ? String(conv.user.their_nickname_for_me).trim() : '');
        const origLabel = CHAT_STR.originalName || 'Original';
        const clearConfirmTitle = CHAT_STR.clearNicknameConfirmTitle || 'Clear nickname?';
        const clearContactConfirmText = (CHAT_STR.clearNicknameConfirmText || 'Clear the nickname for %s?').replace('%s', contactName);
        const clearMyConfirmText = CHAT_STR.clearMyNicknameConfirmText || 'Clear your nickname?';
        const html = '<div class="text-start mb-3"><label class="form-label fw-semibold">' + escapeHtml(contactName) + '</label><div class="d-flex align-items-center gap-1 mb-1"><input type="text" class="form-control flex-grow-1" id="swal-nick-contact" value="' + escapeHtml(contactNick) + '" autocomplete="off"><button type="button" class="btn btn-icon btn-sm btn-light-danger flex-shrink-0 mt-0" id="swal-clear-contact" title="' + escapeHtml(CHAT_STR.clearNickname || 'Clear nickname') + '"><i class="fa-solid fa-xmark"></i></button></div><small class="text-muted d-block">' + escapeHtml(origLabel) + ': ' + escapeHtml(contactName) + '</small></div><div class="text-start"><label class="form-label fw-semibold">' + escapeHtml(CHAT_STR.me || 'Me') + '</label><div class="d-flex align-items-center gap-1 mb-1"><input type="text" class="form-control flex-grow-1" id="swal-nick-me" value="' + escapeHtml(myNick) + '" autocomplete="off"><button type="button" class="btn btn-icon btn-sm btn-light-danger flex-shrink-0 mt-0" id="swal-clear-me" title="' + escapeHtml(CHAT_STR.clearMyNickname || 'Clear my nickname') + '"><i class="fa-solid fa-xmark"></i></button></div><small class="text-muted d-block">' + escapeHtml(origLabel) + ': ' + escapeHtml(currentUserName) + '</small></div>';
        function doSubmit(values) {
            if (!values) return;
            showPreloader();
            const contactVal = (values.contact || '').trim();
            const meVal = (values.me || '').trim();
            const p1 = fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ contact_user_id: currentOtherUserId, nickname: contactVal }) }).then(r => r.json());
            const p2 = fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ contact_user_id: currentUserId, user_id: currentOtherUserId, nickname: meVal }) }).then(r => r.json());
            Promise.all([p1, p2]).then(function(results) {
                const err1 = results[0].error || results[0].message || (results[0].errors && results[0].errors.nickname ? results[0].errors.nickname[0] : null);
                const err2 = results[1].error || results[1].message || (results[1].errors && results[1].errors.nickname ? results[1].errors.nickname[0] : null);
                const errMsg = err1 || err2;
                if (errMsg) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                    else alert(errMsg);
                    return;
                }
                const c = conversationsCache.find(function(x) { return x.user && x.user.id === currentOtherUserId; });
                if (c && c.user) {
                    c.user.contact_nickname = contactVal || null;
                    c.user.their_nickname_for_me = meVal || null;
                }
                update1to1ThreadHeader();
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', text: CHAT_STR.nicknameSaved || 'Nickname saved', timer: 2000, timerProgressBar: true, showConfirmButton: false });
                poll().then(function() {
                    update1to1ThreadHeader();
                    renderConversationList(conversationsCache);
                    if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache);
                });
            }).catch(function() {}).finally(function() { hidePreloader(); });
        }
        function doClearContactFromModal() {
            if (typeof Swal === 'undefined') return;
            Swal.fire({
                title: clearConfirmTitle,
                text: clearContactConfirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: CHAT_STR.yesRemove || 'Yes, remove',
                cancelButtonText: CHAT_STR.close || 'Cancel'
            }).then(function(r) {
                if (!r.isConfirmed) return;
                showPreloader();
                fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ contact_user_id: currentOtherUserId, nickname: '' }) })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        const errMsg = data.error || data.message || (data.errors && data.errors.nickname ? data.errors.nickname[0] : null);
                        if (errMsg) {
                            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                            else alert(errMsg);
                            return;
                        }
                        var c = conversationsCache.find(function(x) { return x.user && x.user.id === currentOtherUserId; });
                        if (c && c.user) c.user.contact_nickname = null;
                        update1to1ThreadHeader();
                        var inp = document.getElementById('swal-nick-contact');
                        if (inp) inp.value = '';
                        Swal.close();
                        poll().then(function() { update1to1ThreadHeader(); renderConversationList(conversationsCache); if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache); });
                    })
                    .catch(function() {})
                    .finally(function() { hidePreloader(); });
            });
        }
        function doClearMyFromModal() {
            if (typeof Swal === 'undefined') return;
            Swal.fire({
                title: clearConfirmTitle,
                text: clearMyConfirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: CHAT_STR.yesRemove || 'Yes, remove',
                cancelButtonText: CHAT_STR.close || 'Cancel'
            }).then(function(r) {
                if (!r.isConfirmed) return;
                showPreloader();
                fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ contact_user_id: currentUserId, user_id: currentOtherUserId, nickname: '' }) })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        const errMsg = data.error || data.message || (data.errors && data.errors.nickname ? data.errors.nickname[0] : null);
                        if (errMsg) {
                            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                            else alert(errMsg);
                            return;
                        }
                        var c = conversationsCache.find(function(x) { return x.user && x.user.id === currentOtherUserId; });
                        if (c && c.user) c.user.their_nickname_for_me = null;
                        var inp = document.getElementById('swal-nick-me');
                        if (inp) inp.value = '';
                        update1to1ThreadHeader();
                        Swal.close();
                        poll().then(function() { update1to1ThreadHeader(); renderConversationList(conversationsCache); if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache); });
                    })
                    .catch(function() {})
                    .finally(function() { hidePreloader(); });
            });
        }
        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            const toggle = menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
            if (toggle && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) { const inst = bootstrap.Dropdown.getInstance(toggle); if (inst) inst.hide(); }
        });
        document.querySelectorAll('.modal.show').forEach(function(modalEl) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) { var inst = bootstrap.Modal.getInstance(modalEl); if (inst) inst.hide(); }
        });
        if (typeof Swal !== 'undefined') {
            setTimeout(function() {
                Swal.fire({
                    title: CHAT_STR.setNicknames || 'Set nicknames',
                    html: html,
                    showCancelButton: true,
                    customClass: { container: 'chat-swal-nickname-modal' },
                    didOpen: function() {
                        var container = document.querySelector('.chat-swal-nickname-modal');
                        if (container) container.style.pointerEvents = 'auto';
                        var popup = document.querySelector('.chat-swal-nickname-modal .swal2-popup');
                        if (popup) popup.style.pointerEvents = 'auto';
                        var inp1 = document.getElementById('swal-nick-contact');
                        var inp2 = document.getElementById('swal-nick-me');
                        if (inp1) { inp1.style.pointerEvents = 'auto'; inp1.focus(); }
                        if (inp2) inp2.style.pointerEvents = 'auto';
                        var btnContact = document.getElementById('swal-clear-contact');
                        var btnMe = document.getElementById('swal-clear-me');
                        if (btnContact) { btnContact.style.pointerEvents = 'auto'; btnContact.addEventListener('click', function(e) { e.preventDefault(); doClearContactFromModal(); }); }
                        if (btnMe) { btnMe.style.pointerEvents = 'auto'; btnMe.addEventListener('click', function(e) { e.preventDefault(); doClearMyFromModal(); }); }
                    },
                    preConfirm: function() {
                        var contact = document.getElementById('swal-nick-contact');
                        var me = document.getElementById('swal-nick-me');
                        return { contact: contact ? contact.value.trim() : '', me: me ? me.value.trim() : '' };
                    }
                }).then(function(res) {
                    doSubmit(res && res.isConfirmed ? res.value : null);
                });
            }, 200);
        } else {
            var contact = prompt(origLabel + ' nickname for ' + contactName + ':', contactNick);
            var me = prompt(origLabel + ' nickname for you:', myNick);
            if (contact !== null && me !== null) doSubmit({ contact: contact || '', me: me || '' });
        }
    }
    function openSetNicknameModal(forGroupMemberUserId) {
        const isOwn = forGroupMemberUserId === 'own';
        const is1to1 = forGroupMemberUserId == null || isOwn;
        let currentName = '';
        let payload = {};
        if (isOwn) {
            if (!currentOtherUserId) return;
            const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
            const raw = conv && conv.user && conv.user.their_nickname_for_me ? conv.user.their_nickname_for_me : '';
            currentName = typeof raw === 'string' ? raw.trim() : String(raw).trim();
            payload = { contact_user_id: currentUserId, user_id: currentOtherUserId };
        } else if (forGroupMemberUserId == null) {
            if (!currentOtherUserId) return;
            const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
            currentName = conv && conv.user ? getRecipientDisplayName(conv.user) : '';
            payload = { contact_user_id: currentOtherUserId };
        } else {
            if (!currentGroupId || !currentGroupData || !currentGroupData.members) return;
            const m = currentGroupData.members.find(function(x) { return x.user_id === forGroupMemberUserId; });
            currentName = m ? ((m.nickname && m.nickname.trim()) ? m.nickname.trim() : (m.name || '')) : '';
            payload = { group_id: currentGroupId, user_id: forGroupMemberUserId };
        }
        const promptMsg = (CHAT_STR.nickname || 'Nickname') + (currentName ? ' (current: ' + currentName + ')' : '');
        function doSubmit(val) {
            if (val === null) return;
            payload.nickname = (val || '').trim();
            showPreloader();
            fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                .then(r => r.json())
                .then(function(data) {
                    const errMsg = data.error || data.message || (data.errors && data.errors.nickname ? data.errors.nickname[0] : null);
                    if (errMsg) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                        else alert(errMsg);
                        return;
                    }
                    poll().then(function() {
                        if (isOwn) {
                            const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                            if (conv && conv.user) conv.user.their_nickname_for_me = (payload.nickname && payload.nickname.trim()) ? payload.nickname.trim() : null;
                            const clearMyNicknameItem = document.getElementById(isWidget ? 'chat-widget-clear-my-nickname-item' : 'chat-clear-my-nickname-item');
                            if (clearMyNicknameItem) clearMyNicknameItem.classList.toggle('d-none', !(payload.nickname && payload.nickname.trim()));
                        } else if (is1to1) {
                            const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                            if (conv && conv.user) conv.user.contact_nickname = payload.nickname || null;
                            const displayName = (payload.nickname && payload.nickname.trim()) ? payload.nickname.trim() : (conv && conv.user ? getRecipientDisplayName(conv.user) : '') || 'User';
                            const threadNameEl = document.getElementById(isWidget ? 'chat-widget-thread-name' : 'chat-thread-name');
                            if (threadNameEl) threadNameEl.textContent = displayName;
                            var avatarInitial = document.getElementById(isWidget ? 'chat-widget-thread-avatar-initial' : 'chat-thread-avatar-initial');
                            if (avatarInitial) avatarInitial.textContent = (displayName || 'U').charAt(0).toUpperCase();
                            const clearNicknameItem = document.getElementById(isWidget ? 'chat-widget-clear-nickname-item' : 'chat-clear-nickname-item');
                            if (clearNicknameItem) clearNicknameItem.classList.toggle('d-none', !(payload.nickname && payload.nickname.trim()));
                        } else {
                            const conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                            if (conv && conv.group) {
                                if (conv.group.members) {
                                    const mem = conv.group.members.find(function(x) { return x.user_id === forGroupMemberUserId; });
                                    if (mem) mem.nickname = payload.nickname || null;
                                }
                                currentGroupData = conv.group;
                            }
                            if (currentGroupData && currentGroupData.members) {
                                const mem = currentGroupData.members.find(function(x) { return x.user_id === forGroupMemberUserId; });
                                if (mem) mem.nickname = payload.nickname || null;
                            }
                            refreshGroupInfoModalContent();
                            refetchGroupMessagesIfOpen();
                        }
                        renderConversationList(conversationsCache);
                        if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache);
                        if (!is1to1) location.reload();
                    });
                })
                .catch(function() {})
                .finally(function() { hidePreloader(); });
        }
        if (typeof Swal !== 'undefined') {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                const toggle = menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    const inst = bootstrap.Dropdown.getInstance(toggle);
                    if (inst) inst.hide();
                }
            });
            /* Hide any open Bootstrap modal so its focus trap doesn't block Swal input */
            document.querySelectorAll('.modal.show').forEach(function(modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }
            });
            setTimeout(function() {
                Swal.fire({
                    title: (isOwn ? (CHAT_STR.setMyNickname || 'Set my nickname') : (CHAT_STR.setNickname || 'Set nickname')),
                    input: 'text',
                    inputValue: currentName,
                    inputPlaceholder: promptMsg,
                    inputAttributes: { autocomplete: 'off' },
                    showCancelButton: true,
                    customClass: { container: 'chat-swal-nickname-modal' },
                    didOpen: function() {
                        var container = document.querySelector('.chat-swal-nickname-modal');
                        if (container) container.style.pointerEvents = 'auto';
                        var popup = document.querySelector('.chat-swal-nickname-modal .swal2-popup');
                        if (popup) {
                            popup.style.pointerEvents = 'auto';
                            popup.querySelector('.swal2-title') && (popup.querySelector('.swal2-title').style.pointerEvents = 'auto');
                            var inp = popup.querySelector('.swal2-input');
                            if (inp) {
                                inp.style.pointerEvents = 'auto';
                                inp.removeAttribute('readonly');
                                inp.removeAttribute('disabled');
                            }
                        }
                        setTimeout(function() {
                            var input = Swal.getInput();
                            if (input) {
                                input.focus();
                                if (input.select) input.select();
                            }
                        }, 50);
                    }
                }).then(function(res) {
                    doSubmit(res && res.isConfirmed ? (res.value || '').trim() : null);
                });
            }, 200);
        } else {
            doSubmit(prompt(promptMsg, currentName));
        }
    }
    function doClearContactNickname() {
        if (!currentOtherUserId || currentGroupId) return;
        showPreloader();
        fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ contact_user_id: currentOtherUserId, nickname: '' }) })
            .then(r => r.json())
            .then(function(data) {
                const errMsg = data.error || data.message || (data.errors && data.errors.nickname ? data.errors.nickname[0] : null);
                if (errMsg) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                    else alert(errMsg);
                    return;
                }
                const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                if (conv && conv.user) conv.user.contact_nickname = null;
                const threadNameEl = document.getElementById(isWidget ? 'chat-widget-thread-name' : 'chat-thread-name');
                if (threadNameEl) threadNameEl.textContent = (conv && conv.user ? getRecipientDisplayName(conv.user) : '') || 'User';
                const avatarInitial = document.getElementById(isWidget ? 'chat-widget-thread-avatar-initial' : 'chat-thread-avatar-initial');
                if (avatarInitial) avatarInitial.textContent = ((conv && conv.user ? getRecipientDisplayName(conv.user) : '') || 'U').charAt(0).toUpperCase();
                const clearNicknameItem = document.getElementById(isWidget ? 'chat-widget-clear-nickname-item' : 'chat-clear-nickname-item');
                if (clearNicknameItem) clearNicknameItem.classList.add('d-none');
                poll().then(function() { renderConversationList(conversationsCache); if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache); });
            })
            .catch(function() {})
            .finally(function() { hidePreloader(); });
    }
    function doClearMyNickname() {
        if (!currentOtherUserId || currentGroupId) return;
        showPreloader();
        fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ contact_user_id: currentUserId, user_id: currentOtherUserId, nickname: '' }) })
            .then(r => r.json())
            .then(function(data) {
                const errMsg = data.error || data.message || (data.errors && data.errors.nickname ? data.errors.nickname[0] : null);
                if (errMsg) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                    else alert(errMsg);
                    return;
                }
                const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                if (conv && conv.user) conv.user.their_nickname_for_me = null;
                const clearMyNicknameItem = document.getElementById(isWidget ? 'chat-widget-clear-my-nickname-item' : 'chat-clear-my-nickname-item');
                if (clearMyNicknameItem) clearMyNicknameItem.classList.add('d-none');
                poll().then(function() { renderConversationList(conversationsCache); if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache); });
            })
            .catch(function() {})
            .finally(function() { hidePreloader(); });
    }
    function doClearGroupNickname(memberUserId) {
        if (!currentGroupId || !currentGroupData) return;
        showPreloader();
        fetch(routes.setNickname, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ group_id: currentGroupId, user_id: memberUserId, nickname: '' }) })
            .then(function(r) {
                return r.text().catch(function() { return r.ok ? '{}' : ''; }).then(function(text) { return { ok: r.ok, status: r.status, text: text }; });
            })
            .then(function(res) {
                var data = {};
                try { data = res.text ? JSON.parse(res.text) : {}; } catch (e) {}
                hidePreloader();
                if (!res.ok) {
                    var errMsg = data.error || data.message || (data.errors && data.errors.nickname ? data.errors.nickname[0] : null) || 'Request failed.';
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: errMsg });
                    else alert(errMsg);
                    return;
                }
                var successMsg = (CHAT_STR.nicknameCleared || 'Nickname cleared.');
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', text: successMsg, timer: 2000, timerProgressBar: true, showConfirmButton: false });
                var conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                if (conv && conv.group && conv.group.members) {
                    var mem = conv.group.members.find(function(x) { return x.user_id === memberUserId; });
                    if (mem) mem.nickname = null;
                }
                if (currentGroupData && currentGroupData.members) {
                    var mem = currentGroupData.members.find(function(x) { return x.user_id === memberUserId; });
                    if (mem) mem.nickname = null;
                }
                refreshGroupInfoModalContent();
                refetchGroupMessagesIfOpen();
                renderConversationList(conversationsCache);
                if (isWidget && typeof updateWidgetBadge === 'function') updateWidgetBadge(conversationsCache);
                poll().then(function() { location.reload(); }).catch(function() { location.reload(); });
            })
            .catch(function() { hidePreloader(); });
    }
    function doGroupRemoveMember(memberUserId) {
        if (!currentGroupId) return;
        var confirmMsg = (CHAT_STR.removeFromGroupConfirm || 'Remove this member from the group?');
        function doRemove() {
            showPreloader();
            fetch(routes.groupRemoveMember, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ group_id: currentGroupId, user_id: memberUserId }) })
                .then(r => r.json())
                .then(function(data) {
                    if (data.error) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                        else alert(data.error);
                        return;
                    }
                    if (memberUserId === currentUserId) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const m = document.getElementById('chat-group-info-modal');
                            if (m) { const inst = bootstrap.Modal.getInstance(m); if (inst) inst.hide(); }
                        }
                        currentGroupId = null;
                        currentGroupData = null;
                        closeThread();
                    } else {
                        if (currentGroupData && currentGroupData.members) {
                            currentGroupData.members = currentGroupData.members.filter(function(m) { return m.user_id !== memberUserId; });
                        }
                        var conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                        if (conv && conv.group && conv.group.members) {
                            conv.group.members = conv.group.members.filter(function(m) { return m.user_id !== memberUserId; });
                        }
                        refreshGroupInfoModalContent();
                        refetchGroupMessagesIfOpen();
                        renderConversationList(conversationsCache);
                    }
                    poll().then(function() {
                        if (currentGroupId) {
                            const conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                            if (conv && conv.group) currentGroupData = conv.group;
                            refreshGroupInfoModalContent();
                            refetchGroupMessagesIfOpen();
                        }
                    });
                })
                .catch(function() {})
                .finally(function() { hidePreloader(); });
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', text: confirmMsg, showCancelButton: true, confirmButtonText: (CHAT_STR.confirm || 'Confirm'), cancelButtonText: (CHAT_STR.cancel || 'Cancel') }).then(function(res) { if (res && res.isConfirmed) doRemove(); });
        } else {
            if (confirm(confirmMsg)) doRemove();
        }
    }

    function refreshGroupInfoModalContent() {
        const imgEl = document.getElementById('chat-group-info-modal-img');
        const membersListEl = document.getElementById('chat-group-info-members-list');
        const membersCountEl = document.getElementById('chat-group-info-members-count');
        const addMembersBtn = document.getElementById('chat-group-info-add-members-btn');
        const changePhotoBtn = document.getElementById('chat-group-info-change-photo-btn');
        if (!currentGroupData) return;
        if (imgEl) {
            const initialEl = document.getElementById('chat-group-info-modal-initial');
            if (currentGroupData.image_url) {
                imgEl.src = currentGroupData.image_url;
                imgEl.style.display = 'block';
                imgEl.classList.remove('d-none');
                if (initialEl) initialEl.classList.add('d-none');
            } else {
                imgEl.src = '';
                imgEl.style.display = 'none';
                imgEl.classList.add('d-none');
                if (initialEl) {
                    initialEl.classList.remove('d-none');
                    initialEl.textContent = (currentGroupData.name || 'G').charAt(0).toUpperCase();
                }
            }
        }
        const isCreator = isCurrentUserGroupCreator();
        const isAdmin = isCurrentUserGroupAdmin();
        const hintEl = document.getElementById('chat-group-info-image-size-hint');
        if (hintEl) hintEl.style.display = (isCreator || isAdmin) ? '' : 'none';
        if (membersCountEl && currentGroupData.members) membersCountEl.textContent = currentGroupData.members.length;
        if (membersListEl && currentGroupData.members) {
            const padLen = currentGroupData.members.length >= 100 ? 3 : 2;
            membersListEl.innerHTML = '<li class="list-group-item text-muted small mb-0">' + (CHAT_STR.members || 'Members') + ' (<span id="chat-group-info-members-count">' + currentGroupData.members.length + '</span>)</li>';
            currentGroupData.members.forEach(function(m, idx) {
                const num = String(idx + 1).padStart(padLen, '0');
                const displayName = (m.nickname && m.nickname.trim()) ? m.nickname.trim() : (m.name || '');
                const isMemberCreator = currentGroupData.created_by_user_id === m.user_id;
                const canChangeRole = (isCreator || isAdmin) && !isMemberCreator && m.user_id !== currentUserId;
                const canRemove = (isCreator || isAdmin) && !isMemberCreator && m.user_id !== currentUserId;
                const canSetNickname = isCreator || isAdmin;
                const canLeaveSelf = m.user_id === currentUserId && !isMemberCreator;
                let actionsHtml = '';
                if (canChangeRole || canRemove || canSetNickname || canLeaveSelf) {
                    actionsHtml = '<div class="dropdown"><button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">';
                    if (canSetNickname) {
                        actionsHtml += '<li><a class="dropdown-item chat-group-member-action" href="#" data-action="set-nickname" data-user-id="' + m.user_id + '">' + (CHAT_STR.setNickname || 'Set nickname') + '</a></li>';
                        if (m.nickname && m.nickname.trim()) actionsHtml += '<li><a class="dropdown-item chat-group-member-action" href="#" data-action="clear-nickname" data-user-id="' + m.user_id + '">' + (CHAT_STR.clearNickname || 'Clear nickname') + '</a></li>';
                    }
                    if (canChangeRole) {
                        if ((m.role || '') === 'admin') actionsHtml += '<li><a class="dropdown-item chat-group-member-action" href="#" data-action="remove-admin" data-user-id="' + m.user_id + '">' + (CHAT_STR.removeAdmin || 'Remove admin') + '</a></li>';
                        else actionsHtml += '<li><a class="dropdown-item chat-group-member-action" href="#" data-action="make-admin" data-user-id="' + m.user_id + '">' + (CHAT_STR.makeAdmin || 'Make admin') + '</a></li>';
                    }
                    if (canRemove) actionsHtml += '<li><a class="dropdown-item chat-group-member-action" href="#" data-action="remove-member" data-user-id="' + m.user_id + '">' + (CHAT_STR.removeFromGroup || 'Remove from group') + '</a></li>';
                    if (canLeaveSelf) actionsHtml += '<li><a class="dropdown-item chat-group-member-action" href="#" data-action="leave-group" data-user-id="' + m.user_id + '">' + (CHAT_STR.leaveGroup || 'Leave group') + '</a></li>';
                    actionsHtml += '</ul></div>';
                }
                const ownerBadge = isMemberCreator ? ' <span class="badge badge-sm badge-primary ms-1">' + (CHAT_STR.owner || 'Owner') + '</span>' : '';
                const isMemberAdmin = (m.role || '') === 'admin';
                const adminBadge = isMemberAdmin ? ' <span class="badge badge-sm badge-info ms-1">' + (CHAT_STR.admin || 'Admin') + '</span>' : '';
                membersListEl.innerHTML += '<li class="list-group-item d-flex align-items-center justify-content-between"><span>' + escapeHtml(num + '. ' + displayName) + ownerBadge + adminBadge + '</span>' + actionsHtml + '</li>';
            });
        }
        if (addMembersBtn) addMembersBtn.style.display = isAdmin ? '' : 'none';
        if (changePhotoBtn) changePhotoBtn.style.display = isAdmin ? '' : 'none';
        const deleteGroupBtn = document.getElementById('chat-group-info-delete-group-btn');
        if (deleteGroupBtn) deleteGroupBtn.style.display = (isCreator || isAdmin) ? '' : 'none';
    }

    function showGroupInfoModal() {
        if (!currentGroupId) return;
        var conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
        if (conv && conv.group) currentGroupData = conv.group;
        if (!currentGroupData || !currentGroupData.members) return;
        const title = (currentGroupData.name || 'Group') + ' – ' + (CHAT_STR.groupInfo || 'Group info');
        let modal = document.getElementById('chat-group-info-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'chat-group-info-modal';
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="chat-group-info-modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="chat-group-info-modal-body">' +
                '<div class="text-center mb-3"><div class="position-relative d-inline-block"><img id="chat-group-info-modal-img" src="" alt="" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;display:none;"><span id="chat-group-info-modal-initial" class="d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white fw-bold" style="width:80px;height:80px;font-size:2rem;">G</span><input type="file" id="chat-group-info-image-input" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp"></div><p id="chat-group-info-image-size-hint" class="text-muted small mt-1 mb-0">300×300 px, Max 2 MB</p></div>' +
                '<ul class="list-group list-group-flush" id="chat-group-info-members-list"><li class="list-group-item text-muted small mb-0">' + (CHAT_STR.members || 'Members') + ' (<span id="chat-group-info-members-count">0</span>)</li></ul></div>' +
                '<div class="modal-footer chat-group-info-modal-footer py-3 px-3"><div class="row g-2 w-100"><div class="col-6"><button type="button" class="btn btn-primary w-100" id="chat-group-info-add-members-btn" style="display:none;"><i class="fa-solid fa-user-plus me-1"></i>' + (CHAT_STR.addMembers || 'Add members') + '</button></div><div class="col-6"><button type="button" class="btn btn-info w-100" id="chat-group-info-change-photo-btn" style="display:none;"><i class="fa-solid fa-camera me-1"></i>' + (CHAT_STR.changeGroupPhoto || 'Change photo') + '</button></div><div class="col-6"><button type="button" class="btn btn-danger w-100" id="chat-group-info-delete-group-btn" style="display:none;"><i class="fa-solid fa-trash me-1"></i>' + (CHAT_STR.deleteGroup || 'Delete group') + '</button></div><div class="col-6"><button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">' + (CHAT_STR.close || 'Close') + '</button></div></div></div></div></div>';
            document.body.appendChild(modal);
            document.getElementById('chat-group-info-change-photo-btn').addEventListener('click', function() {
                document.getElementById('chat-group-info-image-input').click();
            });
            document.getElementById('chat-group-info-image-input').addEventListener('change', function() {
                if (!this.files || !this.files[0] || !currentGroupId) return;
                const fd = new FormData();
                fd.append('group_id', currentGroupId);
                fd.append('image', this.files[0]);
                fd.append('_token', csrf);
                this.value = '';
                showPreloader();
                fetch(routes.groupUpdate, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                            else alert(data.error);
                            return;
                        }
                        if (data.group) {
                            const conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                            if (conv) conv.group = data.group;
                            currentGroupData = data.group;
                            refreshGroupInfoModalContent();
                            const avatar = document.getElementById(isWidget ? 'chat-widget-thread-avatar' : 'chat-thread-avatar');
                            const initial = document.getElementById(isWidget ? 'chat-widget-thread-avatar-initial' : 'chat-thread-avatar-initial');
                            if (avatar && data.group.image_url) { avatar.src = data.group.image_url; avatar.style.display = 'block'; if (initial) initial.style.display = 'none'; }
                            else if (initial) { initial.textContent = (data.group.name || 'G').charAt(0).toUpperCase(); initial.style.display = 'flex'; if (avatar) avatar.style.display = 'none'; }
                        }
                        poll();
                    })
                    .catch(() => {})
                    .finally(() => { hidePreloader(); });
            });
            document.getElementById('chat-group-info-add-members-btn').addEventListener('click', function() {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const inst = bootstrap.Modal.getInstance(modal);
                    if (inst) inst.hide();
                }
                openAddMembersToGroupModal();
            });
            document.getElementById('chat-group-info-delete-group-btn').addEventListener('click', function() {
                if (!currentGroupId || (!isCurrentUserGroupCreator() && !isCurrentUserGroupAdmin())) return;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: CHAT_STR.deleteGroup || 'Delete group', text: CHAT_STR.deleteGroupConfirm || 'Permanently delete this group?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#f1416c', confirmButtonText: CHAT_STR.yesRemove || 'Yes, remove' }).then(function(res) {
                        if (res && res.isConfirmed) doGroupDelete();
                    });
                } else if (confirm(CHAT_STR.deleteGroupConfirm || 'Permanently delete this group?')) doGroupDelete();
            });
            document.getElementById('chat-group-info-members-list').addEventListener('click', function(e) {
                const a = e.target.closest('a.chat-group-member-action');
                if (!a || !currentGroupId) return;
                e.preventDefault();
                const action = a.dataset.action;
                const userId = parseInt(a.dataset.userId, 10);
                if (action === 'set-nickname') openSetNicknameModal(userId);
                else if (action === 'clear-nickname') doClearGroupNickname(userId);
                else if (action === 'make-admin') doGroupSetMemberRole(userId, 'admin');
                else if (action === 'remove-admin') doGroupSetMemberRole(userId, 'member');
                else if (action === 'remove-member') doGroupRemoveMember(userId);
                else if (action === 'leave-group' && userId === currentUserId) doLeaveGroup();
            });
        }
        document.getElementById('chat-group-info-modal-title').textContent = title;
        document.getElementById('chat-group-info-members-count').textContent = (currentGroupData.members || []).length;
        refreshGroupInfoModalContent();
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const inst = new bootstrap.Modal(modal);
            inst.show();
        }
    }

    function openAddMembersToGroupModal() {
        if (!currentGroupId || !currentGroupData) return;
        const existingIds = (currentGroupData.members || []).map(function(m) { return m.user_id; });
        const usersToAdd = (conversationsCache || []).filter(function(c) {
            return c.type === 'user' && c.user && existingIds.indexOf(c.user.id) === -1 && !(c.user.is_automation_chatbot === 1 || c.user.is_automation_chatbot === true);
        });
        let addModal = document.getElementById('chat-group-add-members-modal');
        if (!addModal) {
            addModal = document.createElement('div');
            addModal.id = 'chat-group-add-members-modal';
            addModal.className = 'modal fade';
            addModal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">' + (CHAT_STR.addMembers || 'Add members') + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted small">Select users to add to the group.</p><div id="chat-group-add-members-list" class="border rounded p-2" style="max-height: 250px; overflow-y: auto;"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="chat-group-add-members-submit">Add selected</button></div></div></div>';
            document.body.appendChild(addModal);
            document.getElementById('chat-group-add-members-submit').addEventListener('click', function() {
                const ids = [];
                addModal.querySelectorAll('#chat-group-add-members-list input[type="checkbox"]:checked').forEach(function(cb) {
                    const id = parseInt(cb.dataset.userId, 10);
                    if (id) ids.push(id);
                });
                if (ids.length === 0) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', text: 'Select at least one user.' });
                    else alert('Select at least one user.');
                    return;
                }
                showPreloader();
                fetch(routes.groupAddMembers, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ group_id: currentGroupId, user_ids: ids }) })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                            else alert(data.error);
                            return;
                        }
                        fetchJson(routes.activity).then(function(act) {
                            if (act.chat && act.chat.conversations) {
                                conversationsCache = act.chat.conversations;
                                const conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === currentGroupId; });
                                if (conv && conv.group) currentGroupData = conv.group;
                            }
                            refetchGroupMessagesIfOpen();
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const inst = bootstrap.Modal.getInstance(addModal);
                                if (inst) inst.hide();
                            }
                            showGroupInfoModal();
                        });
                        poll();
                    })
                    .catch(() => {})
                    .finally(() => { hidePreloader(); });
            });
        }
        const listEl = document.getElementById('chat-group-add-members-list');
        listEl.innerHTML = '';
        if (usersToAdd.length === 0) {
            listEl.innerHTML = '<p class="text-muted small mb-0">No other users to add. All your contacts are already in this group.</p>';
        } else {
            usersToAdd.forEach(function(c) {
                const label = document.createElement('label');
                label.className = 'd-flex align-items-center gap-2 py-1 cursor-pointer';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'form-check-input';
                cb.dataset.userId = c.user.id;
                label.appendChild(cb);
                label.appendChild(document.createTextNode(c.user.name || 'User'));
                listEl.appendChild(label);
            });
        }
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const inst = new bootstrap.Modal(addModal);
            inst.show();
        }
    }

    function openCreateGroupModal() {
        let modal = document.getElementById('chat-create-group-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'chat-create-group-modal';
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">' + (CHAT_STR.createGroup || 'Create group') + '</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">' + (CHAT_STR.groupName || 'Group name') + '</label><input type="text" class="form-control" id="chat-create-group-name" placeholder="Group name" maxlength="255"></div><div class="mb-2"><label class="form-label">' + (CHAT_STR.addMembers || 'Add members') + '</label></div><div id="chat-create-group-members" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="chat-create-group-submit">Create</button></div></div></div>';
            document.body.appendChild(modal);
            document.getElementById('chat-create-group-submit').addEventListener('click', function() {
                const name = (document.getElementById('chat-create-group-name').value || '').trim();
                if (!name) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Required', text: 'Enter group name.' });
                    else alert('Enter group name.');
                    return;
                }
                const memberIds = [];
                modal.querySelectorAll('#chat-create-group-members input[type="checkbox"]:checked').forEach(function(cb) {
                    const id = parseInt(cb.dataset.userId, 10);
                    if (id && id !== currentUserId) memberIds.push(id);
                });
                if (memberIds.length === 0) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Required', text: 'Select at least one member.' });
                    else alert('Select at least one member.');
                    return;
                }
                showPreloader();
                fetch(routes.groupCreate, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ name: name, member_ids: memberIds }) })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                            else alert(data.error);
                            return;
                        }
                        if (data.group) {
                            conversationsCache.unshift({ type: 'group', user: null, group: data.group, last_message: null, unread_count: 0 });
                            renderConversationList(conversationsCache);
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const inst = bootstrap.Modal.getInstance(modal);
                                if (inst) inst.hide();
                            }
                            document.getElementById('chat-create-group-name').value = '';
                            modal.querySelectorAll('#chat-create-group-members input[type="checkbox"]').forEach(function(cb) { cb.checked = false; });
                            openThread(null, data.group.id);
                        }
                        poll();
                    })
                    .catch(() => {})
                    .finally(() => { hidePreloader(); });
            });
        }
        document.getElementById('chat-create-group-name').value = '';
        const membersEl = document.getElementById('chat-create-group-members');
        membersEl.innerHTML = '';
        (conversationsCache || []).filter(function(c) { return c.type === 'user' && c.user && !(c.user.is_automation_chatbot === 1 || c.user.is_automation_chatbot === true); }).forEach(function(c) {
            const label = document.createElement('label');
            label.className = 'd-flex align-items-center gap-2 py-1 cursor-pointer';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input';
            cb.dataset.userId = c.user.id;
            label.appendChild(cb);
            label.appendChild(document.createTextNode(c.user.name || 'User'));
            membersEl.appendChild(label);
        });
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInst = new bootstrap.Modal(modal);
            modalInst.show();
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
        const active = currentGroupId || currentOtherUserId;
        if (!input || !active) return;
        const body = (input.value || '').trim();
        if (!body) return;
        if (editingMessageId) {
            showPreloader();
            fetch(routes.edit, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ message_id: editingMessageId, body: body }) })
                .then(r => r.json())
                .then(data => {
                    if (data.message) {
                        const idx = (messagesCache || []).findIndex(function(m) { return m.id === data.message.id; });
                        if (idx !== -1) messagesCache[idx] = data.message;
                        else messagesCache = (messagesCache || []).concat(data.message);
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
                    } else if (data.error) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.error });
                        else alert(data.error);
                    }
                    poll();
                })
                .catch(() => {})
                .finally(function() { hidePreloader(); clearEdit(); });
            return;
        }
        const replyToId = replyToMessage ? replyToMessage.id : null;
        input.value = '';
        clearReply();
        showPreloader();
        if (currentGroupId) {
            const payload = { group_id: currentGroupId, body, reply_to_message_id: replyToId };
            fetch(routes.sendGroup, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                .then(r => r.json())
                .then(data => {
                    if (data.message) {
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        messagesCache = messagesCache.concat(data.message);
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
                    }
                    poll();
                })
                .catch(() => {})
                .finally(() => { hidePreloader(); });
        } else {
            const payload = { recipient_id: currentOtherUserId, body, reply_to_message_id: replyToId };
            fetch(routes.send, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                .then(r => r.json())
                .then(data => {
                    if (data.message) {
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        messagesCache = messagesCache.concat(data.message);
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
                    }
                    poll();
                })
                .catch(() => {})
                .finally(() => { hidePreloader(); });
        }
    }

    function sendFile(inputEl) {
        const active = currentGroupId || currentOtherUserId;
        if (!inputEl?.files?.length || !active) return;
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
        inputEl.value = '';
        showPreloader();
        if (currentGroupId) {
            const fd = new FormData();
            fd.append('group_id', currentGroupId);
            fd.append('file', file);
            fd.append('_token', csrf);
            fetch(routes.sendGroupFile, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to send file.' });
                        else alert(data.error || 'Failed to send file.');
                        return;
                    }
                    if (data.message) {
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        messagesCache = messagesCache.concat(data.message);
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
                    }
                    poll();
                })
                .catch(() => {})
                .finally(() => { hidePreloader(); });
        } else {
            const fd = new FormData();
            fd.append('recipient_id', currentOtherUserId);
            fd.append('file', file);
            fd.append('_token', csrf);
            fetch(routes.sendFile, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to send file.' });
                        else alert(data.error || 'Failed to send file.');
                        return;
                    }
                    if (data.message) {
                        const cont = el(isWidget ? 'messages' : 'messages-container');
                        messagesCache = messagesCache.concat(data.message);
                        if (cont) renderMessages(getMessagesToRender(), cont, getRenderOptions());
                    }
                    poll();
                })
                .catch(() => {})
                .finally(() => { hidePreloader(); });
        }
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

    /** Chat list datetime: "Today 03:54 PM", "Yesterday 03:54 PM", or "3/9/2026 03:54 PM" */
    function formatConversationListTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        const hours = d.getHours();
        const mins = d.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const h12 = hours % 12 || 12;
        const timeStr = String(h12).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ' ' + ampm;
        const now = new Date();
        const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const yesterdayStart = new Date(todayStart);
        yesterdayStart.setDate(yesterdayStart.getDate() - 1);
        const msgDayStart = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        if (msgDayStart.getTime() === todayStart.getTime()) return 'Today ' + timeStr;
        if (msgDayStart.getTime() === yesterdayStart.getTime()) return 'Yesterday ' + timeStr;
        const month = d.getMonth() + 1;
        const day = d.getDate();
        const year = d.getFullYear();
        return month + '/' + day + '/' + year + ' ' + timeStr;
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
    function makeLinksClickable(text) {
        if (!text) return '';
        var escaped = escapeHtml(text);
        return escaped.replace(/(https?:\/\/[^\s<>]+)/g, function(match) {
            var url = match.replace(/[.,;:!?)]+$/, '');
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" class="chat-msg-link text-decoration-underline">' + url + '</a>';
        });
    }

    // Init
    if (isWidget) {
        function closeWidgetThread() {
            document.getElementById('chat-widget-thread-panel').classList.add('d-none');
            document.getElementById('chat-widget-conversation-list').classList.remove('d-none');
            document.getElementById('chat-widget-search-wrap')?.classList.remove('d-none');
            currentOtherUserId = null;
            currentGroupId = null;
            currentGroupData = null;
            document.getElementById('chat-widget-group-info-item')?.classList.add('d-none');
            document.getElementById('chat-widget-set-nicknames-item')?.classList.add('d-none');
            document.getElementById('chat-widget-leave-group-item')?.classList.add('d-none');
            clearReply();
            clearEdit();
            if (typeof startPollTimer === 'function') startPollTimer();
        }
        var widgetPanelHideTimer = null;
        var widgetPanelTransitionEndHandler = null;

        function showWidgetPanel() {
            var panel = document.getElementById('chat-widget-panel');
            if (!panel) return;

            if (widgetPanelHideTimer) {
                clearTimeout(widgetPanelHideTimer);
                widgetPanelHideTimer = null;
            }
            if (widgetPanelTransitionEndHandler) {
                panel.removeEventListener('transitionend', widgetPanelTransitionEndHandler);
                widgetPanelTransitionEndHandler = null;
            }

            // Ensure a predictable start state for the opening transition
            panel.classList.remove('d-none');
            panel.classList.remove('chat-widget-panel-open');
            panel.classList.add('chat-widget-panel-closed');

            // Force layout so the browser picks up the initial state
            void panel.offsetHeight;

            requestAnimationFrame(function() {
                panel.classList.add('chat-widget-panel-open');
                panel.classList.remove('chat-widget-panel-closed');
            });

            poll();
        }

        function hideWidgetPanel() {
            var panel = document.getElementById('chat-widget-panel');
            if (!panel) return;
            if (panel.classList.contains('d-none')) return;

            if (widgetPanelHideTimer) {
                clearTimeout(widgetPanelHideTimer);
                widgetPanelHideTimer = null;
            }
            if (widgetPanelTransitionEndHandler) {
                panel.removeEventListener('transitionend', widgetPanelTransitionEndHandler);
                widgetPanelTransitionEndHandler = null;
            }

            panel.classList.remove('chat-widget-panel-open');
            panel.classList.add('chat-widget-panel-closed');

            widgetPanelTransitionEndHandler = function(e) {
                if (e && e.target !== panel) return;
                panel.classList.add('d-none');
                panel.removeEventListener('transitionend', widgetPanelTransitionEndHandler);
                widgetPanelTransitionEndHandler = null;
            };
            panel.addEventListener('transitionend', widgetPanelTransitionEndHandler);

            // Fallback in case transitionend doesn't fire
            widgetPanelHideTimer = setTimeout(function() {
                if (!panel.classList.contains('d-none') && panel.classList.contains('chat-widget-panel-closed')) {
                    panel.classList.add('d-none');
                }
                if (widgetPanelTransitionEndHandler) {
                    panel.removeEventListener('transitionend', widgetPanelTransitionEndHandler);
                    widgetPanelTransitionEndHandler = null;
                }
                widgetPanelHideTimer = null;
            }, 320);
        }
        function closeWidgetPanel() {
            closeWidgetThread();
            hideWidgetPanel();
        }
        var chatWidgetToggle = document.getElementById('chat-widget-toggle');
        if (chatWidgetToggle) {
            chatWidgetToggle.addEventListener('click', () => {
                var panel = document.getElementById('chat-widget-panel');
                if (panel && panel.classList.contains('d-none')) {
                    showWidgetPanel();
                } else if (panel) {
                    closeWidgetPanel();
                }
            });
        }
        var chatWidgetMinimize = document.getElementById('chat-widget-minimize');
        if (chatWidgetMinimize) chatWidgetMinimize.addEventListener('click', closeWidgetPanel);
        document.getElementById('chat-widget-back')?.addEventListener('click', () => {
            closeWidgetThread();
        });
        document.getElementById('chat-widget-close-thread')?.addEventListener('click', closeWidgetThread);
        document.getElementById('chat-widget-group-info')?.addEventListener('click', function(e) { e.preventDefault(); showGroupInfoModal(); });
        document.getElementById('chat-widget-set-nicknames')?.addEventListener('click', function(e) { e.preventDefault(); openSetNicknamesModal(); });
        document.getElementById('chat-widget-delete-conversation')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentOtherUserId) deleteConversation();
        });
        document.getElementById('chat-widget-leave-group')?.addEventListener('click', function(e) {
            e.preventDefault();
            doLeaveGroup();
        });
        document.getElementById('chat-widget-send').addEventListener('click', sendMessage);
        document.getElementById('chat-widget-attach').addEventListener('click', () => document.getElementById('chat-widget-file-input').click());
        document.getElementById('chat-widget-file-input').addEventListener('change', function() { sendFile(this); });
        document.getElementById('chat-widget-message-input').addEventListener('keydown', onMessageInputKeydown);
        document.getElementById('chat-widget-reply-cancel')?.addEventListener('click', cancelReplyOrEdit);
        document.getElementById('chat-widget-messages')?.addEventListener('scroll', onMessagesScroll);
        document.getElementById('chat-widget-search-user')?.addEventListener('input', function() { renderConversationList(conversationsCache); });
        initEmojiPicker('chat-widget-emoji-picker', 'chat-widget-emoji-btn', 'chat-widget-message-input');
        document.getElementById('chat-widget-view-all')?.addEventListener('click', function(e) {
            if (currentOtherUserId || currentGroupId) {
                e.preventDefault();
                let openParam = '';
                if (currentGroupId) {
                    openParam = 'g' + currentGroupId;
                } else {
                    const conv = conversationsCache.find(function(c) { return c.user && c.user.id === currentOtherUserId; });
                    openParam = (conv?.user?.uid != null && conv.user.uid !== '') ? conv.user.uid : currentOtherUserId;
                }
                const base = this.getAttribute('href');
                window.location.href = base + (base.indexOf('?') !== -1 ? '&' : '?') + 'open=' + encodeURIComponent(openParam);
            }
        });
    } else {
        function closeChatThread() {
            document.getElementById('chat-thread-placeholder').classList.remove('d-none');
            document.getElementById('chat-thread-panel').classList.add('d-none');
            currentOtherUserId = null;
            currentGroupId = null;
            currentGroupData = null;
            document.getElementById('chat-group-info-item')?.classList.add('d-none');
            document.getElementById('chat-set-nicknames-item')?.classList.add('d-none');
            document.getElementById('chat-leave-group-item')?.classList.add('d-none');
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
        document.getElementById('chat-group-info')?.addEventListener('click', function(e) { e.preventDefault(); showGroupInfoModal(); });
        document.getElementById('chat-set-nicknames')?.addEventListener('click', function(e) { e.preventDefault(); openSetNicknamesModal(); });
        document.getElementById('chat-delete-conversation')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentOtherUserId) deleteConversation();
        });
        document.getElementById('chat-leave-group')?.addEventListener('click', function(e) { e.preventDefault(); doLeaveGroup(); });
        document.getElementById('chat-reply-cancel')?.addEventListener('click', cancelReplyOrEdit);
        document.getElementById('chat-messages-container')?.addEventListener('scroll', onMessagesScroll);
        document.getElementById('chat-send-btn').addEventListener('click', sendMessage);
        document.getElementById('chat-attach-btn')?.addEventListener('click', () => document.getElementById('chat-file-input').click());
        document.getElementById('chat-file-input')?.addEventListener('change', function() { sendFile(this); });
        document.getElementById('chat-message-input')?.addEventListener('keydown', onMessageInputKeydown);
        document.getElementById('chat-search-user')?.addEventListener('input', () => renderConversationList(conversationsCache));
        initEmojiPicker('chat-emoji-picker', 'chat-emoji-btn', 'chat-message-input');
        document.getElementById('chat-create-group-btn')?.addEventListener('click', openCreateGroupModal);
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

    (function setupMessageActionsDelegation() {
        var ids = ['chat-messages-container', 'chat-widget-messages'];
        ids.forEach(function(id) {
            var cont = document.getElementById(id);
            if (!cont) return;
            cont.addEventListener('click', function(e) {
                var actionEl = e.target.closest('.chat-action');
                if (!actionEl) return;
                e.preventDefault();
                var action = actionEl.dataset.action;
                var msgId = parseInt(actionEl.dataset.id, 10);
                if (action === 'reply') setReplyTo({ id: msgId, body: actionEl.dataset.replyPreview || '', sender_name: actionEl.dataset.replySender || '' });
                else if (action === 'edit') setEditMessage(msgId);
                else if (action === 'history') showHistory(msgId);
                else if (action === 'deleteForMe') deleteForMe(msgId);
                else if (action === 'deleteForAll') deleteForAll(msgId);
                else if (action === 'forward') openForwardModal(msgId);
            });
        });
    })();

    function initEmojiPicker(pickerId, btnId, inputId) {
        const picker = document.getElementById(pickerId);
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        if (!picker || !btn || !input) return;

        // The widget panel uses overflow-hidden, which can clip absolutely-positioned popovers.
        // So we "portal" the picker to <body> while open (position: fixed), then restore it.
        let portalPlaceholder = null;
        let portalOriginalParent = null;
        let portalOriginalNextSibling = null;
        let portalOriginalClassName = null;

        function insertAtCursor(str) {
            const start = input.selectionStart ?? (input.value || '').length;
            const end = input.selectionEnd ?? start;
            const text = input.value || '';
            input.value = text.substring(0, start) + str + text.substring(end);
            input.selectionStart = input.selectionEnd = start + str.length;
            input.focus();
        }

        // Build once (full emoji dataset picker)
        if (!picker.querySelector('emoji-picker')) {
            picker.classList.add('chat-emoji-picker-popover');
            picker.innerHTML = '';
            const pickerEl = document.createElement('emoji-picker');

            // Default dataSource already points to emoji-picker-element-data CDN.
            // Explicitly set in case a CSP/proxy rewrites defaults.
            pickerEl.setAttribute('data-source', 'https://cdn.jsdelivr.net/npm/emoji-picker-element-data@^1/en/emojibase/data.json');

            // Size is controlled via CSS, but keep a safe inline fallback.
            pickerEl.style.width = '320px';
            pickerEl.style.height = '360px';

            pickerEl.addEventListener('emoji-click', function(event) {
                const unicode = event?.detail?.unicode || '';
                if (!unicode) return;
                insertAtCursor(unicode);
                picker.classList.add('d-none');
            });

            picker.appendChild(pickerEl);

            // Custom styles inside Shadow DOM (emoji-picker-element)
            function applyEmojiPickerShadowStyles(el) {
                if (!el || !el.shadowRoot) return;
                var style = el.shadowRoot.querySelector('style[data-chat-emoji-picker-styles]');
                if (style) return;
                style = document.createElement('style');
                style.setAttribute('data-chat-emoji-picker-styles', '1');
                style.textContent = '.skintone-button-wrapper { display: none !important; } .search-row { padding-right: var(--input-padding, 0.25rem); }';
                el.shadowRoot.appendChild(style);
            }
            if (typeof customElements !== 'undefined' && customElements.whenDefined) {
                customElements.whenDefined('emoji-picker').then(function() { applyEmojiPickerShadowStyles(pickerEl); });
            } else {
                setTimeout(function() { applyEmojiPickerShadowStyles(pickerEl); }, 100);
            }
        }

        function portalAttach() {
            if (picker.dataset.portalAttached === '1') return;
            portalOriginalParent = picker.parentNode;
            portalOriginalNextSibling = picker.nextSibling;
            portalOriginalClassName = picker.className;
            portalPlaceholder = document.createComment('emoji-picker-placeholder');
            if (portalOriginalParent) portalOriginalParent.insertBefore(portalPlaceholder, picker);
            document.body.appendChild(picker);
            picker.dataset.portalAttached = '1';

            // Remove bootstrap positioning utilities (they use !important and break fixed positioning in <body>)
            picker.classList.remove('position-absolute', 'bottom-100', 'top-100', 'start-0', 'end-0', 'mb-1', 'ms-1', 'me-1');
        }

        function portalDetach() {
            if (picker.dataset.portalAttached !== '1') return;
            try {
                if (portalPlaceholder && portalPlaceholder.parentNode) {
                    portalPlaceholder.parentNode.insertBefore(picker, portalPlaceholder);
                    portalPlaceholder.parentNode.removeChild(portalPlaceholder);
                } else if (portalOriginalParent) {
                    portalOriginalParent.insertBefore(picker, portalOriginalNextSibling);
                }
            } catch (e) {
                // If anything goes wrong, just leave it in body (still functional).
            }
            picker.dataset.portalAttached = '0';
            portalPlaceholder = null;
            portalOriginalParent = null;
            portalOriginalNextSibling = null;
            if (portalOriginalClassName != null) {
                const shouldBeHidden = picker.classList.contains('d-none');
                picker.className = portalOriginalClassName;
                if (shouldBeHidden) picker.classList.add('d-none');
            }
            portalOriginalClassName = null;
        }

        function positionFixedPopover() {
            if (picker.classList.contains('d-none')) return;

            // Ensure we're not clipped by parent overflow rules
            portalAttach();

            // Reset and measure
            picker.style.transform = '';
            picker.style.position = 'fixed';
            picker.style.top = '0px';
            picker.style.left = '0px';
            picker.style.right = 'auto';
            picker.style.bottom = 'auto';
            picker.style.margin = '0';
            picker.style.zIndex = '2000';

            const pad = 8;
            const btnRect = btn.getBoundingClientRect();
            let pickerRect = picker.getBoundingClientRect();
            if ((pickerRect.width || 0) < 50 || (pickerRect.height || 0) < 50) {
                // If the custom element hasn't painted yet, use a sane fallback size.
                const fallbackW = 320;
                const fallbackH = 360;
                picker.style.width = `${fallbackW}px`;
                picker.style.height = `${fallbackH}px`;
                pickerRect = picker.getBoundingClientRect();
            }

            // Prefer opening above, aligned to button's right edge
            let left = btnRect.right - pickerRect.width;
            let top = btnRect.top - pickerRect.height - pad;

            // If not enough space above, open below
            if (top < pad) top = btnRect.bottom + pad;

            // Clamp within viewport
            left = Math.max(pad, Math.min(left, window.innerWidth - pickerRect.width - pad));
            top = Math.max(pad, Math.min(top, window.innerHeight - pickerRect.height - pad));

            picker.style.left = `${left}px`;
            picker.style.top = `${top}px`;
        }

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            picker.classList.toggle('d-none');
            if (!picker.classList.contains('d-none')) {
                requestAnimationFrame(positionFixedPopover);
            } else {
                picker.style.transform = '';
                picker.style.position = '';
                picker.style.top = '';
                picker.style.left = '';
                picker.style.right = '';
                picker.style.bottom = '';
                picker.style.margin = '';
                picker.style.zIndex = '';
                picker.style.width = '';
                picker.style.height = '';
                portalDetach();
            }
        });
        document.addEventListener('click', function(e) {
            if (!picker.contains(e.target) && !btn.contains(e.target)) {
                picker.classList.add('d-none');
                picker.style.transform = '';
                picker.style.position = '';
                picker.style.top = '';
                picker.style.left = '';
                picker.style.right = '';
                picker.style.bottom = '';
                picker.style.margin = '';
                picker.style.zIndex = '';
                picker.style.width = '';
                picker.style.height = '';
                portalDetach();
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                picker.classList.add('d-none');
                picker.style.transform = '';
                picker.style.position = '';
                picker.style.top = '';
                picker.style.left = '';
                picker.style.right = '';
                picker.style.bottom = '';
                picker.style.margin = '';
                picker.style.zIndex = '';
                picker.style.width = '';
                picker.style.height = '';
                portalDetach();
            }
        });
        window.addEventListener('resize', function() {
            requestAnimationFrame(positionFixedPopover);
        });
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
                    if (String(openParam).charAt(0) === 'g') {
                        const gid = parseInt(openParam.substring(1), 10);
                        const conv = conversationsCache.find(function(c) { return c.type === 'group' && c.group && c.group.id === gid; });
                        if (conv && conv.group) openThread(null, gid);
                    } else {
                        const conv = conversationsCache.find(function(c) {
                            if (!c.user) return false;
                            if (c.user.uid != null && c.user.uid !== '' && String(c.user.uid) === String(openParam)) return true;
                            return String(c.user.id) === String(openParam);
                        });
                        if (conv && conv.user) openThread(conv.user.id, null);
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

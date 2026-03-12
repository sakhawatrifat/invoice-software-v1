<?php

namespace App\Http\Controllers;

use App\Models\ChatContactEvent;
use App\Models\ChatContactNickname;
use App\Models\ChatGroup;
use App\Models\ChatGroupEvent;
use App\Models\ChatGroupMember;
use App\Models\ChatMessage;
use App\Models\ChatMessageHidden;
use App\Models\ChatMessageReaction;
use App\Models\ChatMessageRead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ChatController extends Controller
{
    /**
     * Serialize datetime to UTC ISO 8601 so the client can display in user's local timezone.
     */
    private function toUtcIso8601($date): ?string
    {
        if ($date === null) {
            return null;
        }
        $carbon = $date instanceof \Carbon\Carbon ? $date->copy() : Carbon::parse($date);

        return $carbon->setTimezone('UTC')->toIso8601String();
    }

    public function index()
    {
        return view('common.chat.index');
    }

    /**
     * Combined activity: last_seen, chat conversations, and (for admin) attendance status.
     * Single URL for background refresh - use this for both chat poll and attendance status.
     * Response is cached per user for a short TTL to reduce DB load from frequent polling.
     */
    public function activity(Request $request)
    {
        $userId = Auth::id();
        $user = Auth::user();

        // Always update last_seen so the user appears online on every request
        User::where('id', $userId)->update(['last_seen_at' => Carbon::now()]);

        $cacheTtl = (int) config('chat.activity_cache_seconds', 8);
        $builder = function () use ($user) {
            $data = [
                'chat' => [
                    'conversations' => $this->getConversationsData(),
                ],
            ];
            if (in_array($user->user_type ?? '', ['admin', 'superadmin'])) {
                $attendanceResponse = app(AttendanceController::class)->getCurrentStatus();
                $data['attendance'] = json_decode($attendanceResponse->getContent(), true);
            }
            return $data;
        };

        if ($cacheTtl > 0) {
            $payload = Cache::remember('chat_activity_' . $userId, $cacheTtl, $builder);
        } else {
            $payload = $builder();
        }

        return response()->json($payload);
    }

    /**
     * List conversations for current user (other users with last message + unread count).
     */
    public function conversations(Request $request)
    {
        return response()->json(['conversations' => $this->getConversationsData()]);
    }

    /**
     * @return array Conversations array (for activity + conversations responses).
     */
    private function getConversationsData(): array
    {
        $userId = Auth::id();
        $users = User::where('id', '!=', $userId)
            ->whereNull('deleted_at')
            ->select('id', 'uid', 'name', 'image', 'last_seen_at', 'status', 'is_automation_chatbot', 'user_type')
            ->orderByRaw('COALESCE(is_automation_chatbot, 0) DESC, name ASC')
            ->get();
        $contactNicknames = ChatContactNickname::where('user_id', $userId)->whereIn('contact_user_id', $users->pluck('id'))->get()->keyBy('contact_user_id');
        $theirNicknamesForMe = ChatContactNickname::where('contact_user_id', $userId)->whereIn('user_id', $users->pluck('id'))->get()->keyBy('user_id');

        $conversations = [];
        foreach ($users as $user) {
            $lastMessage = ChatMessage::whereNull('chat_messages.deleted_at')
                ->where(function ($q) use ($userId, $user) {
                    $q->where(function ($q2) use ($userId, $user) {
                        $q2->where('sender_id', $userId)->where('recipient_id', $user->id);
                    })->orWhere(function ($q2) use ($userId, $user) {
                        $q2->where('sender_id', $user->id)->where('recipient_id', $userId);
                    });
                })
                ->whereNotIn('id', function ($q) use ($userId) {
                    $q->select('message_id')->from('chat_message_hidden')->where('user_id', $userId);
                })
                ->orderByDesc('created_at')
                ->first();

            $unread = 0;
            if ($lastMessage) {
                $unread = ChatMessage::where('recipient_id', $userId)
                    ->where('sender_id', $user->id)
                    ->whereNull('chat_messages.deleted_at')
                    ->whereNotIn('id', function ($q) use ($userId) {
                        $q->select('message_id')->from('chat_message_hidden')->where('user_id', $userId);
                    })
                    ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $userId))
                    ->count();
            }

            $contactNick = $contactNicknames->get($user->id)?->nickname;
            $theirNickForMe = $theirNicknamesForMe->get($user->id)?->nickname;
            $conversations[] = [
                'type' => 'user',
                'user' => [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'image_url' => $user->image_url,
                    'last_seen_at' => $this->toUtcIso8601($user->last_seen_at),
                    'status' => $user->status,
                    'is_automation_chatbot' => (bool) ($user->is_automation_chatbot ?? false),
                    'user_type' => $user->user_type ?? 'user',
                    'contact_nickname' => $contactNick ?: null,
                    'their_nickname_for_me' => $theirNickForMe ?: null,
                ],
                'group' => null,
                'last_message' => $lastMessage ? $this->formatMessage($lastMessage, $userId, null, null, $contactNick) : null,
                'unread_count' => $unread,
            ];
        }

        // Add groups the user is a member of (with members and roles for admin display)
        $groupIds = ChatGroupMember::where('user_id', $userId)->pluck('group_id');
        foreach (ChatGroup::whereIn('id', $groupIds)->with(['creator:id,name,image', 'memberPivots.user:id,name'])->get() as $group) {
            $lastMessage = ChatMessage::with(['sender:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name'])
                ->whereNull('chat_messages.deleted_at')
                ->where('group_id', $group->id)
                ->whereNotIn('id', function ($q) use ($userId) {
                    $q->select('message_id')->from('chat_message_hidden')->where('user_id', $userId);
                })
                ->orderByDesc('created_at')
                ->first();
            $unread = 0;
            if ($lastMessage && $lastMessage->sender_id !== $userId) {
                $unread = ChatMessage::where('group_id', $group->id)
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('chat_messages.deleted_at')
                    ->whereNotIn('id', function ($q) use ($userId) {
                        $q->select('message_id')->from('chat_message_hidden')->where('user_id', $userId);
                    })
                    ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $userId))
                    ->count();
            }
            $members = $group->memberPivots->map(function ($pivot) {
                return [
                    'user_id' => $pivot->user_id,
                    'name' => $pivot->user ? $pivot->user->name : null,
                    'role' => $pivot->role ?? 'member',
                    'nickname' => $pivot->nickname ?: null,
                ];
            })->values()->all();
            $adminCount = collect($members)->where('role', 'admin')->count();
            $conversations[] = [
                'type' => 'group',
                'user' => null,
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'image_url' => $group->image ? getUploadedUrl($group->image) : null,
                    'created_by_user_id' => $group->created_by_user_id,
                    'creator_name' => $group->creator ? $group->creator->name : null,
                    'members' => $members,
                    'member_count' => count($members),
                    'admin_count' => $adminCount,
                ],
                'last_message' => $lastMessage ? $this->formatMessage($lastMessage, $userId, null, $group->memberPivots->keyBy('user_id')->map(fn($p) => $p->nickname)->all()) : null,
                'unread_count' => $unread,
            ];
        }

        usort($conversations, function ($a, $b) {
            $userA = $a['user'] ?? null;
            $userB = $b['user'] ?? null;
            $botA = $userA && (bool) ($userA['is_automation_chatbot'] ?? false);
            $botB = $userB && (bool) ($userB['is_automation_chatbot'] ?? false);
            if ($botA && !$botB) return -1;
            if (!$botA && $botB) return 1;
            $t1 = $a['last_message']['created_at'] ?? null;
            $t2 = $b['last_message']['created_at'] ?? null;
            if (!$t1) return 1;
            if (!$t2) return -1;
            return strcmp($t2, $t1);
        });

        return $conversations;
    }

    /**
     * Get messages between current user and another user.
     * Query params: before_id (optional, cursor for older messages), limit (default 50).
     */
    public function messages(Request $request, int $otherUserId)
    {
        $userId = Auth::id();
        $hiddenIds = ChatMessageHidden::where('user_id', $userId)->pluck('message_id');
        $limit = min(max((int) $request->get('limit', 50), 1), 100);
        $beforeId = $request->get('before_id') ? (int) $request->get('before_id') : null;

        $query = ChatMessage::with(['sender:id,name,image', 'recipient:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name', 'forwardedFrom.sender:id,name'])
            ->whereNull('chat_messages.deleted_at')
            ->whereNotIn('id', $hiddenIds)
            ->where(function ($q) use ($userId, $otherUserId) {
                $q->where(function ($q2) use ($userId, $otherUserId) {
                    $q2->where('sender_id', $userId)->where('recipient_id', $otherUserId);
                })->orWhere(function ($q2) use ($userId, $otherUserId) {
                    $q2->where('sender_id', $otherUserId)->where('recipient_id', $userId);
                });
            });

        if ($beforeId) {
            $query->where('id', '<', $beforeId)->orderByDesc('id')->limit($limit);
            $messages = $query->get()->reverse()->values();
        } else {
            $messages = $query->orderByDesc('id')->limit($limit)->get()->reverse()->values();
        }

        $hasMore = $messages->count() === $limit;
        $contactNick = ChatContactNickname::where('user_id', $userId)->where('contact_user_id', $otherUserId)->value('nickname');
        $list = $messages->map(fn($m) => $this->formatMessage($m, $userId, null, null, $contactNick))->all();
        $contactEvents = ChatContactEvent::where(function ($q) use ($userId, $otherUserId) {
            $q->where('user_id', $userId)->where('contact_user_id', $otherUserId);
        })->orWhere(function ($q) use ($userId, $otherUserId) {
            $q->where('user_id', $otherUserId)->where('contact_user_id', $userId);
        })
            ->with(['user:id,name', 'contactUser:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn($e) => $this->formatContactEvent($e, $userId))->all();
        return response()->json(['messages' => $list, 'has_more' => $hasMore, 'contact_events' => $contactEvents]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'body' => 'required|string|max:10000',
            'recipient_id' => 'required|exists:users,id',
            'reply_to_message_id' => 'nullable|exists:chat_messages,id',
        ]);
        $userId = Auth::id();
        if ((int) $request->recipient_id === $userId) {
            return response()->json(['error' => 'Invalid recipient'], 422);
        }

        $replyToId = $request->reply_to_message_id ? (int) $request->reply_to_message_id : null;
        if ($replyToId) {
            $replyMsg = ChatMessage::find($replyToId);
            if (!$replyMsg || $replyMsg->deleted_at) {
                return response()->json(['error' => 'Invalid reply message'], 422);
            }
            $inConversation = ($replyMsg->sender_id === $userId && $replyMsg->recipient_id === (int) $request->recipient_id)
                || ($replyMsg->sender_id === (int) $request->recipient_id && $replyMsg->recipient_id === $userId);
            if (!$inConversation) {
                return response()->json(['error' => 'Invalid reply message'], 422);
            }
        }

        $msg = ChatMessage::create([
            'sender_id' => $userId,
            'recipient_id' => $request->recipient_id,
            'body' => $request->body,
            'type' => 'text',
            'reply_to_message_id' => $replyToId,
        ]);
        $msg->load(['sender:id,name,image', 'recipient:id,name,image', 'reads', 'replyTo.sender:id,name']);
        $this->clearActivityCache($userId, (int) $request->recipient_id);
        return response()->json(['message' => $this->formatMessage($msg, $userId)]);
    }

    public function sendFile(Request $request)
    {
        $maxKb = config('chat.max_file_size_kb', 0);
        $rules = [
            'recipient_id' => 'required|exists:users,id',
            'file' => ['required', 'file'],
        ];
        if ($maxKb > 0) {
            $rules['file'][] = 'max:' . $maxKb;
        }
        $request->validate($rules, $maxKb > 0 ? [
            'file.max' => 'File size must not exceed ' . round($maxKb / 1024, 1) . ' MB.',
        ] : []);

        $userId = Auth::id();
        if ((int) $request->recipient_id === $userId) {
            return response()->json(['error' => 'Invalid recipient'], 422);
        }

        $file = $request->file('file');
        $fileSize = $file->getSize();
        if ($maxKb > 0) {
            $sizeKb = (int) ceil($fileSize / 1024);
            if ($sizeKb > $maxKb) {
                return response()->json(['error' => 'File too large.'], 422);
            }
        }

        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic'];
        $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'm4v', 'wmv'];

        if (in_array($ext, $imageExts)) {
            $path = handleImageUpload($file, 1200, 1200, 'chat-files', 'chat', null);
            if (!$path) {
                return response()->json(['error' => 'Failed to process image.'], 422);
            }
        } elseif (in_array($ext, $videoExts)) {
            $path = convertVideo($file, 'chat-files', 'chat', ['max_width' => 1280, 'max_height' => 720]);
            if (!$path) {
                return response()->json(['error' => 'Failed to process video.'], 422);
            }
            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: $ext;
        } else {
            $path = $file->store('chat-files', 'local');
        }

        $appName = trim(preg_replace('/[\\\\\/:*?"<>|]/', '', config('app.name', env('APP_NAME', 'App'))));
        if (in_array($ext, $imageExts)) {
            $fileTypeLabel = 'Image';
        } elseif (in_array($ext, $videoExts)) {
            $fileTypeLabel = 'Video';
        } elseif ($ext === 'pdf') {
            $fileTypeLabel = 'PDF';
        } else {
            $fileTypeLabel = 'File';
        }
        $tz = config('app.timezone', env('APP_TIMEZONE', 'UTC'));
        $tzSafe = trim(str_replace(['/', '\\'], '-', $tz));
        $uniqueSuffix = bin2hex(random_bytes(4));
        $displayFileName = $appName . ' ' . $fileTypeLabel . '-' . Carbon::now()->format('Y-m-d \a\t g.i.s A') . ' (' . $tzSafe . ')-' . $uniqueSuffix . '.' . $ext;

        $msg = ChatMessage::create([
            'sender_id' => $userId,
            'recipient_id' => $request->recipient_id,
            'body' => null,
            'type' => 'file',
            'file_path' => $path,
            'file_name' => $displayFileName,
            'file_size' => $fileSize,
        ]);
        $msg->load(['sender:id,name,image', 'recipient:id,name,image', 'reads']);
        $this->clearActivityCache($userId, (int) $request->recipient_id);
        return response()->json(['message' => $this->formatMessage($msg, $userId)]);
    }

    public function markRead(Request $request)
    {
        $request->validate(['message_ids' => 'array', 'message_ids.*' => 'integer', 'other_user_id' => 'nullable|exists:users,id', 'group_id' => 'nullable|exists:chat_groups,id']);
        $userId = Auth::id();
        $now = Carbon::now();

        if (!empty($request->message_ids)) {
            foreach ($request->message_ids as $mid) {
                ChatMessageRead::firstOrCreate(
                    ['message_id' => $mid, 'user_id' => $userId],
                    ['read_at' => $now]
                );
            }
        }
        if ($request->other_user_id) {
            $otherUserId = (int) $request->other_user_id;
            $messages = ChatMessage::where('sender_id', $otherUserId)
                ->where('recipient_id', $userId)
                ->whereNull('deleted_at')
                ->pluck('id');
            foreach ($messages as $mid) {
                ChatMessageRead::firstOrCreate(
                    ['message_id' => $mid, 'user_id' => $userId],
                    ['read_at' => $now]
                );
            }
            $this->clearActivityCache($userId, $otherUserId);
        }
        if ($request->group_id) {
            $groupId = (int) $request->group_id;
            if (ChatGroupMember::where('group_id', $groupId)->where('user_id', $userId)->exists()) {
                $messages = ChatMessage::where('group_id', $groupId)
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('deleted_at')
                    ->pluck('id');
                foreach ($messages as $mid) {
                    ChatMessageRead::firstOrCreate(
                        ['message_id' => $mid, 'user_id' => $userId],
                        ['read_at' => $now]
                    );
                }
                $this->clearActivityCache($userId);
                foreach (ChatGroupMember::where('group_id', $groupId)->pluck('user_id') as $uid) {
                    Cache::forget('chat_activity_' . $uid);
                }
            }
        }
        if (!empty($request->message_ids) && !$request->other_user_id && !$request->group_id) {
            $this->clearActivityCache($userId);
        }
        return response()->json(['ok' => true]);
    }

    public function deleteForMe(Request $request)
    {
        $request->validate(['message_id' => 'required|exists:chat_messages,id']);
        $userId = Auth::id();
        $msg = ChatMessage::find($request->message_id);
        if (!$msg || ($msg->sender_id !== $userId && $msg->recipient_id !== $userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        ChatMessageHidden::firstOrCreate(['user_id' => $userId, 'message_id' => $request->message_id]);
        return response()->json(['ok' => true]);
    }

    public function deleteForAll(Request $request)
    {
        $request->validate(['message_id' => 'required|exists:chat_messages,id']);
        $userId = Auth::id();
        $msg = ChatMessage::find($request->message_id);
        if (!$msg || $msg->sender_id !== $userId) {
            return response()->json(['error' => 'Only sender can unsend for everyone.'], 403);
        }
        if ($msg->type === 'file' && $msg->file_path) {
            if (Storage::disk('public')->exists($msg->file_path)) {
                Storage::disk('public')->delete($msg->file_path);
            } elseif (Storage::disk('local')->exists($msg->file_path)) {
                Storage::disk('local')->delete($msg->file_path);
            }
        }
        $msg->body = null;
        $msg->type = 'text';
        $msg->file_path = null;
        $msg->file_name = null;
        $msg->file_size = null;
        $msg->reply_to_message_id = null;
        $msg->deleted_for_everyone_at = Carbon::now();
        $msg->save();
        return response()->json(['ok' => true, 'message' => $this->formatMessage($msg->fresh(['sender:id,name,image', 'recipient:id,name,image', 'reads']), $userId)]);
    }

    public function history(Request $request, int $messageId)
    {
        $userId = Auth::id();
        $msg = ChatMessage::withTrashed()->with(['sender:id,name,image', 'recipient:id,name,image'])->find($messageId);
        if (!$msg) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $canAccess = $msg->sender_id === $userId || $msg->recipient_id === $userId;
        if ($msg->group_id) {
            $canAccess = ChatGroupMember::where('group_id', $msg->group_id)->where('user_id', $userId)->exists();
        }
        if (!$canAccess) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $reads = ChatMessageRead::where('message_id', $messageId)->with('user:id,name')->get();
        return response()->json([
            'message' => [
                'id' => $msg->id,
                'body' => $msg->body,
                'type' => $msg->type,
                'created_at' => $this->toUtcIso8601($msg->created_at),
                'updated_at' => $this->toUtcIso8601($msg->updated_at),
                'deleted_at' => $this->toUtcIso8601($msg->deleted_at),
                'deleted_for_everyone_at' => $this->toUtcIso8601($msg->deleted_for_everyone_at),
                'sender' => $msg->sender ? ['id' => $msg->sender->id, 'name' => $msg->sender->name] : null,
                'recipient' => $msg->recipient ? ['id' => $msg->recipient->id, 'name' => $msg->recipient->name] : null,
            ],
            'read_by' => $reads->map(fn($r) => ['user_id' => $r->user_id, 'name' => $r->user->name ?? '', 'read_at' => $this->toUtcIso8601($r->read_at)]),
        ]);
    }

    public function lastSeen(Request $request)
    {
        $userId = Auth::id();
        User::where('id', $userId)->update(['last_seen_at' => Carbon::now()]);

        $users = User::where('id', '!=', $userId)
            ->whereNull('deleted_at')
            ->select('id', 'last_seen_at', 'status')
            ->get();
        $list = $users->mapWithKeys(fn($u) => [$u->id => [
            'last_seen_at' => $this->toUtcIso8601($u->last_seen_at),
            'status' => $u->status,
        ]]);
        return response()->json(['last_seen' => $list]);
    }

    public function downloadFile(int $messageId)
    {
        $userId = Auth::id();
        $msg = ChatMessage::find($messageId);
        if (!$msg || $msg->type !== 'file' || !$msg->file_path) {
            abort(404);
        }
        $canAccess = $msg->sender_id === $userId || $msg->recipient_id === $userId;
        if ($msg->group_id) {
            $canAccess = ChatGroupMember::where('group_id', $msg->group_id)->where('user_id', $userId)->exists();
        }
        if (!$canAccess) {
            abort(403);
        }
        $disk = Storage::disk('public')->exists($msg->file_path)
            ? 'public'
            : (Storage::disk('local')->exists($msg->file_path) ? 'local' : null);
        if (!$disk) {
            abort(404);
        }
        $fileName = $msg->file_name ?? 'file';
        $inline = request()->boolean('inline');
        if ($inline && $fileName) {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
            $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
            $audioExts = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'weba', 'flac'];
            if (in_array($ext, $imageExts) || in_array($ext, $videoExts) || in_array($ext, $audioExts)) {
                return response()->file(Storage::disk($disk)->path($msg->file_path), [
                    'Content-Disposition' => 'inline; filename="' . basename($fileName) . '"',
                ]);
            }
        }
        return Storage::disk($disk)->download($msg->file_path, $fileName);
    }

    /**
     * Hide entire conversation for the current user. Then: if the other user has already
     * hidden this conversation too, permanently delete those messages and their files from DB.
     */
    public function deleteConversation(Request $request)
    {
        $request->validate(['other_user_id' => 'required|exists:users,id']);
        $userId = Auth::id();
        $otherUserId = (int) $request->other_user_id;
        if ($otherUserId === $userId) {
            return response()->json(['error' => 'Invalid user.'], 422);
        }

        $query = function ($q) use ($userId, $otherUserId) {
            $q->where(function ($q2) use ($userId, $otherUserId) {
                $q2->where('sender_id', $userId)->where('recipient_id', $otherUserId);
            })->orWhere(function ($q2) use ($userId, $otherUserId) {
                $q2->where('sender_id', $otherUserId)->where('recipient_id', $userId);
            });
        };

        $messageIds = ChatMessage::whereNull('deleted_at')->where($query)->pluck('id');

        foreach ($messageIds as $messageId) {
            ChatMessageHidden::firstOrCreate(['user_id' => $userId, 'message_id' => $messageId]);
        }

        $hiddenByBoth = ChatMessageHidden::whereIn('message_id', $messageIds)
            ->whereIn('user_id', [$userId, $otherUserId])
            ->get()
            ->groupBy('message_id')
            ->filter(fn ($rows) => $rows->pluck('user_id')->unique()->count() === 2)
            ->keys()
            ->all();

        if (!empty($hiddenByBoth)) {
            $messages = ChatMessage::withTrashed()->whereIn('id', $hiddenByBoth)->get();
            foreach ($messages as $msg) {
                if ($msg->type === 'file' && !empty($msg->file_path)) {
                    if (Storage::disk('public')->exists($msg->file_path)) {
                        Storage::disk('public')->delete($msg->file_path);
                    }
                    if (Storage::disk('local')->exists($msg->file_path)) {
                        Storage::disk('local')->delete($msg->file_path);
                    }
                }
            }
            DB::transaction(function () use ($hiddenByBoth) {
                ChatMessageHidden::whereIn('message_id', $hiddenByBoth)->delete();
                ChatMessageRead::whereIn('message_id', $hiddenByBoth)->delete();
                ChatMessage::withTrashed()->whereIn('id', $hiddenByBoth)->forceDelete();
            });
        }

        $this->clearActivityCache($userId, $otherUserId);
        return response()->json(['ok' => true]);
    }

    /**
     * Clear the activity response cache for the given user(s) so next poll gets fresh data.
     */
    private function clearActivityCache(int ...$userIds): void
    {
        foreach ($userIds as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
    }

    /**
     * Get sender's role in the group for group messages. Pass $groupRolesMap when formatting many messages to avoid N+1.
     */
    private function getSenderGroupRole(ChatMessage $m, ?array $groupRolesMap = null): ?string
    {
        if (!$m->group_id) {
            return null;
        }
        if ($groupRolesMap !== null) {
            return $groupRolesMap[$m->sender_id] ?? 'member';
        }
        return ChatGroupMember::where('group_id', $m->group_id)->where('user_id', $m->sender_id)->value('role') ?? 'member';
    }

    private function formatMessage(ChatMessage $m, int $currentUserId, ?array $groupRolesMap = null, ?array $groupNicknamesMap = null, ?string $contactNicknameForOther = null): array
    {
        $isSent = $m->sender_id === $currentUserId;
        $deletedForEveryone = $m->deleted_for_everyone_at !== null;
        $senderDisplayName = null;
        if ($m->group_id && $groupNicknamesMap !== null && isset($groupNicknamesMap[$m->sender_id]) && $groupNicknamesMap[$m->sender_id]) {
            $senderDisplayName = $groupNicknamesMap[$m->sender_id];
        } elseif ($m->recipient_id && !$isSent && $contactNicknameForOther) {
            $senderDisplayName = $contactNicknameForOther;
        }

        $read = null;
        $status = 'sent';
        if (!$deletedForEveryone && $m->recipient_id) {
            $read = $m->relationLoaded('reads')
                ? $m->reads->where('user_id', $m->recipient_id)->first()
                : $m->reads()->where('user_id', $m->recipient_id)->first();
            if ($read) {
                $status = 'seen';
            } elseif ($isSent) {
                $status = 'delivered';
            }
        }

        $fileUrl = null;
        if (!$deletedForEveryone && $m->type === 'file' && $m->file_path) {
            $fileUrl = route('chat.download', $m->id);
        }

        $replyTo = null;
        if (!$deletedForEveryone && $m->reply_to_message_id) {
            $r = $m->relationLoaded('replyTo') ? $m->replyTo : $m->replyTo()->with('sender:id,name')->first();
            $replyTo = [
                'id' => $m->reply_to_message_id,
                'body' => $r ? ($r->type === 'file' ? ($r->file_name ?? 'File') : ($r->body ?? '')) : 'Message',
                'type' => $r ? $r->type : 'text',
                'file_name' => $r ? $r->file_name : null,
                'sender_name' => $r && $r->sender ? $r->sender->name : null,
            ];
        }

        $reactions = [];
        if (!$deletedForEveryone && ($m->relationLoaded('reactions') ? $m->reactions->isNotEmpty() : $m->reactions()->exists())) {
            $reactionsList = $m->relationLoaded('reactions') ? $m->reactions : $m->reactions()->with('user:id,name')->get();
            $byEmoji = [];
            $otherUserId = $m->group_id ? null : ($isSent ? $m->recipient_id : $m->sender_id);
            foreach ($reactionsList as $reaction) {
                $emoji = $reaction->emoji;
                if (!isset($byEmoji[$emoji])) {
                    $byEmoji[$emoji] = ['emoji' => $emoji, 'count' => 0, 'user_ids' => [], 'users' => []];
                }
                $byEmoji[$emoji]['count']++;
                $byEmoji[$emoji]['user_ids'][] = $reaction->user_id;
                $name = $reaction->user ? $reaction->user->name : '';
                $displayName = $name;
                if ($m->group_id && $groupNicknamesMap !== null && isset($groupNicknamesMap[$reaction->user_id]) && $groupNicknamesMap[$reaction->user_id]) {
                    $displayName = $groupNicknamesMap[$reaction->user_id];
                } elseif ($otherUserId !== null && $reaction->user_id === $otherUserId && $contactNicknameForOther) {
                    $displayName = $contactNicknameForOther;
                }
                $byEmoji[$emoji]['users'][] = ['id' => $reaction->user_id, 'name' => $name, 'display_name' => $displayName];
            }
            $reactions = array_values($byEmoji);
        }

        $forwarded_from = null;
        if (!$deletedForEveryone && $m->forwarded_from_message_id && ($m->relationLoaded('forwardedFrom') && $m->forwardedFrom || $m->forwardedFrom)) {
            $f = $m->relationLoaded('forwardedFrom') ? $m->forwardedFrom : $m->forwardedFrom()->with('sender:id,name')->first();
            if ($f) {
                $forwarded_from = [
                    'message_id' => $f->id,
                    'sender_name' => $f->sender ? $f->sender->name : null,
                ];
            }
        }

        $isEdited = $m->edited_at !== null;

        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'recipient_id' => $m->recipient_id,
            'group_id' => $m->group_id,
            'body' => $deletedForEveryone ? null : $m->body,
            'type' => $m->type,
            'file_name' => $deletedForEveryone ? null : $m->file_name,
            'file_size' => $deletedForEveryone ? null : $m->file_size,
            'file_url' => $fileUrl,
            'reply_to_message_id' => $m->reply_to_message_id,
            'reply_to' => $replyTo,
            'forwarded_from' => $forwarded_from,
            'reactions' => $reactions,
            'created_at' => $this->toUtcIso8601($m->created_at),
            'updated_at' => $this->toUtcIso8601($m->updated_at),
            'edited_at' => $this->toUtcIso8601($m->edited_at),
            'is_edited' => $isEdited,
            'is_sent' => $isSent,
            'status' => $status,
            'read_at' => $this->toUtcIso8601($read?->read_at),
            'sender' => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name, 'image_url' => $m->sender->image_url] : null,
            'sender_display_name' => $senderDisplayName,
            'sender_group_role' => $this->getSenderGroupRole($m, $groupRolesMap),
            'deleted_for_everyone' => $deletedForEveryone,
        ];
    }

    /**
     * Add or update reaction on a message (one reaction per user per message).
     */
    public function react(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:chat_messages,id',
            'emoji' => 'required|string|max:32',
        ]);
        $userId = Auth::id();
        $msg = ChatMessage::find($request->message_id);
        if (!$msg || $msg->deleted_at) {
            return response()->json(['error' => 'Message not found'], 404);
        }
        $canAccess = $msg->recipient_id && ($msg->sender_id === $userId || $msg->recipient_id === $userId);
        if ($msg->group_id) {
            $canAccess = ChatGroupMember::where('group_id', $msg->group_id)->where('user_id', $userId)->exists();
        }
        if (!$canAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $reaction = ChatMessageReaction::updateOrCreate(
            ['message_id' => $msg->id, 'user_id' => $userId],
            ['emoji' => $request->emoji]
        );
        $msg->load(['reactions.user:id,name']);
        return response()->json(['ok' => true, 'reactions' => $this->formatReactions($msg->reactions)]);
    }

    /**
     * Remove reaction from a message.
     */
    public function removeReaction(Request $request)
    {
        $request->validate(['message_id' => 'required|exists:chat_messages,id']);
        $userId = Auth::id();
        $msg = ChatMessage::find($request->message_id);
        if (!$msg) {
            return response()->json(['error' => 'Message not found'], 404);
        }
        $canAccess = $msg->recipient_id && ($msg->sender_id === $userId || $msg->recipient_id === $userId);
        if ($msg->group_id) {
            $canAccess = ChatGroupMember::where('group_id', $msg->group_id)->where('user_id', $userId)->exists();
        }
        if (!$canAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        ChatMessageReaction::where('message_id', $msg->id)->where('user_id', $userId)->delete();
        $msg->load(['reactions.user:id,name']);
        return response()->json(['ok' => true, 'reactions' => $this->formatReactions($msg->reactions)]);
    }

    /**
     * Edit a sent message (text only). Allowed within 15 minutes of sending for both individual and group chat.
     */
    public function editMessage(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:chat_messages,id',
            'body' => 'required|string|max:10000',
        ]);
        $userId = Auth::id();
        $msg = ChatMessage::find($request->message_id);
        if (!$msg || $msg->deleted_at || $msg->deleted_for_everyone_at) {
            return response()->json(['error' => 'Message not found'], 404);
        }
        if ($msg->sender_id !== $userId) {
            return response()->json(['error' => 'You can only edit your own messages'], 403);
        }
        if ($msg->type !== 'text') {
            return response()->json(['error' => 'Only text messages can be edited'], 422);
        }
        $editDeadline = Carbon::now()->subMinutes(15);
        if ($msg->created_at->lt($editDeadline)) {
            return response()->json(['error' => 'Messages can only be edited within 15 minutes of sending'], 422);
        }
        $msg->body = $request->body;
        $msg->edited_at = Carbon::now();
        $msg->save();
        $msg->load(['sender:id,name,image', 'recipient:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name', 'forwardedFrom.sender:id,name']);
        $groupNicknamesMap = null;
        if ($msg->group_id) {
            $groupNicknamesMap = ChatGroupMember::where('group_id', $msg->group_id)->get()->keyBy('user_id')->map(fn($p) => $p->nickname)->all();
        }
        $contactNickname = null;
        if ($msg->recipient_id) {
            $contactNickname = ChatContactNickname::where('user_id', $userId)->where('contact_user_id', $msg->recipient_id)->value('nickname');
        }
        return response()->json(['message' => $this->formatMessage($msg, $userId, null, $groupNicknamesMap, $contactNickname)]);
    }

    private function formatReactions($reactions): array
    {
        $byEmoji = [];
        foreach ($reactions as $r) {
            $emoji = $r->emoji;
            if (!isset($byEmoji[$emoji])) {
                $byEmoji[$emoji] = ['emoji' => $emoji, 'count' => 0, 'user_ids' => [], 'users' => []];
            }
            $byEmoji[$emoji]['count']++;
            $byEmoji[$emoji]['user_ids'][] = $r->user_id;
            $byEmoji[$emoji]['users'][] = ['id' => $r->user_id, 'name' => $r->user ? $r->user->name : ''];
        }
        return array_values($byEmoji);
    }

    /**
     * Forward a message to a user or to a group.
     */
    public function forward(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:chat_messages,id',
            'recipient_id' => 'nullable|exists:users,id',
            'group_id' => 'nullable|exists:chat_groups,id',
        ]);
        $userId = Auth::id();
        if (!$request->recipient_id && !$request->group_id) {
            return response()->json(['error' => 'Provide recipient_id or group_id'], 422);
        }
        if ($request->recipient_id && $request->group_id) {
            return response()->json(['error' => 'Provide only one of recipient_id or group_id'], 422);
        }
        $source = ChatMessage::find($request->message_id);
        if (!$source || $source->deleted_at || $source->deleted_for_everyone_at) {
            return response()->json(['error' => 'Message not found'], 404);
        }
        $canAccessSource = $source->recipient_id && ($source->sender_id === $userId || $source->recipient_id === $userId);
        if ($source->group_id) {
            $canAccessSource = ChatGroupMember::where('group_id', $source->group_id)->where('user_id', $userId)->exists();
        }
        if (!$canAccessSource) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($request->recipient_id) {
            if ((int) $request->recipient_id === $userId) {
                return response()->json(['error' => 'Invalid recipient'], 422);
            }
            $msg = ChatMessage::create([
                'sender_id' => $userId,
                'recipient_id' => $request->recipient_id,
                'body' => $source->body,
                'type' => $source->type,
                'file_path' => $source->file_path,
                'file_name' => $source->file_name,
                'file_size' => $source->file_size,
                'forwarded_from_message_id' => $source->id,
            ]);
            $msg->load(['sender:id,name,image', 'recipient:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name', 'forwardedFrom.sender:id,name']);
            $this->clearActivityCache($userId, (int) $request->recipient_id);
            return response()->json(['message' => $this->formatMessage($msg, $userId)]);
        }
        $groupId = (int) $request->group_id;
        if (!ChatGroupMember::where('group_id', $groupId)->where('user_id', $userId)->exists()) {
            return response()->json(['error' => 'Not a group member'], 403);
        }
        $msg = ChatMessage::create([
            'sender_id' => $userId,
            'group_id' => $groupId,
            'body' => $source->body,
            'type' => $source->type,
            'file_path' => $source->file_path,
            'file_name' => $source->file_name,
            'file_size' => $source->file_size,
            'forwarded_from_message_id' => $source->id,
        ]);
        $msg->load(['sender:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name', 'forwardedFrom.sender:id,name']);
        $this->clearActivityCache($userId);
        Cache::forget('chat_activity_' . $userId);
        foreach (ChatGroupMember::where('group_id', $groupId)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        $groupNicknamesMap = ChatGroupMember::where('group_id', $groupId)->get()->keyBy('user_id')->map(fn($p) => $p->nickname)->all();
        return response()->json(['message' => $this->formatMessage($msg, $userId, null, $groupNicknamesMap)]);
    }

    /**
     * List groups the current user is in.
     */
    public function groups(Request $request)
    {
        $userId = Auth::id();
        $groupIds = ChatGroupMember::where('user_id', $userId)->pluck('group_id');
        $groups = ChatGroup::whereIn('id', $groupIds)->with('creator:id,name')->get()->map(function ($g) {
            return [
                'id' => $g->id,
                'name' => $g->name,
                'image_url' => $g->image ? getUploadedUrl($g->image) : null,
                'created_by_user_id' => $g->created_by_user_id,
                'creator_name' => $g->creator ? $g->creator->name : null,
            ];
        });
        return response()->json(['groups' => $groups]);
    }

    /**
     * Create a new group. Creator is added as admin. At least one other member required.
     */
    public function groupCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:users,id',
        ]);
        $userId = Auth::id();
        $memberIds = array_unique(array_map('intval', $request->member_ids));
        $memberIds = array_values(array_filter($memberIds, fn($id) => $id !== $userId));
        $memberIds = User::whereIn('id', $memberIds)->where(function ($q) {
            $q->where('is_automation_chatbot', '!=', 1)->orWhereNull('is_automation_chatbot');
        })->pluck('id')->all();
        if (empty($memberIds)) {
            return response()->json(['error' => 'Add at least one other member'], 422);
        }
        $group = ChatGroup::create(['name' => $request->name, 'created_by_user_id' => $userId]);
        ChatGroupMember::create(['group_id' => $group->id, 'user_id' => $userId, 'role' => 'admin']);
        foreach ($memberIds as $mid) {
            ChatGroupMember::create(['group_id' => $group->id, 'user_id' => $mid, 'role' => 'member']);
        }
        $this->clearActivityCache($userId);
        foreach (array_merge($memberIds, [$userId]) as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        return response()->json([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'image_url' => null,
                'created_by_user_id' => $group->created_by_user_id,
                'creator_name' => Auth::user()->name,
            ],
        ]);
    }

    /**
     * Add members to an existing group. Only admins or creator can add.
     */
    public function groupAddMembers(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        $userId = Auth::id();
        $group = ChatGroup::find($request->group_id);
        $pivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
        if (!$pivot || $pivot->role !== 'admin') {
            return response()->json(['error' => 'Only group admins can add members'], 403);
        }
        $userIds = array_unique(array_map('intval', $request->user_ids));
        $userIds = User::whereIn('id', $userIds)->where(function ($q) {
            $q->where('is_automation_chatbot', '!=', 1)->orWhereNull('is_automation_chatbot');
        })->pluck('id')->all();
        $existing = ChatGroupMember::where('group_id', $group->id)->pluck('user_id')->all();
        $toAdd = array_diff($userIds, $existing);
        foreach ($toAdd as $uid) {
            ChatGroupMember::create(['group_id' => $group->id, 'user_id' => $uid, 'role' => 'member']);
            ChatGroupEvent::create(['group_id' => $group->id, 'user_id' => $userId, 'action' => 'member_added', 'target_user_id' => $uid]);
        }
        foreach (array_merge($toAdd, $existing) as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        return response()->json(['ok' => true, 'added_count' => count($toAdd)]);
    }

    /**
     * Update group (profile image and/or name). Only group admins can update.
     */
    public function groupUpdate(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        $userId = Auth::id();
        $group = ChatGroup::find($request->group_id);
        $pivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
        if (!$pivot || $pivot->role !== 'admin') {
            return response()->json(['error' => 'Only group admins can update the group'], 403);
        }
        if ($request->hasFile('image')) {
            $oldImage = $group->image;
            $path = handleImageUpload($request->file('image'), 300, 300, 'chat-groups', 'group-' . $group->id, $oldImage);
            if ($path) {
                $group->image = $path;
            }
        }
        if ($request->filled('name')) {
            $group->name = $request->name;
        }
        $group->save();
        $group->load('creator:id,name');
        $this->clearActivityCache($userId);
        foreach (ChatGroupMember::where('group_id', $group->id)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        return response()->json([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'image_url' => $group->image ? getUploadedUrl($group->image) : null,
                'created_by_user_id' => $group->created_by_user_id,
                'creator_name' => $group->creator ? $group->creator->name : null,
            ],
        ]);
    }

    /**
     * Set member role (admin/member). Only group creator can change roles.
     */
    public function groupSetMemberRole(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member',
        ]);
        $userId = Auth::id();
        $group = ChatGroup::findOrFail($request->group_id);
        if ($group->created_by_user_id !== $userId) {
            return response()->json(['error' => 'Only the group creator can change roles'], 403);
        }
        $targetUserId = (int) $request->user_id;
        if ($targetUserId === $userId) {
            return response()->json(['error' => 'You cannot change your own role'], 422);
        }
        $pivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $targetUserId)->first();
        if (!$pivot) {
            return response()->json(['error' => 'User is not a member'], 404);
        }
        $pivot->role = $request->role;
        $pivot->save();
        ChatGroupEvent::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'action' => $request->role === 'admin' ? 'admin_added' : 'admin_removed',
            'target_user_id' => $targetUserId,
        ]);
        $this->clearActivityCache($userId);
        foreach (ChatGroupMember::where('group_id', $group->id)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        return response()->json(['ok' => true, 'role' => $pivot->role]);
    }

    /**
     * Remove a member from the group. Creator or admin. Admin cannot remove creator.
     */
    public function groupRemoveMember(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $userId = Auth::id();
        $group = ChatGroup::findOrFail($request->group_id);
        $myPivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
        if (!$myPivot) {
            return response()->json(['error' => 'Not a group member'], 403);
        }
        $isCreator = $group->created_by_user_id === $userId;
        if (!$isCreator && $myPivot->role !== 'admin') {
            return response()->json(['error' => 'Only creator or admins can remove members'], 403);
        }
        $targetUserId = (int) $request->user_id;
        if ($targetUserId === $group->created_by_user_id) {
            return response()->json(['error' => 'The group creator cannot be removed'], 403);
        }
        $pivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $targetUserId)->first();
        if (!$pivot) {
            return response()->json(['error' => 'User is not a member'], 404);
        }
        ChatGroupEvent::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'action' => 'member_removed',
            'target_user_id' => $targetUserId,
        ]);
        $pivot->delete();
        $this->clearActivityCache($userId);
        foreach (ChatGroupMember::where('group_id', $group->id)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        Cache::forget('chat_activity_' . $targetUserId);
        return response()->json(['ok' => true]);
    }

    /**
     * Leave the group. Only non-creator members can leave; creator cannot leave.
     */
    public function groupLeave(Request $request)
    {
        $request->validate(['group_id' => 'required|exists:chat_groups,id']);
        $userId = Auth::id();
        $group = ChatGroup::findOrFail($request->group_id);
        if ($group->created_by_user_id === $userId) {
            return response()->json(['error' => 'The group creator cannot leave the group'], 403);
        }
        $pivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
        if (!$pivot) {
            return response()->json(['error' => 'Not a group member'], 404);
        }
        ChatGroupEvent::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'action' => 'member_left',
            'target_user_id' => $userId,
        ]);
        $pivot->delete();
        $this->clearActivityCache($userId);
        foreach (ChatGroupMember::where('group_id', $group->id)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Delete the group. Only group creator can delete.
     * Permanently deletes the group, all messages (force delete), and all file attachments from storage.
     */
    public function groupDelete(Request $request)
    {
        $request->validate(['group_id' => 'required|exists:chat_groups,id']);
        $userId = Auth::id();
        $group = ChatGroup::findOrFail($request->group_id);
        if ($group->created_by_user_id !== $userId) {
            return response()->json(['error' => 'Only the group creator can delete the group'], 403);
        }
        $memberIds = ChatGroupMember::where('group_id', $group->id)->pluck('user_id')->all();
        $messageIds = ChatMessage::where('group_id', $group->id)->pluck('id')->all();
        // Permanently delete all file attachments from storage (group is being deleted)
        ChatMessage::where('group_id', $group->id)
            ->where('type', 'file')
            ->whereNotNull('file_path')
            ->get()
            ->each(function (ChatMessage $msg) {
                if (Storage::disk('public')->exists($msg->file_path)) {
                    Storage::disk('public')->delete($msg->file_path);
                }
                if (Storage::disk('local')->exists($msg->file_path)) {
                    Storage::disk('local')->delete($msg->file_path);
                }
            });
        ChatMessageReaction::whereIn('message_id', $messageIds)->delete();
        ChatMessageRead::whereIn('message_id', $messageIds)->delete();
        ChatMessageHidden::whereIn('message_id', $messageIds)->delete();
        ChatMessage::where('group_id', $group->id)->forceDelete();
        ChatGroupEvent::where('group_id', $group->id)->delete();
        ChatGroupMember::where('group_id', $group->id)->delete();
        if ($group->image) {
            if (Storage::disk('public')->exists($group->image)) {
                Storage::disk('public')->delete($group->image);
            }
            if (Storage::disk('local')->exists($group->image)) {
                Storage::disk('local')->delete($group->image);
            }
        }
        $group->delete();
        $this->clearActivityCache($userId);
        foreach (array_merge($memberIds, [$userId]) as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Set nickname: for group (group_id, user_id optional, nickname) or 1:1 (contact_user_id, nickname).
     */
    public function setNickname(Request $request)
    {
        $userId = Auth::id();
        if ($request->filled('group_id')) {
            $request->validate([
                'group_id' => 'required|exists:chat_groups,id',
                'user_id' => 'nullable|exists:users,id',
                'nickname' => 'nullable|string|max:100',
            ]);
            $group = ChatGroup::findOrFail($request->group_id);
            $targetUserId = $request->user_id ? (int) $request->user_id : $userId;
            $pivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $targetUserId)->first();
            if (!$pivot) {
                return response()->json(['error' => 'Not a group member'], 404);
            }
            $myPivot = ChatGroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
            if (!$myPivot) {
                return response()->json(['error' => 'Not a group member'], 403);
            }
            $isCreator = $group->created_by_user_id === $userId;
            $isAdmin = $myPivot->role === 'admin';
            if (!$isCreator && !$isAdmin) {
                return response()->json(['error' => 'Only creator or admins can set or clear nicknames in this group'], 403);
            }
            $nicknameValue = trim($request->nickname) ?: null;
            $pivot->nickname = $nicknameValue;
            $pivot->save();
            ChatGroupEvent::create([
                'group_id' => $group->id,
                'user_id' => $userId,
                'action' => $nicknameValue !== null ? 'nickname_set' : 'nickname_cleared',
                'target_user_id' => $targetUserId,
                'extra' => $nicknameValue ?? '',
            ]);
            $this->clearActivityCache($userId);
            foreach (ChatGroupMember::where('group_id', $group->id)->pluck('user_id') as $uid) {
                Cache::forget('chat_activity_' . $uid);
            }
            return response()->json(['ok' => true, 'nickname' => $pivot->nickname]);
        }
        $request->validate([
            'contact_user_id' => 'required|exists:users,id',
            'user_id' => 'nullable|exists:users,id',
            'nickname' => 'nullable|string|max:100',
        ]);
        $contactUserId = (int) $request->contact_user_id;
        $nickname = trim($request->nickname) ?: null;
        if ($contactUserId === $userId) {
            $viewerId = $request->user_id ? (int) $request->user_id : null;
            if (!$viewerId || $viewerId === $userId) {
                return response()->json(['error' => 'Invalid request'], 422);
            }
            $existing = ChatContactNickname::where('user_id', $viewerId)->where('contact_user_id', $userId)->first();
            $previousNick = $existing && trim((string) $existing->nickname) !== '' ? trim($existing->nickname) : null;
            ChatContactNickname::updateOrCreate(
                ['user_id' => $viewerId, 'contact_user_id' => $userId],
                ['nickname' => $nickname ?? '']
            );
            $shouldLogCleared = $nickname === null && $previousNick !== null;
            $shouldLogSet = $nickname !== null && $nickname !== $previousNick;
            if ($shouldLogCleared || $shouldLogSet) {
                ChatContactEvent::create([
                    'user_id' => $userId,
                    'contact_user_id' => $viewerId,
                    'action' => $nickname !== null ? 'my_nickname_set' : 'my_nickname_cleared',
                    'extra' => $nickname ?? '',
                ]);
            }
            $this->clearActivityCache($userId);
            Cache::forget('chat_activity_' . $userId);
            Cache::forget('chat_activity_' . $viewerId);
            return response()->json(['ok' => true, 'nickname' => $nickname]);
        }
        $existingContact = ChatContactNickname::where('user_id', $userId)->where('contact_user_id', $contactUserId)->first();
        $previousContactNick = $existingContact && trim((string) $existingContact->nickname) !== '' ? trim($existingContact->nickname) : null;
        $newNormalized = ($nickname !== null && trim((string) $nickname) !== '') ? trim($nickname) : null;
        ChatContactNickname::updateOrCreate(
            ['user_id' => $userId, 'contact_user_id' => $contactUserId],
            ['nickname' => $nickname ?? '']
        );
        $contactChanged = ($previousContactNick !== $newNormalized);
        if ($contactChanged) {
            ChatContactEvent::create([
                'user_id' => $userId,
                'contact_user_id' => $contactUserId,
                'action' => $newNormalized !== null ? 'nickname_set' : 'nickname_cleared',
                'extra' => $newNormalized ?? '',
            ]);
        }
        $this->clearActivityCache($userId);
        Cache::forget('chat_activity_' . $userId);
        Cache::forget('chat_activity_' . $contactUserId);
        return response()->json(['ok' => true, 'nickname' => $nickname]);
    }

    /**
     * Get messages for a group. Query params: before_id, limit.
     */
    public function groupMessages(Request $request, int $groupId)
    {
        $userId = Auth::id();
        if (!ChatGroupMember::where('group_id', $groupId)->where('user_id', $userId)->exists()) {
            return response()->json(['error' => 'Not a group member'], 403);
        }
        $hiddenIds = ChatMessageHidden::where('user_id', $userId)->pluck('message_id');
        $limit = min(max((int) $request->get('limit', 50), 1), 100);
        $beforeId = $request->get('before_id') ? (int) $request->get('before_id') : null;

        $query = ChatMessage::with(['sender:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name', 'forwardedFrom.sender:id,name'])
            ->whereNull('chat_messages.deleted_at')
            ->where('group_id', $groupId)
            ->whereNotIn('id', $hiddenIds);

        if ($beforeId) {
            $query->where('id', '<', $beforeId)->orderByDesc('id')->limit($limit);
            $messages = $query->get()->reverse()->values();
        } else {
            $messages = $query->orderByDesc('id')->limit($limit)->get()->reverse()->values();
        }
        $hasMore = $messages->count() === $limit;
        $pivots = ChatGroupMember::where('group_id', $groupId)->get();
        $groupRolesMap = $pivots->keyBy('user_id')->map(fn($p) => $p->role)->all();
        $groupNicknamesMap = $pivots->keyBy('user_id')->map(fn($p) => $p->nickname)->all();
        $list = $messages->map(fn($m) => $this->formatMessage($m, $userId, $groupRolesMap, $groupNicknamesMap))->all();
        $events = ChatGroupEvent::where('group_id', $groupId)
            ->with(['user:id,name', 'targetUser:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn($e) => $this->formatGroupEvent($e))->all();
        return response()->json(['messages' => $list, 'has_more' => $hasMore, 'group_events' => $events]);
    }

    private function formatGroupEvent(ChatGroupEvent $e): array
    {
        $actorName = $e->user ? $e->user->name : '';
        $targetName = $e->targetUser ? $e->targetUser->name : '';
        $text = '';
        switch ($e->action) {
            case 'member_added':
                $text = $actorName . ' added ' . $targetName;
                break;
            case 'member_removed':
                $text = $actorName . ' removed ' . $targetName;
                break;
            case 'member_left':
                $text = $actorName . ' left the group';
                break;
            case 'admin_added':
                $text = $actorName . ' added ' . $targetName . ' as admin';
                break;
            case 'admin_removed':
                $text = $actorName . ' removed ' . $targetName . '\'s admin access';
                break;
            case 'nickname_set':
                $nickname = $e->extra ?: $targetName;
                $text = $actorName . ' set ' . $targetName . '\'s nickname to ' . $nickname;
                break;
            case 'nickname_cleared':
                $text = $actorName . ' Cleared ' . $targetName . '\'s nickname';
                break;
            default:
                $text = $actorName . ' – ' . $e->action;
        }
        return [
            'id' => 'event-' . $e->id,
            'type' => 'event',
            'action' => $e->action,
            'user_id' => $e->user_id,
            'user_name' => $actorName,
            'target_user_id' => $e->target_user_id,
            'target_user_name' => $targetName,
            'extra' => $e->extra,
            'created_at' => $this->toUtcIso8601($e->created_at),
            'text' => $text,
        ];
    }

    private function formatContactEvent(ChatContactEvent $e, int $viewerId): array
    {
        $actorName = $e->user ? $e->user->name : '';
        $contactName = $e->contactUser ? $e->contactUser->name : '';
        $isViewerActor = (int) $e->user_id === $viewerId;
        $isViewerContact = (int) $e->contact_user_id === $viewerId;
        $actorDisplay = $isViewerActor ? 'You' : $actorName;
        $nicknameTarget = $isViewerContact ? 'Your nickname' : ($contactName . '\'s nickname');
        $text = '';
        switch ($e->action) {
            case 'nickname_set':
                $nickname = $e->extra ?: $contactName;
                $text = $actorDisplay . ' set ' . $nicknameTarget . ' to ' . $nickname;
                break;
            case 'nickname_cleared':
                $text = $actorDisplay . ' cleared ' . $nicknameTarget;
                break;
            case 'my_nickname_set':
                $nickname = $e->extra ?: '';
                $text = $isViewerActor ? ('You set your nickname to ' . $nickname) : ($actorName . ' set their nickname to ' . $nickname);
                break;
            case 'my_nickname_cleared':
                $text = $isViewerActor ? 'You cleared your nickname' : ($actorName . ' cleared their nickname');
                break;
            default:
                $text = $actorDisplay . ' – ' . $e->action;
        }
        return [
            'id' => 'contact-event-' . $e->id,
            'type' => 'event',
            'action' => $e->action,
            'user_id' => $e->user_id,
            'user_name' => $actorName,
            'contact_user_id' => $e->contact_user_id,
            'contact_user_name' => $contactName,
            'extra' => $e->extra,
            'created_at' => $this->toUtcIso8601($e->created_at),
            'text' => $text,
        ];
    }

    /**
     * Send a text message to a group.
     */
    public function sendGroup(Request $request)
    {
        $request->validate([
            'body' => 'required|string|max:10000',
            'group_id' => 'required|exists:chat_groups,id',
            'reply_to_message_id' => 'nullable|exists:chat_messages,id',
        ]);
        $userId = Auth::id();
        if (!ChatGroupMember::where('group_id', $request->group_id)->where('user_id', $userId)->exists()) {
            return response()->json(['error' => 'Not a group member'], 403);
        }
        $replyToId = $request->reply_to_message_id ? (int) $request->reply_to_message_id : null;
        if ($replyToId) {
            $replyMsg = ChatMessage::where('id', $replyToId)->where('group_id', $request->group_id)->first();
            if (!$replyMsg || $replyMsg->deleted_at) {
                return response()->json(['error' => 'Invalid reply message'], 422);
            }
        }
        $msg = ChatMessage::create([
            'sender_id' => $userId,
            'group_id' => $request->group_id,
            'body' => $request->body,
            'type' => 'text',
            'reply_to_message_id' => $replyToId,
        ]);
        $msg->load(['sender:id,name,image', 'reads', 'replyTo.sender:id,name', 'reactions.user:id,name', 'forwardedFrom.sender:id,name']);
        $this->clearActivityCache($userId);
        foreach (ChatGroupMember::where('group_id', $request->group_id)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        $groupNicknamesMap = ChatGroupMember::where('group_id', $request->group_id)->get()->keyBy('user_id')->map(fn($p) => $p->nickname)->all();
        return response()->json(['message' => $this->formatMessage($msg, $userId, null, $groupNicknamesMap)]);
    }

    /**
     * Send a file to a group.
     */
    public function sendGroupFile(Request $request)
    {
        $maxKb = config('chat.max_file_size_kb', 0);
        $rules = [
            'group_id' => 'required|exists:chat_groups,id',
            'file' => ['required', 'file'],
        ];
        if ($maxKb > 0) {
            $rules['file'][] = 'max:' . $maxKb;
        }
        $request->validate($rules, $maxKb > 0 ? [
            'file.max' => 'File size must not exceed ' . round($maxKb / 1024, 1) . ' MB.',
        ] : []);

        $userId = Auth::id();
        if (!ChatGroupMember::where('group_id', $request->group_id)->where('user_id', $userId)->exists()) {
            return response()->json(['error' => 'Not a group member'], 403);
        }

        $file = $request->file('file');
        $fileSize = $file->getSize();
        if ($maxKb > 0 && (int) ceil($fileSize / 1024) > $maxKb) {
            return response()->json(['error' => 'File too large.'], 422);
        }

        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic'];
        $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'm4v', 'wmv'];

        if (in_array($ext, $imageExts)) {
            $path = handleImageUpload($file, 1200, 1200, 'chat-files', 'chat', null);
            if (!$path) {
                return response()->json(['error' => 'Failed to process image.'], 422);
            }
        } elseif (in_array($ext, $videoExts)) {
            $path = convertVideo($file, 'chat-files', 'chat', ['max_width' => 1280, 'max_height' => 720]);
            if (!$path) {
                return response()->json(['error' => 'Failed to process video.'], 422);
            }
            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: $ext;
        } else {
            $path = $file->store('chat-files', 'local');
        }

        $appName = trim(preg_replace('/[\\\\\/:*?"<>|]/', '', config('app.name', env('APP_NAME', 'App'))));
        $fileTypeLabel = in_array($ext, $imageExts) ? 'Image' : (in_array($ext, $videoExts) ? 'Video' : ($ext === 'pdf' ? 'PDF' : 'File'));
        $tz = config('app.timezone', env('APP_TIMEZONE', 'UTC'));
        $tzSafe = trim(str_replace(['/', '\\'], '-', $tz));
        $uniqueSuffix = bin2hex(random_bytes(4));
        $displayFileName = $appName . ' ' . $fileTypeLabel . '-' . Carbon::now()->format('Y-m-d \a\t g.i.s A') . ' (' . $tzSafe . ')-' . $uniqueSuffix . '.' . $ext;

        $msg = ChatMessage::create([
            'sender_id' => $userId,
            'group_id' => $request->group_id,
            'body' => null,
            'type' => 'file',
            'file_path' => $path,
            'file_name' => $displayFileName,
            'file_size' => $fileSize,
        ]);
        $msg->load(['sender:id,name,image', 'reads', 'reactions.user:id,name', 'forwardedFrom.sender:id,name']);
        $this->clearActivityCache($userId);
        foreach (ChatGroupMember::where('group_id', $request->group_id)->pluck('user_id') as $uid) {
            Cache::forget('chat_activity_' . $uid);
        }
        $groupNicknamesMap = ChatGroupMember::where('group_id', $request->group_id)->get()->keyBy('user_id')->map(fn($p) => $p->nickname)->all();
        return response()->json(['message' => $this->formatMessage($msg, $userId, null, $groupNicknamesMap)]);
    }
}

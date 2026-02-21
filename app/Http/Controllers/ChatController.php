<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatMessageHidden;
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
            ->select('id', 'uid', 'name', 'image', 'last_seen_at', 'status')
            ->orderBy('name')
            ->get();

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

            $conversations[] = [
                'user' => [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'image_url' => $user->image_url,
                    'last_seen_at' => $this->toUtcIso8601($user->last_seen_at),
                    'status' => $user->status,
                ],
                'last_message' => $lastMessage ? $this->formatMessage($lastMessage, $userId) : null,
                'unread_count' => $unread,
            ];
        }

        usort($conversations, function ($a, $b) {
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

        $query = ChatMessage::with(['sender:id,name,image', 'recipient:id,name,image', 'reads', 'replyTo.sender:id,name'])
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
        $list = $messages->map(fn($m) => $this->formatMessage($m, $userId))->all();
        return response()->json(['messages' => $list, 'has_more' => $hasMore]);
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
            $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . (pathinfo($path, PATHINFO_EXTENSION) ?: $ext);
        } else {
            $path = $file->store('chat-files', 'local');
        }

        $msg = ChatMessage::create([
            'sender_id' => $userId,
            'recipient_id' => $request->recipient_id,
            'body' => null,
            'type' => 'file',
            'file_path' => $path,
            'file_name' => $originalName,
            'file_size' => $fileSize,
        ]);
        $msg->load(['sender:id,name,image', 'recipient:id,name,image', 'reads']);
        $this->clearActivityCache($userId, (int) $request->recipient_id);
        return response()->json(['message' => $this->formatMessage($msg, $userId)]);
    }

    public function markRead(Request $request)
    {
        $request->validate(['message_ids' => 'array', 'message_ids.*' => 'integer', 'other_user_id' => 'nullable|exists:users,id']);
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
        } elseif (!empty($request->message_ids)) {
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
        if (!$msg || ($msg->sender_id !== $userId && $msg->recipient_id !== $userId)) {
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
        if ($msg->sender_id !== $userId && $msg->recipient_id !== $userId) {
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
    /**
     * Delete entire conversation with another user: permanently remove all messages
     * and their files from storage and database (no soft delete).
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

        // Include soft-deleted messages so we permanently remove everything
        $messages = ChatMessage::withTrashed()->where($query)->get();
        $messageIds = $messages->pluck('id')->all();

        if (empty($messageIds)) {
            return response()->json(['ok' => true]);
        }

        // 1. Permanently delete files from server (storage)
        foreach ($messages as $msg) {
            if ($msg->type === 'file' && !empty($msg->file_path)) {
                $path = $msg->file_path;
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
        }

        // 2. Permanently delete from database (no soft delete)
        DB::transaction(function () use ($messageIds) {
            ChatMessageHidden::whereIn('message_id', $messageIds)->delete();
            ChatMessageRead::whereIn('message_id', $messageIds)->delete();
            ChatMessage::withTrashed()->whereIn('id', $messageIds)->forceDelete();
        });

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

    private function formatMessage(ChatMessage $m, int $currentUserId): array
    {
        $isSent = $m->sender_id === $currentUserId;
        $deletedForEveryone = $m->deleted_for_everyone_at !== null;

        $read = $deletedForEveryone ? null : ($m->relationLoaded('reads')
            ? $m->reads->where('user_id', $m->recipient_id)->first()
            : $m->reads()->where('user_id', $m->recipient_id)->first());
        $status = 'sent';
        if ($read) {
            $status = 'seen';
        } elseif ($isSent && $m->recipient_id) {
            $status = 'delivered';
        }

        $fileUrl = null;
        if (!$deletedForEveryone && $m->type === 'file' && $m->file_path) {
            $fileUrl = route('chat.download', $m->id);
        }

        $replyTo = null;
        if (!$deletedForEveryone && $m->reply_to_message_id && $m->relationLoaded('replyTo') && $m->replyTo) {
            $r = $m->replyTo;
            $replyTo = [
                'id' => $r->id,
                'body' => $r->type === 'file' ? ($r->file_name ?? 'File') : ($r->body ?? ''),
                'type' => $r->type,
                'file_name' => $r->file_name,
                'sender_name' => $r->sender ? $r->sender->name : null,
            ];
        }

        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'recipient_id' => $m->recipient_id,
            'body' => $deletedForEveryone ? null : $m->body,
            'type' => $m->type,
            'file_name' => $deletedForEveryone ? null : $m->file_name,
            'file_size' => $deletedForEveryone ? null : $m->file_size,
            'file_url' => $fileUrl,
            'reply_to' => $replyTo,
            'created_at' => $this->toUtcIso8601($m->created_at),
            'updated_at' => $this->toUtcIso8601($m->updated_at),
            'is_sent' => $isSent,
            'status' => $status,
            'read_at' => $this->toUtcIso8601($read?->read_at),
            'sender' => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name, 'image_url' => $m->sender->image_url] : null,
            'deleted_for_everyone' => $deletedForEveryone,
        ];
    }
}

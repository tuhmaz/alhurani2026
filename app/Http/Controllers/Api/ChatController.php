<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\ChatBlock;

class ChatController extends Controller
{
    // إرجاع جميع المحادثات (العامة + الخاصة الخاصة بالمستخدم)
    public function conversations()
    {
        $user = Auth::user();

        // ضمان وجود محادثة عامة واحدة على الأقل
        if (!Conversation::where('type', 'public')->exists()) {
            $uid = Auth::id() ?: User::query()->value('id');
            Conversation::create([
                'type' => 'public',
                'title' => 'الدردشة العامة',
                'user1_id' => $uid,
                'user2_id' => $uid,
            ]);
        }

        $conversations = Conversation::where('type', 'public')
            ->orWhereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with([
                'users:id,name',
                'messages' => function ($q) {
                    $q->where('is_chat', true)->latest()->limit(1);
                },
                'messages.sender:id,name,profile_photo_path'
            ])
            ->get();

        return response()->json(['conversations' => $conversations]);
    }

    // جلب رسائل محادثة محددة مع دعم after
    public function messages($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->type === 'private' && !$conversation->users->contains(Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $after = request()->query('after');

        $messages = $conversation->messages()
            ->where('is_chat', true)
            ->when($after, function ($q) use ($after) {
                $q->where('id', '>', (int) $after);
            })
            ->with('sender:id,name,profile_photo_path')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    // إرسال رسالة داخل محادثة
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:255',
        ]);

        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->type === 'private' && !$conversation->users->contains(Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // منع الإرسال في حال وجود حظر متبادل
        if ($conversation->type === 'private') {
            $currentUser = Auth::user();
            $toUserId = $conversation->users()->where('users.id', '!=', $currentUser->id)->first()?->id;
            if ($toUserId) {
                $blocked = ChatBlock::where('blocker_id', $currentUser->id)->where('blocked_id', $toUserId)->exists()
                    || ChatBlock::where('blocker_id', $toUserId)->where('blocked_id', $currentUser->id)->exists();
                if ($blocked) {
                    return response()->json(['success' => false, 'message' => 'لا يمكن إرسال الرسائل، يوجد حظر بين الطرفين.'], 403);
                }
            }
        }

        $message = $conversation->messages()->create([
            'sender_id' => Auth::id(),
            'subject'   => $request->subject ?? '',
            'body'      => $request->body,
            'is_chat'   => true,
        ]);

        $message->load('sender:id,name');

        // إرسال إشعارات اختيارياً (تعتمد على إعدادات النظام)
        try {
            $receivers = $conversation->users()->where('users.id', '!=', Auth::id())->get();
            foreach ($receivers as $user) {
                if (class_exists(\App\Notifications\ChatMessageReceived::class)) {
                    $user->notify(new \App\Notifications\ChatMessageReceived(Auth::user()->name, $request->body, $conversation->id));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('chat send notify failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['message' => $message]);
    }

    // إنشاء/إرجاع محادثة خاصة بين مستخدمين
    public function createPrivateConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user1 = Auth::id();
        $user2 = (int) $request->user_id;

        $conversation = Conversation::where('type', 'private')
            ->whereHas('users', function ($q) use ($user1) {
                $q->where('users.id', $user1);
            })
            ->whereHas('users', function ($q) use ($user2) {
                $q->where('users.id', $user2);
            })
            ->first();

        if (!$conversation) {
            DB::beginTransaction();
            $conversation = Conversation::create([
                'type' => 'private',
                'user1_id' => $user1,
                'user2_id' => $user2,
            ]);
            $conversation->users()->attach([$user1, $user2]);
            DB::commit();
        }

        return response()->json(['conversation_id' => $conversation->id]);
    }

    // إحضار/إنشاء المحادثة العامة الافتراضية
    public function publicConversation(Request $request)
    {
        $conversation = Conversation::where('type', 'public')
            ->where('title', 'الدردشة العامة')
            ->first();

        if (!$conversation) {
            $uid = Auth::id() ?: User::query()->value('id');
            $conversation = Conversation::create([
                'type' => 'public',
                'title' => 'الدردشة العامة',
                'user1_id' => $uid,
                'user2_id' => $uid,
            ]);
        }

        $uid = Auth::id();
        if ($uid && !$conversation->users()->where('users.id', $uid)->exists()) {
            $conversation->users()->attach($uid);
        }

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
        ]);
    }

    // قائمة المستخدمين (بحث + pagination + المتصلون أولاً إن وُجد العمود)
    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $hasIsOnline = Schema::hasColumn('users', 'is_online');
        $hasLastActivity = Schema::hasColumn('users', 'last_activity');
        $hasLastSeenAt = Schema::hasColumn('users', 'last_seen_at');
        $activityWindowMinutes = (int) $request->query('activity_window', 5);

        // active visitor sessions subquery
        $vsSub = DB::table('visitor_sessions')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->where('last_activity', '>=', now()->subMinutes($activityWindowMinutes))
            ->groupBy('user_id');

        // Build online expression
        $onlineExprParts = [];
        if ($hasIsOnline) $onlineExprParts[] = 'users.is_online = 1';
        $onlineExprParts[] = 'vs.user_id IS NOT NULL';
        if ($hasLastActivity) $onlineExprParts[] = "users.last_activity >= '" . now()->subMinutes($activityWindowMinutes)->toDateTimeString() . "'";
        if ($hasLastSeenAt) $onlineExprParts[] = "users.last_seen_at >= '" . now()->subMinutes($activityWindowMinutes)->toDateTimeString() . "'";
        $onlineExpr = '(' . implode(' OR ', $onlineExprParts) . ')';

        $query = User::query()
            ->from('users')
            ->where('id', '!=', Auth::id())
            ->leftJoinSub($vsSub, 'vs', function($join){ $join->on('vs.user_id', '=', 'users.id'); })
            ->select([
                'users.id', 'users.name', 'users.email', 'users.profile_photo_path',
                DB::raw("CASE WHEN $onlineExpr THEN 1 ELSE 0 END as is_online"),
            ]);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        // Filter only online users if requested
        if ((string) $request->query('online', '') !== '') {
            $query->whereRaw($onlineExpr);
        }
        // online first then name
        $query->orderByDesc(DB::raw($onlineExpr));
        $query->orderBy('name');

        $paginator = $query->paginate($perPage);

        $users = collect($paginator->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_online' => (bool) ($user->is_online ?? false),
                'avatar' => $user->profile_photo_url ?? asset('assets/img/avatars/4.png'),
            ];
        });

        return response()->json([
            'users' => $users,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? ($paginator->currentPage() + 1) : null,
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'avatar'    => $user->avatar,
            'about'     => $user->about ?? '',
            'role'      => $user->role ?? '',
            'is_online' => (bool) ($user->is_online ?? false),
        ]);
    }

    public function unreadCounts(Request $request)
    {
        $userId = Auth::id();
        $current = $request->query('current');

        $conversationIds = DB::table('conversation_user')
            ->where('user_id', $userId)
            ->pluck('conversation_id');

        if ($conversationIds->isEmpty()) {
            return response()->json([]);
        }

        $rows = Message::query()
            ->selectRaw('conversation_id, COUNT(*) as cnt')
            ->whereIn('conversation_id', $conversationIds)
            ->where('is_chat', true)
            ->where('sender_id', '!=', $userId)
            ->groupBy('conversation_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->conversation_id] = (int) $row->cnt;
        }

        if ($current && isset($map[$current])) {
            $map[$current] = 0;
        }

        return response()->json($map);
    }

    // حظر مستخدم دردشة
    public function blockChat($id)
    {
        $blockerId = Auth::id();
        if ($blockerId == $id) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك حظر نفسك!']);
        }
        ChatBlock::firstOrCreate([
            'blocker_id' => $blockerId,
            'blocked_id' => (int) $id,
        ]);
        return response()->json(['success' => true, 'message' => 'تم حظر العضو من الدردشة.']);
    }

    public function unblockChat($id)
    {
        $blockerId = Auth::id();
        ChatBlock::where([
            'blocker_id' => $blockerId,
            'blocked_id' => (int) $id,
        ])->delete();
        return response()->json(['success' => true, 'message' => 'تم فك الحظر عن المستخدم.']);
    }

    public function checkBlockStatus($id)
    {
        $userId = Auth::id();
        $iBlocked = ChatBlock::where('blocker_id', $userId)->where('blocked_id', $id)->exists();
        $blockedMe = ChatBlock::where('blocker_id', $id)->where('blocked_id', $userId)->exists();
        return response()->json([
            'i_blocked' => $iBlocked,
            'blocked_me' => $blockedMe,
        ]);
    }

    // مسح المحادثة (مع حماية إضافية للدردشة العامة)
    public function clearChat($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $currentUser = Auth::user();

        if ($conversation->type === 'public') {
            $isAdmin = false;
            $isSuper = function ($name) {
                if (!is_string($name) || $name === '') return false;
                $n = strtolower($name);
                $n = preg_replace('/[^a-z0-9]+/', '_', $n);
                $n = trim($n, '_');
                return in_array($n, ['super_admin', 'superadmin']);
            };
            if ($currentUser) {
                try {
                    $dbg = [
                        'user_id' => $currentUser->id ?? null,
                        'role' => isset($currentUser->role) ? (is_string($currentUser->role) ? $currentUser->role : ($currentUser->role->name ?? null)) : null,
                        'role_id' => $currentUser->role_id ?? null,
                    ];
                    if (isset($currentUser->roles)) {
                        $rolesProp = $currentUser->roles;
                        if ($rolesProp instanceof \Illuminate\Support\Collection) {
                            $dbg['roles'] = $rolesProp->map(function ($r) { return is_string($r) ? $r : ($r->name ?? null); })->filter()->values();
                        } elseif (is_array($rolesProp)) {
                            $dbg['roles'] = collect($rolesProp)->map(function ($r) { return is_string($r) ? $r : ($r->name ?? null); })->filter()->values();
                        }
                    }
                    Log::info('clearChat public role debug (api)', $dbg);
                } catch (\Throwable $e) {}

                if (!$isAdmin && isset($currentUser->role)) {
                    $roleName = is_string($currentUser->role) ? $currentUser->role : ($currentUser->role->name ?? null);
                    if ($isSuper($roleName)) { $isAdmin = true; }
                }
                if (!$isAdmin && isset($currentUser->roles)) {
                    $rolesProp = $currentUser->roles;
                    if ($rolesProp instanceof \Illuminate\Support\Collection) {
                        foreach ($rolesProp as $r) { $rName = is_string($r) ? $r : ($r->name ?? null); if ($isSuper($rName)) { $isAdmin = true; break; } }
                    } elseif (is_array($rolesProp) || $rolesProp instanceof \Traversable) {
                        foreach ($rolesProp as $r) { $rName = is_string($r) ? $r : ($r->name ?? null); if ($isSuper($rName)) { $isAdmin = true; break; } }
                    }
                }
                if (!$isAdmin && isset($currentUser->role_id) && (int) $currentUser->role_id === 1) {
                    $isAdmin = true;
                }
            }
            if (!$isAdmin) {
                Log::warning('clearChat denied for public chat (api)', ['user_id' => $currentUser->id ?? null, 'conversation_id' => $conversationId]);
                return response()->json(['success' => false, 'message' => 'غير مسموح إلا للمشرف بمسح محادثة الدردشة العامة.'], 403);
            }
            $conversation->messages()->delete();
            return response()->json(['success' => true, 'message' => 'تم حذف جميع الرسائل في هذه المحادثة بنجاح.']);
        }

        if (!$conversation->users->contains($currentUser->id)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتنفيذ هذه العملية.'], 403);
        }

        $conversation->messages()->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف جميع الرسائل في هذه المحادثة بنجاح.']);
    }
}

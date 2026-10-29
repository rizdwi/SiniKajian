<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PushController extends Controller
{
    /**
     * Simpan subscription push dari browser.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $svc = new \App\Services\SupabaseUserService();
        if (!$svc->enabled()) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }

        // ID app_users yang stabil via email (bukan Auth::id() SQLite yang
        // berubah antar instance Vercel) agar subscription terhubung ke
        // bookmark & follow user yang sama. findOrCreate bisa melempar saat
        // Supabase tidak terjangkau — jangan biarkan jadi 500.
        $userId = null;
        try {
            $userId = Auth::check() ? ($svc->currentAppUserId() ?? Auth::id()) : null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Push subscribe userId gagal: ' . $e->getMessage());
            $userId = Auth::check() ? Auth::id() : null;
        }

        // Upsert ke Supabase push_subscriptions
        $svc->upsertPushSubscription([
            'user_id' => $userId,
            'endpoint' => $request->input('endpoint'),
            'p256dh' => $request->input('keys.p256dh'),
            'auth' => $request->input('keys.auth'),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Hapus subscription push.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string']);

        $svc = new \App\Services\SupabaseUserService();
        if ($svc->enabled()) {
            $svc->deletePushSubscription($request->input('endpoint'));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Kirim notif ke semua subscriber (dipanggil cron).
     * Dilindungi webhook secret.
     */
    public function send(Request $request): JsonResponse
    {
        $secret = config('app.sync_webhook_secret') ?: env('SYNC_WEBHOOK_SECRET');
        $provided = (string) $request->header('X-Sync-Secret', '');
        if (!$secret || !hash_equals($secret, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:200',
            'url' => 'nullable|string|max:500',
            'kajian_id' => 'nullable|integer',
            'masjid' => 'nullable|string|max:200',
            'ustadz' => 'nullable|string|max:200',
            'topic' => 'nullable|string|in:adzan,dzikir,puasa',
        ]);

        $svc = new \App\Services\SupabaseUserService();
        $subs = $svc->enabled() ? $svc->getPushSubscriptions() : [];

        // Jika kajian_id diberikan, hanya kirim ke user yang mem-bookmark kajian ini
        $kajianId = $request->input('kajian_id');
        if ($kajianId && $svc->enabled()) {
            $bookmarkedUserIds = $svc->getBookmarkUserIds((int) $kajianId);
            if (!empty($bookmarkedUserIds)) {
                $subs = array_values(array_filter($subs, function ($sub) use ($bookmarkedUserIds) {
                    return isset($sub['user_id']) && in_array((int) $sub['user_id'], $bookmarkedUserIds, true);
                }));
            } else {
                $subs = []; // tidak ada yang bookmark → tidak ada notif
            }
        }

        // Jika masjid diberikan, hanya kirim ke user yang mengikuti majelis itu
        $masjid = $request->input('masjid');
        if ($masjid && $svc->enabled()) {
            $followerUserIds = $svc->getMajelisFollowerUserIds((string) $masjid);
            if (!empty($followerUserIds)) {
                $subs = array_values(array_filter($subs, function ($sub) use ($followerUserIds) {
                    return isset($sub['user_id']) && in_array((int) $sub['user_id'], $followerUserIds, true);
                }));
            } else {
                $subs = []; // tidak ada pengikut → tidak ada notif
            }
        }

        // Jika ustadz diberikan, hanya kirim ke user yang mengikuti ustadz itu
        $ustadz = $request->input('ustadz');
        if ($ustadz && $svc->enabled()) {
            $followerUserIds = $svc->getUstadzFollowerUserIds((string) $ustadz);
            if (!empty($followerUserIds)) {
                $subs = array_values(array_filter($subs, function ($sub) use ($followerUserIds) {
                    return isset($sub['user_id']) && in_array((int) $sub['user_id'], $followerUserIds, true);
                }));
            } else {
                $subs = []; // tidak ada pengikut → tidak ada notif
            }
        }

        // Jika topik pengingat ibadah diberikan (adzan|dzikir|puasa),
        // hanya kirim ke user yang opt-in topik itu (Segmen No.2).
        $topic = $request->input('topic');
        if ($topic && $svc->enabled()) {
            $optInUserIds = $svc->getOptInUserIds((string) $topic);
            if (!empty($optInUserIds)) {
                $subs = array_values(array_filter($subs, function ($sub) use ($optInUserIds) {
                    return isset($sub['user_id']) && in_array((int) $sub['user_id'], $optInUserIds, true);
                }));
            } else {
                $subs = []; // tidak ada yang opt-in → tidak ada notif
            }
        }

        // Chunking (audit 2026-10-21, H4): broadcast besar tidak boleh
        // mengandalkan satu request sampai timeout. Pemanggil dapat
        // mengirim offset+chunk; respons membawa next_offset untuk
        // dilanjutkan pemanggil sampai null. Tanpa parameter, perilaku
        // lama dipertahankan (semua target dalam satu panggilan).
        $totalTargets = count($subs);
        $offset = max(0, (int) $request->input('offset', 0));
        $chunk = $request->has('chunk') ? min(100, max(1, (int) $request->input('chunk'))) : 1000;
        $subs = array_slice($subs, $offset, $chunk);
        $nextOffset = ($offset + count($subs)) < $totalTargets ? $offset + count($subs) : null;

        $sent = 0;
        $failed = 0;
        foreach ($subs as $sub) {
            try {
                $result = $this->sendWebPush($sub, $request->only(['title', 'body', 'url']));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('PushController send error: ' . $e->getMessage());
                $result = false;
            }
            if ($result === true) {
                $sent++;
            } else {
                $failed++;
                if ($this->lastPushService) {
                    \Illuminate\Support\Facades\Log::warning('PushController send gagal: stage='
                        . ($this->lastPushService->lastStage ?? '-')
                        . ' err=' . substr((string) ($this->lastPushService->lastError ?? ''), 0, 300)
                        . ' endpoint=' . substr($sub['endpoint'] ?? '', 0, 40));
                }
                // Hapus subscription HANYA bila server push menyatakan gone/invalid (410/404).
                // Jangan hapus pada error jaringan sementara agar subscription valid tidak hilang.
                if ($result === 410 || $result === 404) {
                    $svc->deletePushSubscription($sub['endpoint']);
                }
            }
        }

        return response()->json(['sent' => $sent, 'failed' => $failed, 'total' => count($subs), 'total_targets' => $totalTargets, 'next_offset' => $nextOffset]);
    }

    /**
     * Kirim satu web push menggunakan VAPID (implementasi manual, tanpa library eksternal).
     * Return: true jika sukses, int (HTTP status) jika gagal dengan status diketahui, false jika gagal tanpa status.
     */
    private ?\App\Services\ManualWebPushService $lastPushService = null;

    private function sendWebPush(array $sub, array $payload): bool|int
    {
        $this->lastPushService = new \App\Services\ManualWebPushService();
        return $this->lastPushService->send($sub, $payload);
    }
}

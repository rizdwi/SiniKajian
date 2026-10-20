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
        // bookmark & follow user yang sama.
        $userId = Auth::check() ? ($svc->currentAppUserId() ?? Auth::id()) : null;

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

        $sent = 0;
        $failed = 0;
        $debug = [];
        foreach ($subs as $sub) {
            try {
                $result = $this->sendWebPush($sub, $request->only(['title', 'body', 'url']));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('PushController send error: ' . $e->getMessage());
                $result = false;
                $debug[] = 'exception: ' . $e->getMessage();
            }
            if ($result === true) {
                $sent++;
            } else {
                $failed++;
                $stageInfo = '';
                if ($this->lastPushService) {
                    $stageInfo = ' stage=' . ($this->lastPushService->lastStage ?? '-')
                        . ' err=' . substr((string) ($this->lastPushService->lastError ?? ''), 0, 700);
                }
                $debug[] = 'result=' . var_export($result, true) . $stageInfo . ' endpoint=' . substr($sub['endpoint'] ?? '', 0, 40);
                // Hapus subscription HANYA bila server push menyatakan gone/invalid (410/404).
                // Jangan hapus pada error jaringan sementara agar subscription valid tidak hilang.
                if ($result === 410 || $result === 404) {
                    $svc->deletePushSubscription($sub['endpoint']);
                }
            }
        }

        $response = ['sent' => $sent, 'failed' => $failed, 'total' => count($subs)];
        if ($request->input('debug')) {
            $response['debug'] = $debug;
            $response['vapid_configured'] = !empty(config('services.vapid.public_key')) && !empty(config('services.vapid.private_key'));
            $response['vapid_pub_len'] = strlen((string) config('services.vapid.public_key'));
            $response['vapid_priv_len'] = strlen((string) config('services.vapid.private_key'));
        }
        return response()->json($response);
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

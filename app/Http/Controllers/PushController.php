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

        $userId = Auth::check() ? Auth::id() : null;

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
        ]);

        $svc = new \App\Services\SupabaseUserService();
        $subs = $svc->enabled() ? $svc->getPushSubscriptions() : [];

        $sent = 0;
        $failed = 0;
        foreach ($subs as $sub) {
            $result = $this->sendWebPush($sub, $request->only(['title', 'body', 'url']));
            if ($result === true) {
                $sent++;
            } else {
                $failed++;
                // Hapus subscription HANYA bila server push menyatakan gone/invalid (410/404).
                // Jangan hapus pada error jaringan sementara agar subscription valid tidak hilang.
                if ($result === 410 || $result === 404) {
                    $svc->deletePushSubscription($sub['endpoint']);
                }
            }
        }

        return response()->json(['sent' => $sent, 'failed' => $failed, 'total' => count($subs)]);
    }

    /**
     * Kirim satu web push menggunakan VAPID (implementasi manual, tanpa library eksternal).
     * Return: true jika sukses, int (HTTP status) jika gagal dengan status diketahui, false jika gagal tanpa status.
     */
    private function sendWebPush(array $sub, array $payload): bool|int
    {
        $svc = new \App\Services\ManualWebPushService();
        return $svc->send($sub, $payload);
    }
}

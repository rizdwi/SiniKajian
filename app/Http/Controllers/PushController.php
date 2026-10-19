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
        $secret = env('SYNC_WEBHOOK_SECRET');
        if (!$secret || $request->header('X-Sync-Secret') !== $secret) {
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
            if ($this->sendWebPush($sub, $request->only(['title', 'body', 'url']))) {
                $sent++;
            } else {
                $failed++;
                // Hapus subscription yang invalid (410 Gone)
                $svc->deletePushSubscription($sub['endpoint']);
            }
        }

        return response()->json(['sent' => $sent, 'failed' => $failed, 'total' => count($subs)]);
    }

    /**
     * Kirim satu web push menggunakan VAPID.
     * Memerlukan minishlink/web-push; jika belum ter-install, return false.
     */
    private function sendWebPush(array $sub, array $payload): bool
    {
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            \Illuminate\Support\Facades\Log::warning('WebPush library belum ter-install.');
            return false;
        }

        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject = config('services.vapid.subject');

        if (!$publicKey || !$privateKey) return false;

        try {
            $auth = [
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];

            $webPush = new \Minishlink\WebPush\WebPush($auth);

            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth'],
                ],
            ]);

            $report = $webPush->sendOneNotification(
                $subscription,
                json_encode($payload)
            );

            return $report->isSuccess();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('WebPush gagal: ' . $e->getMessage());
            return false;
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Box saran & masukan melayang (perintah user 2026-10-21).
 *
 * POST /api/feedback (stateless, untuk tamu maupun pengguna login).
 * Menulis ke tabel Supabase `feedback` (dibuat lewat SQL Editor —
 * database/supabase_feedback.sql). Bila tabel belum ada: 503 sopan,
 * TIDAK PERNAH mengganggu halaman pengunjung.
 */
class FeedbackController extends Controller
{
    private const CATEGORIES = ['Saran Fitur', 'Laporan Bug', 'Info Kajian', 'Lainnya'];

    public function store(Request $request): JsonResponse
    {
        // Honeypot anti-spam: field tak terlihat; bot yang mengisinya
        // menerima respons sukses palsu tanpa ada yang disimpan.
        if (trim((string) $request->input('website', '')) !== '') {
            return response()->json(['ok' => true], 201);
        }

        $message = trim(strip_tags((string) $request->input('message', '')));
        if (mb_strlen($message) < 3 || mb_strlen($message) > 1000) {
            return response()->json([
                'ok' => false,
                'error' => 'Pesan harus 3–1000 karakter.',
            ], 422);
        }

        $category = (string) $request->input('category', 'Lainnya');
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'Lainnya';
        }

        $name = trim(strip_tags((string) $request->input('name', '')));
        $contact = trim(strip_tags((string) $request->input('contact', '')));
        $page = strtok((string) $request->input('page', ''), '?#') ?: '';

        $url = config('services.supabase.url');
        $key = config('services.supabase.service_key');
        if (!$url || !$key) {
            return response()->json([
                'ok' => false,
                'error' => 'Feedback belum aktif. Coba lagi nanti ya.',
            ], 503);
        }

        try {
            $res = Http::withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal',
            ])->timeout(8)->post("{$url}/rest/v1/feedback", [
                'category' => $category,
                'name' => $name !== '' ? mb_substr($name, 0, 60) : null,
                'contact' => $contact !== '' ? mb_substr($contact, 0, 80) : null,
                'message' => $message,
                'page' => ($page !== '' && $page[0] === '/') ? mb_substr($page, 0, 200) : null,
            ]);
            if (!$res->successful()) {
                Log::warning('Feedback insert gagal: HTTP ' . $res->status());
                return response()->json([
                    'ok' => false,
                    'error' => 'Feedback belum aktif. Coba lagi nanti ya.',
                ], 503);
            }
        } catch (\Throwable $e) {
            Log::warning('Feedback insert error: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => 'Gagal mengirim. Coba lagi sebentar lagi ya.',
            ], 503);
        }

        return response()->json(['ok' => true], 201);
    }
}

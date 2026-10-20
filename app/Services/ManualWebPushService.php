<?php

namespace App\Services;

/**
 * Web Push sender tanpa dependency eksternal (hanya ext-openssl).
 * Mengikuti RFC 8291 (Message Encryption) + RFC 8292 (VAPID).
 *
 * Dibuat karena minishlink/web-push menyebabkan build error di Vercel.
 */
class ManualWebPushService
{
    /**
     * Kirim satu notifikasi push.
     * @return true jika sukses, int (HTTP status) jika gagal dengan status, false jika gagal tanpa status.
     */
    public function send(array $subscription, array $payload): bool|int
    {
        $vapidPublic = config('services.vapid.public_key');
        $vapidPrivate = config('services.vapid.private_key');
        $subject = config('services.vapid.subject', 'mailto:admin@majelisku.web.id');

        if (!$vapidPublic || !$vapidPrivate) {
            \Illuminate\Support\Facades\Log::warning('VAPID keys belum dikonfigurasi.');
            return false;
        }

        try {
            $endpoint = $subscription['endpoint'] ?? null;
            $p256dh = $subscription['p256dh'] ?? null;
            $auth = $subscription['auth'] ?? null;
            if (!$endpoint || !$p256dh || !$auth) {
                return false;
            }

            // 1. Enkripsi payload (aes128gcm)
            try {
                $encrypted = $this->encryptPayload(json_encode($payload), $p256dh, $auth);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WebPush enkripsi gagal: ' . $e->getMessage());
                return false;
            }
            if ($encrypted === null) return false;

            // 2. Buat VAPID JWT
            try {
                $urlParts = parse_url($endpoint);
                $audience = ($urlParts['scheme'] ?? 'https') . '://' . ($urlParts['host'] ?? '');
                $jwt = $this->createVapidJwt($audience, $subject, $vapidPublic, $vapidPrivate);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WebPush VAPID gagal: ' . $e->getMessage());
                return false;
            }
            if ($jwt === null) return false;

            // 3. Kirim via HTTP POST
            $headers = [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: 86400',
                'Authorization: vapid t=' . $jwt . ', k=' . $vapidPublic,
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $encrypted,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                \Illuminate\Support\Facades\Log::warning('WebPush curl error: ' . $curlErr);
                return false;
            }

            if ($status >= 200 && $status < 300) return true;
            return $status ?: false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ManualWebPush gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enkripsi payload per RFC 8291 (aes128gcm).
     */
    private function encryptPayload(string $plaintext, string $p256dhB64, string $authB64): ?string
    {
        $p256dh = $this->b64urlDecode($p256dhB64);
        $authSecret = $this->b64urlDecode($authB64);
        if ($p256dh === false || $authSecret === false) return null;

        // Generate ephemeral keypair (prime256v1)
        $ephemeral = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        if (!$ephemeral) return null;

        $details = openssl_pkey_get_details($ephemeral);
        $ephemeralPub = "\x04" . $details['ec']['x'] . $details['ec']['y']; // uncompressed 65 bytes

        // ECDH shared secret
        $sharedSecret = $this->ecdh($ephemeral, $p256dh);
        if ($sharedSecret === null) return null;

        // Salt 16 bytes
        $salt = random_bytes(16);

        // HKDF steps (RFC 8291)
        $prk = $this->hkdf($authSecret, $sharedSecret, 'Content-Encoding: auth' . "\x00", 32);

        // Context: "P-256" + 0x00 + len(peer pub) + peer pub + len(ephem pub) + ephem pub
        // (masing-masing len = 2 bytes big-endian)
        $context = "P-256\x00"
            . pack('n', strlen($p256dh)) . $p256dh
            . pack('n', strlen($ephemeralPub)) . $ephemeralPub;

        $cekInfo = 'Content-Encoding: aes128gcm' . "\x00" . $context;
        $nonceInfo = 'Content-Encoding: nonce' . "\x00" . $context;

        $cek = $this->hkdf($salt, $prk, $cekInfo, 16);
        $nonce = $this->hkdf($salt, $prk, $nonceInfo, 12);

        // Pad plaintext: 2-byte big-endian pad length (0) + data + delimiter 0x02
        $padded = "\x00\x00" . $plaintext . "\x02";

        $tag = '';
        $ciphertext = openssl_encrypt($padded, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false) return null;

        // Header: salt(16) + rs(4, =4096) + idlen(1) + keylen(1=65) + pubkey(65)
        return $salt . pack('N', 4096) . chr(strlen($ephemeralPub)) . $ephemeralPub . $ciphertext . $tag;
    }

    /**
     * ECDH: derive shared secret antara ephemeral private key dan peer public key.
     */
    private function ecdh($privateKey, string $peerPubUncompressed): ?string
    {
        // Gunakan openssl_pkey_derive (PHP 8+)
        if (!function_exists('openssl_pkey_derive')) return null;

        // Buat public key resource dari raw uncompressed point
        // Format: uncompressed point perlu dibungkus jadi PEM/DER
        $x = substr($peerPubUncompressed, 1, 32);
        $y = substr($peerPubUncompressed, 33, 32);

        // DER encode EC public key (prime256v1)
        $der = $this->derEncodeEcPublicKey($x, $y);
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $peerKey = openssl_pkey_get_public($pem);
        if (!$peerKey) return null;

        $secret = openssl_pkey_derive($peerKey, $privateKey, 32);
        return $secret === false ? null : $secret;
    }

    private function derEncodeEcPublicKey(string $x, string $y): string
    {
        // AlgorithmIdentifier: ecPublicKey + prime256v1
        $algId = "\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $bitString = "\x03\x42\x00\x04" . $x . $y;
        $seq = $algId . $bitString;
        return "\x30" . $this->derLen(strlen($seq)) . $seq;
    }

    private function derLen(int $len): string
    {
        if ($len < 128) return chr($len);
        $bytes = '';
        while ($len > 0) { $bytes = chr($len & 0xff) . $bytes; $len >>= 8; }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function hkdf(string $salt, string $ikm, string $info, int $len): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $okm = '';
        $t = '';
        $counter = 1;
        while (strlen($okm) < $len) {
            $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, true);
            $okm .= $t;
            $counter++;
        }
        return substr($okm, 0, $len);
    }

    /**
     * Buat VAPID JWT (ES256).
     */
    private function createVapidJwt(string $audience, string $subject, string $pubB64, string $privB64): ?string
    {
        $header = $this->b64urlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = $this->b64urlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200, // 12 jam
            'sub' => $subject,
        ]));
        $data = $header . '.' . $payload;

        // Private key: raw 32 bytes -> buat EC key
        $privRaw = $this->b64urlDecode($privB64);
        if ($privRaw === false || strlen($privRaw) !== 32) return null;

        // Derive public key dari private
        $keyPair = $this->ecKeypairFromPrivate($privRaw);
        if ($keyPair === null) return null;

        $sigDer = '';
        if (!openssl_sign($data, $sigDer, $keyPair, OPENSSL_ALGO_SHA256)) return null;

        // Convert DER -> raw R||S (64 bytes)
        $sigRaw = $this->derToRawEs256($sigDer);
        if ($sigRaw === null) return null;

        return $data . '.' . $this->b64urlEncode($sigRaw);
    }

    private function ecKeypairFromPrivate(string $privRaw): mixed
    {
        // Bangun PEM EC private key (SEC1) dari raw 32 bytes
        $der = "\x30\x77\x02\x01\x01\x04\x20" . $privRaw
            . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
        $key = openssl_pkey_get_private($pem);
        return $key === false ? null : $key;
    }

    private function derToRawEs256(string $der): ?string
    {
        // Parse DER sequence of two integers
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) return null;
        $pos += $this->derLenSize($der, $pos);
        if (ord($der[$pos++]) !== 0x02) return null;
        $rLen = $this->derReadLen($der, $pos, $p);
        $pos = $p;
        $r = substr($der, $pos, $rLen); $pos += $rLen;
        if (ord($der[$pos++]) !== 0x02) return null;
        $sLen = $this->derReadLen($der, $pos, $p);
        $pos = $p;
        $s = substr($der, $pos, $sLen);

        // Strip leading zeros, pad to 32 bytes
        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
        if (strlen($r) !== 32 || strlen($s) !== 32) return null;
        return $r . $s;
    }

    private function derLenSize(string $der, int $pos): int
    {
        $b = ord($der[$pos]);
        return ($b & 0x80) ? (1 + ($b & 0x7f)) : 1;
    }

    private function derReadLen(string $der, int $pos, &$next): int
    {
        $b = ord($der[$pos]);
        if (!($b & 0x80)) { $next = $pos + 1; return $b; }
        $n = $b & 0x7f;
        $len = 0;
        for ($i = 1; $i <= $n; $i++) $len = ($len << 8) | ord($der[$pos + $i]);
        $next = $pos + 1 + $n;
        return $len;
    }

    private function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $data): string|false
    {
        $padded = strtr($data, '-_', '+/');
        $pad = strlen($padded) % 4;
        if ($pad) $padded .= str_repeat('=', 4 - $pad);
        return base64_decode($padded, true);
    }
}

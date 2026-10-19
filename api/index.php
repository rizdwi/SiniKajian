<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// 1. Inisialisasi struktur direktori /tmp untuk Vercel Serverless
$tmp = sys_get_temp_dir();
$storage = $tmp . '/storage';
$dirs = [
    $storage . '/framework/views',
    $storage . '/framework/cache/data',
    $storage . '/framework/sessions',
    $storage . '/logs',
    $storage . '/app/public',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// 2. Salin SQLite database dengan data kajian & akun ke /tmp jika di Vercel (hanya jika target belum ada)
$sourceDb = __DIR__ . '/../database/database.sqlite';
$targetDb = $tmp . '/database.sqlite';

if (file_exists($sourceDb)) {
    if (!file_exists($targetDb)) {
        @copy($sourceDb, $targetDb);
    }
} elseif (!file_exists($targetDb)) {
    @touch($targetDb);
}

// Pastikan skema tabel users & kajians selalu mutakhir (self-healing migration)
if (file_exists($targetDb)) {
    try {
        $pdo = new PDO('sqlite:' . $targetDb);
        $userCols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!empty($userCols)) {
            if (!in_array('provider', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN provider VARCHAR(255) NULL");
            }
            if (!in_array('provider_id', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN provider_id VARCHAR(255) NULL");
            }
            if (!in_array('avatar', $userCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN avatar TEXT NULL");
            }
        }
        $kajianCols = $pdo->query("PRAGMA table_info(kajians)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!empty($kajianCols) && !in_array('event_datetime', $kajianCols)) {
            $pdo->exec("ALTER TABLE kajians ADD COLUMN event_datetime DATETIME NULL");
        }
    } catch (\Throwable $e) {
        // Abaikan jika tabel belum diinisialisasi
    }
}

// 3. Konfigurasi runtime environment overrides untuk Vercel Serverless
date_default_timezone_set('Asia/Jakarta');
putenv('APP_TIMEZONE=Asia/Jakarta');
$_ENV['APP_TIMEZONE'] = 'Asia/Jakarta';
$_SERVER['APP_TIMEZONE'] = 'Asia/Jakarta';

putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
putenv('APP_ENV=production');
$_ENV['APP_ENV'] = 'production';
$_SERVER['APP_ENV'] = 'production';

// Force cookie session driver (file tidak shared antar instance Vercel)
putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER'] = 'cookie';
$_SERVER['SESSION_DRIVER'] = 'cookie';
if (!getenv('APP_KEY') && !isset($_ENV['APP_KEY'])) {
    $fallbackKey = 'base64:eneFvNA7mfb/CrYt8SVrrRaHH+nRCD+cZ8pZDsnV72M=';
    putenv('APP_KEY=' . $fallbackKey);
    $_ENV['APP_KEY'] = $fallbackKey;
    $_SERVER['APP_KEY'] = $fallbackKey;
}

$viewCompiledPath = $storage . '/framework/views';
putenv('VIEW_COMPILED_PATH=' . $viewCompiledPath);
$_ENV['VIEW_COMPILED_PATH'] = $viewCompiledPath;
$_SERVER['VIEW_COMPILED_PATH'] = $viewCompiledPath;

$dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');
if ($dbConnection === 'sqlite' || empty(getenv('DB_CONNECTION'))) {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=' . $targetDb);
    $_ENV['DB_DATABASE'] = $targetDb;
    $_SERVER['DB_DATABASE'] = $targetDb;
}

$appDebug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'false');
$appName = getenv('APP_NAME') ?: ($_ENV['APP_NAME'] ?? 'Majelisku');
$appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'https://majelisku.web.id');
$mailMailer = getenv('MAIL_MAILER') ?: ($_ENV['MAIL_MAILER'] ?? 'resend');
$mailFrom = getenv('MAIL_FROM_ADDRESS') ?: ($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@majelisku.web.id');
$mailFromName = getenv('MAIL_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? 'Majelisku');
$resendKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? ($_SERVER['RESEND_API_KEY'] ?? ''));

$supabaseUrl = getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? ($_SERVER['SUPABASE_URL'] ?? 'https://hhcopgprjdjvgfcuqdyf.supabase.co'));
$supabaseKey = getenv('SUPABASE_SERVICE_KEY') ?: ($_ENV['SUPABASE_SERVICE_KEY'] ?? ($_SERVER['SUPABASE_SERVICE_KEY'] ?? (getenv('SUPABASE_KEY') ?: ($_ENV['SUPABASE_KEY'] ?? ''))));

putenv('APP_NAME=' . $appName);
putenv('APP_DEBUG=' . $appDebug);
putenv('APP_URL=' . $appUrl);
putenv('SESSION_DRIVER=cookie');
putenv('SESSION_LIFETIME=120');
putenv('SESSION_SECURE_COOKIE=true');
putenv('SESSION_SAME_SITE=lax');
putenv('CACHE_STORE=array');
putenv('MAIL_MAILER=' . $mailMailer);
putenv('MAIL_FROM_ADDRESS=' . $mailFrom);
putenv('MAIL_FROM_NAME=' . $mailFromName);
if (!empty($resendKey)) {
    putenv('RESEND_API_KEY=' . $resendKey);
}
if (!empty($supabaseUrl)) {
    putenv('SUPABASE_URL=' . $supabaseUrl);
}
if (!empty($supabaseKey)) {
    putenv('SUPABASE_SERVICE_KEY=' . $supabaseKey);
}

$_ENV['APP_NAME'] = $appName;
$_ENV['APP_DEBUG'] = $appDebug;
$_ENV['APP_URL'] = $appUrl;
$_ENV['SESSION_DRIVER'] = 'cookie';
$_ENV['SESSION_LIFETIME'] = '120';
$_ENV['SESSION_SECURE_COOKIE'] = 'true';
$_ENV['SESSION_SAME_SITE'] = 'lax';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['MAIL_MAILER'] = $mailMailer;
$_ENV['MAIL_FROM_ADDRESS'] = $mailFrom;
$_ENV['MAIL_FROM_NAME'] = $mailFromName;
if (!empty($resendKey)) {
    $_ENV['RESEND_API_KEY'] = $resendKey;
}
if (!empty($supabaseUrl)) {
    $_ENV['SUPABASE_URL'] = $supabaseUrl;
}
if (!empty($supabaseKey)) {
    $_ENV['SUPABASE_SERVICE_KEY'] = $supabaseKey;
}

$_SERVER['APP_NAME'] = $appName;
$_SERVER['APP_DEBUG'] = $appDebug;
$_SERVER['APP_URL'] = $appUrl;
$_SERVER['SESSION_DRIVER'] = 'cookie';
$_SERVER['SESSION_LIFETIME'] = '120';
$_SERVER['SESSION_SECURE_COOKIE'] = 'true';
$_SERVER['SESSION_SAME_SITE'] = 'lax';
$_SERVER['CACHE_STORE'] = 'array';
$_SERVER['MAIL_MAILER'] = $mailMailer;
$_SERVER['MAIL_FROM_ADDRESS'] = $mailFrom;
$_SERVER['MAIL_FROM_NAME'] = $mailFromName;
if (!empty($resendKey)) {
    $_SERVER['RESEND_API_KEY'] = $resendKey;
}
if (!empty($supabaseUrl)) {
    $_SERVER['SUPABASE_URL'] = $supabaseUrl;
}
if (!empty($supabaseKey)) {
    $_SERVER['SUPABASE_SERVICE_KEY'] = $supabaseKey;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Diagnostic log viewer - dilindungi token keamanan khusus admin
if (str_contains($_SERVER['REQUEST_URI'] ?? '', 'view-log')) {
    $adminLogKey = getenv('ADMIN_LOG_KEY') ?: ($_ENV['ADMIN_LOG_KEY'] ?? '');
    $providedToken = $_GET['key'] ?? ($_GET['token'] ?? '');
    if (empty($adminLogKey) || !hash_equals($adminLogKey, $providedToken)) {
        http_response_code(404);
        echo '404 Not Found';
        exit;
    }
    header('Content-Type: text/plain');
    $logFile = $storage . '/logs/laravel.log';
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    } else {
        echo "No log file found at: " . $logFile;
    }
    exit;
}

// 4. Delegasikan eksekusi request ke Front Controller Laravel 13
require __DIR__ . '/../public/index.php';

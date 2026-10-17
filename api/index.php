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

// 2. Salin SQLite database dengan data kajian & akun ke /tmp jika di Vercel
$sourceDb = __DIR__ . '/../database/database.sqlite';
$targetDb = $tmp . '/database.sqlite';

if (file_exists($sourceDb)) {
    if (!file_exists($targetDb) || filesize($targetDb) !== filesize($sourceDb) || @md5_file($sourceDb) !== @md5_file($targetDb)) {
        @copy($sourceDb, $targetDb);
    }
} elseif (!file_exists($targetDb)) {
    @touch($targetDb);
}

// 3. Konfigurasi runtime environment overrides untuk Vercel Serverless
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';

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

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $targetDb);
$_ENV['DB_DATABASE'] = $targetDb;
$_SERVER['DB_DATABASE'] = $targetDb;

putenv('APP_NAME=SiniKajian');
putenv('APP_DEBUG=true');
putenv('APP_URL=https://sinikajian.vercel.app');
putenv('SESSION_DRIVER=cookie');
putenv('SESSION_LIFETIME=120');
putenv('CACHE_STORE=array');

$_ENV['APP_NAME'] = 'SiniKajian';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['APP_URL'] = 'https://sinikajian.vercel.app';
$_ENV['SESSION_DRIVER'] = 'cookie';
$_ENV['SESSION_LIFETIME'] = '120';
$_ENV['CACHE_STORE'] = 'array';

$_SERVER['APP_NAME'] = 'SiniKajian';
$_SERVER['APP_DEBUG'] = 'true';
$_SERVER['APP_URL'] = 'https://sinikajian.vercel.app';
$_SERVER['SESSION_DRIVER'] = 'cookie';
$_SERVER['SESSION_LIFETIME'] = '120';
$_SERVER['CACHE_STORE'] = 'array';

// Diagnostic log viewer
if (str_contains($_SERVER['REQUEST_URI'] ?? '', 'view-log')) {
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

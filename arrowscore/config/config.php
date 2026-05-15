<?php
// Matikan error reporting untuk production (aktifkan jika debugging)
error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('Asia/Jakarta');

// Deteksi protokol (HTTP/HTTPS)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// Deteksi host (IP lokal, IP publik, atau domain)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Deteksi path folder aplikasi (misal: /arrowscore)
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Gabungkan menjadi BASE_URL
define('BASE_URL', $protocol . '://' . $host . $scriptDir);
define('SESSION_NAME', 'arrowscore_admin');
define('HASH_ALGO', PASSWORD_BCRYPT);
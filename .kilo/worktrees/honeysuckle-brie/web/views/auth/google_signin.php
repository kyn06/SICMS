<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';

function app_base_path() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(preg_replace('#/web/views/auth/google_signin\.php$#', '', $script), '/');
}

function google_fail($message) {
    $_SESSION['error'] = $message;
    header('Location: login.php');
    exit;
}

function google_redirect_uri() {
    $config = $GLOBALS['google_config'] ?? [];
    if (!empty($config['redirect_uri'])) {
        return $config['redirect_uri'];
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return $scheme . '://' . $host . $script;
}

function google_base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function google_http_post($url, array $params, $verifySsl = true) {
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => (bool) $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['curl_error' => $error];
    }

    return json_decode($body, true);
}

if (isset($_SESSION['email'])) {
    header('Location: ' . app_base_path() . '/index.php');
    exit;
}

$config = require __DIR__ . '/../../config/google_config.php';
$GLOBALS['google_config'] = $config;

$clientId     = trim((string) ($config['client_id'] ?? ''));
$clientSecret = trim((string) ($config['client_secret'] ?? ''));
$hostedDomain = trim((string) ($config['hosted_domain'] ?? ''));
$verifySsl    = (bool) ($config['verify_ssl'] ?? true);

if (($_GET['diag'] ?? '') === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Add this EXACT URL under 'Authorized redirect URIs' in Google Cloud Console:\n\n";
    echo google_redirect_uri() . "\n\n";
    echo "Client ID set:     " . ($clientId !== '' && !str_starts_with($clientId, 'YOUR_') ? 'yes' : 'NO - edit web/config/google_config.php') . "\n";
    echo "Client secret set: " . ($clientSecret !== '' && !str_starts_with($clientSecret, 'YOUR_') ? 'yes' : 'NO - edit web/config/google_config.php') . "\n";
    exit;
}

if ($clientId === '' || $clientSecret === '' || str_starts_with($clientId, 'YOUR_')) {
    google_fail('Google sign-in is not configured yet. Please set the OAuth credentials in web/config/google_config.php.');
}

if (!empty($_GET['error'])) {
    google_fail('Google sign-in was cancelled. You can log in with your email and password instead.');
}

if (!isset($_GET['code'])) {
    // Step 1: Send the user to Google's consent screen.
    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));

    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['google_oauth_nonce'] = $nonce;
    session_write_close();

    $params = [
        'client_id'     => $clientId,
        'redirect_uri'  => google_redirect_uri(),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'nonce'         => $nonce,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ];

    if ($hostedDomain !== '') {
        $params['hd'] = $hostedDomain;
    }

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}

// Step 2: Handle the redirect back from Google.
$state = (string) ($_GET['state'] ?? '');
if ($state === '' || empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
    google_fail('Invalid Google sign-in state. Please try again.');
}
unset($_SESSION['google_oauth_state']);

$nonce = (string) ($_SESSION['google_oauth_nonce'] ?? '');
unset($_SESSION['google_oauth_nonce']);

$tokens = google_http_post('https://oauth2.googleapis.com/token', [
    'code'          => (string) $_GET['code'],
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => google_redirect_uri(),
    'grant_type'    => 'authorization_code',
], $verifySsl);

if (empty($tokens['id_token'])) {
    $detail = $tokens['error_description'] ?? $tokens['curl_error'] ?? null;
    google_fail('Could not verify your Google account.' . ($detail ? ' (' . $detail . ')' : ' Please try again.'));
}

$parts = explode('.', (string) $tokens['id_token']);
if (count($parts) !== 3 || !($payload = json_decode(google_base64url_decode($parts[1]), true))) {
    google_fail('Could not read your Google account details. Please try again.');
}

// Validate the ID token claims (token received directly from Google over TLS).
$validIssuers = ['https://accounts.google.com', 'accounts.google.com'];

if (!in_array($payload['iss'] ?? '', $validIssuers, true)) {
    google_fail('Untrusted Google response. Please try again.');
}

if (!hash_equals($clientId, (string) ($payload['aud'] ?? ''))) {
    google_fail('Google sign-in is not configured for this application (audience mismatch).');
}

if ((int) ($payload['exp'] ?? 0) < time()) {
    google_fail('Your Google session expired. Please try signing in again.');
}

if ($nonce === '' || empty($payload['nonce']) || !hash_equals($nonce, (string) $payload['nonce'])) {
    google_fail('Invalid Google sign-in replay protection. Please try again.');
}

$email = strtolower(trim((string) ($payload['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    google_fail('Your Google account does not expose a valid email address.');
}

if (($payload['email_verified'] ?? false) !== true) {
    google_fail('Please verify your email address with Google first, then try again.');
}

if ($hostedDomain !== '' && strtolower((string) ($payload['hd'] ?? '')) !== strtolower($hostedDomain)) {
    google_fail('Only ' . $hostedDomain . ' accounts may use Google sign-in.');
}

$firstName = trim((string) ($payload['given_name'] ?? ''));
$lastName  = trim((string) ($payload['family_name'] ?? ''));

if ($firstName === '' || $lastName === '') {
    $fullName = trim((string) ($payload['name'] ?? $email));
    $nameParts = explode(' ', $fullName);
    $firstName = $firstName !== '' ? $firstName : array_shift($nameParts);
    $lastName  = $lastName !== '' ? $lastName : trim(implode(' ', $nameParts));
}

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);

$result = User::loginWithGoogle([
    'email'      => $email,
    'first_name' => mb_substr($firstName, 0, 100),
    'last_name'  => mb_substr($lastName !== '' ? $lastName : '-', 0, 100),
]);

if (!$result) {
    google_fail($_SESSION['error'] ?? 'Could not sign you in with Google. Please try again.');
}

header('Location: ' . app_base_path() . '/index.php');
exit;

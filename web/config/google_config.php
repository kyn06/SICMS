<?php

// Google OAuth 2.0 credentials for "Sign in with Google".
//
// Setup:
//   1. Open https://console.cloud.google.com/apis/credentials
//   2. Create a project (or select the CLSU SDRU project) and configure the
//      OAuth consent screen.
//   3. Create credentials -> OAuth client ID -> Web application.
//   4. Under "Authorized redirect URIs" add the exact URL of google_signin.php, e.g.:
//        http://localhost/SICMS/web/views/auth/google_signin.php
//        https://your-domain/SICMS/web/views/auth/google_signin.php
//   5. Copy the client ID and client secret below.

return [
    'client_id'     => '90868996166-po0cbi7a7n4mkckasot8vk1geg1r5vcs.apps.googleusercontent.com',
    'client_secret' => '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',

    // Leave empty to auto-detect from the current request URL.
    'redirect_uri'  => '',

    // Optionally restrict sign-in to an email domain, e.g. 'clsu.edu.ph'.
    // Leave empty to allow any Google account.
    'hosted_domain' => '',

    // Keep enabled in production. Set to false ONLY for local development
    // if cURL reports certificate errors (then also download cacert.pem and
    // set curl.cainfo in php.ini instead, preferably).
    'verify_ssl'    => true,
];

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

    // Restrict sign-in to CLSU2 email domain.
    'hosted_domain' => 'clsu2.edu.ph',

    // Keep enabled in production. Set to false ONLY for local development
    // if cURL reports certificate errors (then also download cacert.pem and
    // set curl.cainfo in php.ini instead, preferably).
    'verify_ssl'    => true,

    // -------------------------------------------------------------
    // Google Calendar (hearings) integration
    // -------------------------------------------------------------
    // Scope used to create/manage hearing events on the connected
    // account's calendar. Add it to the OAuth consent screen and enable
    // the Google Calendar API in the Cloud Console:
    //   https://console.cloud.google.com/apis/library/calendar.googleapis.com
    'calendar_scopes' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/gmail.send',

    // Leave empty to auto-detect. If set, it MUST exactly match the
    // "Authorized redirect URI" configured in Cloud Console, e.g.:
    //   https://your-domain/SICMS/web/views/auth/google_connect_calendar.php
    'calendar_redirect_uri' => '',

    // Default length in minutes of a generated calendar event.
    'event_duration_minutes' => 60,
];

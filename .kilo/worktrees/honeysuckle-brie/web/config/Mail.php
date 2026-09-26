<?php

return [
    'host' => getenv('DARIS_MAIL_HOST') ?: getenv('SICMS_MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => (int) (getenv('DARIS_MAIL_PORT') ?: getenv('SICMS_MAIL_PORT') ?: 587),
    'encryption' => getenv('DARIS_MAIL_ENCRYPTION') ?: getenv('SICMS_MAIL_ENCRYPTION') ?: 'tls',
    'username' => getenv('DARIS_MAIL_USERNAME') ?: getenv('SICMS_MAIL_USERNAME') ?: '',
    'password' => getenv('DARIS_MAIL_PASSWORD') ?: getenv('SICMS_MAIL_PASSWORD') ?: '',
    'from_email' => getenv('DARIS_MAIL_FROM') ?: getenv('SICMS_MAIL_FROM') ?: '',
    'from_name' => getenv('DARIS_MAIL_FROM_NAME') ?: 'DARIS - Discipline and Reformation Information System',
    'app_url' => rtrim(getenv('DARIS_APP_URL') ?: getenv('SICMS_APP_URL') ?: 'http://localhost/daris', '/'),
    'timeout_seconds' => (int) (getenv('DARIS_MAIL_TIMEOUT') ?: 4),
    'request_budget_seconds' => (int) (getenv('DARIS_MAIL_REQUEST_BUDGET') ?: 8),
];

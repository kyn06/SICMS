<?php

return [
    'host' => getenv('SICMS_MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => (int) (getenv('SICMS_MAIL_PORT') ?: 587),
    'encryption' => getenv('SICMS_MAIL_ENCRYPTION') ?: 'tls',
    'username' => getenv('SICMS_MAIL_USERNAME') ?: '',
    'password' => getenv('SICMS_MAIL_PASSWORD') ?: '',
    'from_email' => getenv('SICMS_MAIL_FROM') ?: '',
    'from_name' => getenv('SICMS_MAIL_FROM_NAME') ?: 'SICMS - Student Integrity Case Management System',
    'app_url' => rtrim(getenv('SICMS_APP_URL') ?: 'http://localhost/sicms', '/'),
];

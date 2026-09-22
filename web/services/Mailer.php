<?php

class Mailer {
    private static $config;

    private static function config() {
        if (self::$config !== null) {
            return self::$config;
        }

        $config = [];
        $configFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Mail.php';
        if (is_file($configFile)) {
            $config = require $configFile;
        }

        $localFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Mail.local.php';
        if (is_file($localFile)) {
            $local = require $localFile;
            if (is_array($local)) {
                $config = array_merge($config, $local);
            }
        }

        self::$config = $config;
        return self::$config;
    }

    /**
     * Build an absolute URL from the single configured SICMS application root.
     * Email links must not infer a base path from the currently executing view.
     */
    public static function applicationUrl($path = '') {
        $path = trim((string) $path);
        if ($path !== '' && preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $config = self::config();
        $baseUrl = rtrim((string) ($config['app_url'] ?? 'http://localhost/sicms'), '/');
        return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
    }

    public static function send($to, $subject, $htmlBody) {
        $to = trim((string) $to);
        $subject = trim((string) $subject);

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::log($to, $subject, false, 'Invalid recipient email address.');
            return false;
        }

        $config = self::config();
        $username = trim((string) ($config['username'] ?? ''));
        $password = (string) ($config['password'] ?? '');
        $host = trim((string) ($config['host'] ?? 'smtp.gmail.com'));
        $port = (int) ($config['port'] ?? 587);
        $encryption = strtolower(trim((string) ($config['encryption'] ?? 'tls')));
        $fromEmail = trim((string) ($config['from_email'] ?? $username));
        $fromName = trim((string) ($config['from_name'] ?? 'SICMS'));

        if ($username === '' || $password === '' || $fromEmail === '') {
            self::log($to, $subject, false, 'SMTP is not configured. Set SICMS_MAIL_USERNAME, SICMS_MAIL_PASSWORD, and SICMS_MAIL_FROM, or create web/config/Mail.local.php.');
            return false;
        }

        $appName = 'SICMS Student Integrity Case Management System';
        $body = '<!DOCTYPE html><html><body style="margin:0;padding:24px;font-family:Verdana,Arial,sans-serif;background:#f5f7f4;color:#172017">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #dce5da;border-radius:12px;padding:24px">'
            . '<div style="font-size:18px;font-weight:bold;color:#167a22;margin-bottom:14px">SICMS</div>'
            . $htmlBody
            . '<hr style="border:none;border-top:1px solid #edf3ec;margin:20px 0">'
            . '<div style="color:#71806e;font-size:12px">This is an automated message from ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '.</div>'
            . '</div></body></html>';

        $socket = null;
        try {
            $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client($target . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
            if (!$socket) {
                throw new RuntimeException('SMTP connection failed: ' . ($errstr ?: 'error ' . $errno));
            }
            stream_set_timeout($socket, 15);

            self::expect($socket, [220]);
            self::command($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoOk !== true) {
                    throw new RuntimeException('Could not enable TLS encryption.');
                }
                self::command($socket, 'EHLO localhost', [250]);
            }

            self::command($socket, 'AUTH LOGIN', [334]);
            self::command($socket, base64_encode($username), [334]);
            self::command($socket, base64_encode($password), [235]);
            self::command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $encodedFromName . ' <' . $fromEmail . '>',
                'To: <' . $to . '>',
                'Subject: ' . $encodedSubject,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'X-Mailer: SICMS',
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $message = preg_replace('/(?m)^\./', '..', str_replace(["\r\n", "\r"], "\n", $message));
            $message = str_replace("\n", "\r\n", $message);
            fwrite($socket, $message . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
            fclose($socket);

            self::log($to, $subject, true, null);
            return true;
        } catch (Throwable $exception) {
            if (is_resource($socket)) {
                @fwrite($socket, "QUIT\r\n");
                @fclose($socket);
            }
            self::log($to, $subject, false, $exception->getMessage());
            return false;
        }
    }

    private static function command($socket, $command, array $expectedCodes) {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $expectedCodes);
    }

    private static function expect($socket, array $expectedCodes) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr(trim($response), 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP server rejected the request (' . $code . '): ' . trim($response));
        }
        return $response;
    }

    private static function log($to, $subject, $delivered, $error = null) {
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'outbound_emails';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'delivered' => (bool) $delivered,
        ];
        if ($error !== null) {
            $entry['error'] = $error;
        }

        @file_put_contents(
            $logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log',
            json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public static function invitationEmailBody($fullName, $activationUrl, $caseNumber) {
        return '<p>Dear ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>A respondent account has been created for you in SICMS in connection with case '
            . '<strong>' . htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            . '<p>To activate your account and set your password, click the button below:</p>'
            . '<p><a href="' . htmlspecialchars($activationUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#1a9d00;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;display:inline-block">Activate My Account</a></p>'
            . '<p>You will need this one-time link to set your password and access the case assigned to you.</p>'
            . '<p style="color:#71806e;font-size:13px">If the button does not work, copy this link into your browser:<br>'
            . htmlspecialchars($activationUrl, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    public static function noticeEmailBody($greeting, $messageText, $actionUrl, $actionLabel) {
        return '<p>' . htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>' . nl2br(htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8')) . '</p>'
            . ($actionUrl
                ? '<p><a href="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#1a9d00;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;display:inline-block">' . htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') . '</a></p>'
                : '')
            . '<p style="color:#71806e;font-size:13px">Please log in to SICMS to view the update. Do not reply to this email.</p>';
    }

    /* External notice sent when a respondent is named on a case but no matching
     * SICMS account exists yet. The account is NOT auto-created; this merely
     * informs them that they are a respondent. */
    public static function respondentNoticeEmailBody($fullName, $caseNumber) {
        return '<p>Dear ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>You have been named as a <strong>respondent</strong> on case '
            . '<strong>' . htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') . '</strong> in the SICMS Student Integrity Case Management System.</p>'
            . '<p>Please watch your email for further instructions from the SDRU office regarding this case. You may be asked to file a counter-statement, provide additional information, or attend a hearing.</p>'
            . '<p style="color:#71806e;font-size:13px">This is an automated message from SICMS. Do not reply to this email.</p>';
    }
}

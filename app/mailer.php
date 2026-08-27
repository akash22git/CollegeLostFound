<?php

/**
 * Lightweight SMTP & Mail Helper
 * Supports direct SMTP (Gmail, Brevo, Mailtrap, Outlook) and fallback to mail()
 * Works out-of-the-box on localhost and shared hosting (InfinityFree, XAMPP, etc.)
 */

function sendApplicationMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $bodyHtml,
    string $bodyText,
    array $env
): bool {
    $fromEmail = $env['MAIL_FROM'] ?? 'no-reply@college.edu';
    $fromName = $env['MAIL_FROM_NAME'] ?? 'College Lost & Found';
    $smtpHost = $env['SMTP_HOST'] ?? '';
    $smtpPort = (int) ($env['SMTP_PORT'] ?? 587);
    $smtpUser = $env['SMTP_USER'] ?? '';
    $smtpPass = $env['SMTP_PASS'] ?? '';
    $smtpSecure = strtolower($env['SMTP_SECURE'] ?? 'tls');

    // If SMTP credentials are configured, send via SMTP socket
    if (!empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass)) {
        $sent = sendViaSmtp(
            $smtpHost,
            $smtpPort,
            $smtpUser,
            $smtpPass,
            $smtpSecure,
            $fromEmail,
            $fromName,
            $toEmail,
            $toName,
            $subject,
            $bodyHtml,
            $bodyText
        );

        if ($sent) {
            return true;
        }
    }

    // Fallback to PHP built-in mail() with suppressed warnings
    $headers = "From: \"{$fromName}\" <{$fromEmail}>\r\n"
        . "Reply-To: {$fromEmail}\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $bodyHtml, $headers);
}

function sendViaSmtp(
    string $host,
    int $port,
    string $username,
    string $password,
    string $secure,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $toName,
    string $subject,
    string $bodyHtml,
    string $bodyText
): bool {
    $timeout = 10;
    $targetHost = ($secure === 'ssl') ? 'ssl://' . $host : $host;

    $socket = @fsockopen($targetHost, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $read = static function () use ($socket): string {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    };

    $send = static function (string $cmd) use ($socket): void {
        fputs($socket, $cmd . "\r\n");
    };

    $initial = $read();
    if (!str_starts_with($initial, '220')) {
        fclose($socket);
        return false;
    }

    $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    $read();

    if ($secure === 'tls') {
        $send('STARTTLS');
        $tlsResponse = $read();
        if (!str_starts_with($tlsResponse, '220')) {
            fclose($socket);
            return false;
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }

        $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $read();
    }

    $send('AUTH LOGIN');
    $authResp = $read();
    if (!str_starts_with($authResp, '334')) {
        fclose($socket);
        return false;
    }

    $send(base64_encode($username));
    $userResp = $read();
    if (!str_starts_with($userResp, '334')) {
        fclose($socket);
        return false;
    }

    $send(base64_encode($password));
    $passResp = $read();
    if (!str_starts_with($passResp, '235')) {
        fclose($socket);
        return false;
    }

    $send("MAIL FROM:<{$fromEmail}>");
    $mailResp = $read();
    if (!str_starts_with($mailResp, '250')) {
        fclose($socket);
        return false;
    }

    $send("RCPT TO:<{$toEmail}>");
    $rcptResp = $read();
    if (!str_starts_with($rcptResp, '250')) {
        fclose($socket);
        return false;
    }

    $send('DATA');
    $dataResp = $read();
    if (!str_starts_with($dataResp, '354')) {
        fclose($socket);
        return false;
    }

    $boundary = md5((string) time());
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [
        "From: \"{$fromName}\" <{$fromEmail}>",
        "To: \"{$toName}\" <{$toEmail}>",
        "Subject: {$encodedSubject}",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        "Date: " . date('r'),
    ];

    $message = implode("\r\n", $headers) . "\r\n\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $message .= $bodyText . "\r\n\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $bodyHtml . "\r\n\r\n";
    $message .= "--{$boundary}--\r\n";
    $message .= ".";

    $send($message);
    $sendResp = $read();

    $send('QUIT');
    fclose($socket);

    return str_starts_with($sendResp, '250');
}

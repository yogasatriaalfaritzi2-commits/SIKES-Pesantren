<?php

namespace App\Core;

/**
 * Mailer SIKES - Pengirim Email Mandiri (SMTP Socket murni tanpa external library).
 * Mendukung Gmail SMTP (smtp.gmail.com port 465 / 587) dengan TLS/SSL.
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): array
    {
        $smtpHost = defined('SMTP_HOST') && SMTP_HOST ? SMTP_HOST : 'smtp.gmail.com';
        $smtpPort = defined('SMTP_PORT') && SMTP_PORT ? (int) SMTP_PORT : 587;
        $smtpUser = defined('SMTP_USER') && SMTP_USER ? SMTP_USER : '';
        $smtpPass = defined('SMTP_PASS') && SMTP_PASS ? SMTP_PASS : '';
        $fromName = defined('APP_NAME') ? APP_NAME : 'SIKES Pondok Pesantren';

        // Jika kredensial Gmail belum disetel di .env, kita kembalikan status butuh setup
        if (empty($smtpUser) || empty($smtpPass)) {
            return [
                'ok'      => false,
                'sent'    => false,
                'simulated' => true,
                'message' => 'Kredensial Gmail (SMTP_USER & SMTP_PASS) belum disetel di file .env.'
            ];
        }

        try {
            $isSsl = ($smtpPort === 465);
            $protocol = $isSsl ? 'ssl://' : 'tcp://';
            $timeout = 15;

            $socket = @fsockopen($protocol . $smtpHost, $smtpPort, $errno, $errstr, $timeout);
            if (!$socket) {
                return ['ok' => false, 'sent' => false, 'message' => "Gagal terhubung ke host SMTP ($errstr)"];
            }

            $response = fgets($socket, 515);

            // Handshake EHLO
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }

            // StartTLS jika port 587
            if ($smtpPort === 587) {
                fputs($socket, "STARTTLS\r\n");
                $response = fgets($socket, 515);
                if (substr($response, 0, 3) !== '220') {
                    fclose($socket);
                    return ['ok' => false, 'sent' => false, 'message' => 'STARTTLS gagal: ' . $response];
                }

                // Upgrade koneksi ke enkripsi TLS
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return ['ok' => false, 'sent' => false, 'message' => 'Enkripsi TLS gagal'];
                }

                // Re-EHLO setelah TLS
                fputs($socket, "EHLO " . gethostname() . "\r\n");
                while ($line = fgets($socket, 515)) {
                    if (substr($line, 3, 1) === ' ') break;
                }
            }

            // Login Autentikasi
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 515);

            fputs($socket, base64_encode($smtpUser) . "\r\n");
            $response = fgets($socket, 515);

            fputs($socket, base64_encode($smtpPass) . "\r\n");
            $response = fgets($socket, 515);

            if (substr($response, 0, 3) !== '235') {
                fclose($socket);
                return ['ok' => false, 'sent' => false, 'message' => 'Autentikasi SMTP Gmail ditolak. Periksa email/app password Anda.'];
            }

            // Pengirim & Penerima
            fputs($socket, "MAIL FROM: <$smtpUser>\r\n");
            $response = fgets($socket, 515);

            fputs($socket, "RCPT TO: <$toEmail>\r\n");
            $response = fgets($socket, 515);

            fputs($socket, "DATA\r\n");
            $response = fgets($socket, 515);

            // Header & Konten
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$smtpUser>\r\n";
            $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$toEmail>\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            $messageData = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
            fputs($socket, $messageData);
            $response = fgets($socket, 515);

            fputs($socket, "QUIT\r\n");
            fclose($socket);

            return ['ok' => true, 'sent' => true, 'message' => 'Email berhasil dikirim ke ' . $toEmail];
        } catch (\Throwable $e) {
            return ['ok' => false, 'sent' => false, 'message' => $e->getMessage()];
        }
    }
}

<?php
/**
 * Mail Helper
 * Sends transactional mail using the server's configured mail transport.
 */

class Mailer {
    public static function send($to, $subject, $htmlBody, $textBody = '', $fromEmail = MAIL_FROM_EMAIL, $fromName = MAIL_FROM_NAME) {
        if (empty($to) || empty($subject) || empty($htmlBody)) {
            return ['success' => false, 'message' => 'Missing mail parameters'];
        }

        $boundary = '=_'.md5((string)microtime(true));
        $fromHeader = self::formatAddress($fromEmail, $fromName);
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'From: ' . $fromHeader;
        $headers[] = 'Reply-To: ' . $fromHeader;
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        if ($textBody === '') {
            $textBody = trim(strip_tags($htmlBody));
        }

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $textBody . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n";
        $body .= "--{$boundary}--";

        $sent = @mail($to, self::encodeSubject($subject), $body, implode("\r\n", $headers));

        if (!$sent) {
            return ['success' => false, 'message' => 'Unable to send email using the server mail transport'];
        }

        return ['success' => true, 'message' => 'Email sent successfully'];
    }

    private static function formatAddress($email, $name) {
        $safeName = str_replace(["\r", "\n", '"'], '', (string)$name);
        $safeEmail = str_replace(["\r", "\n"], '', (string)$email);

        if ($safeName !== '') {
            return sprintf('"%s" <%s>', $safeName, $safeEmail);
        }

        return $safeEmail;
    }

    private static function encodeSubject($subject) {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($subject, 'UTF-8');
        }

        return $subject;
    }
}

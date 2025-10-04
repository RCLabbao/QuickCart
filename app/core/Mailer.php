<?php
namespace App\Core;

class Mailer
{
    /**
     * Send an HTML email using settings. If PHPMailer is available (via Composer), use SMTP when enabled.
     * Fallback to PHP mail() when SMTP is disabled or PHPMailer is not available.
     */
    public static function send(string $to, string $subject, string $html): bool
    {
        $settings = self::settings();
        $from = $settings['smtp_from_email'] ?? 'no-reply@localhost';
        $fromName = $settings['smtp_from_name'] ?? ($settings['store_name'] ?? 'QuickCart');
        $smtpEnabled = ($settings['smtp_enabled'] ?? '0') === '1';

        // If PHPMailer is available and SMTP is enabled, use it
        if ($smtpEnabled && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $settings['smtp_host'] ?? '';
                $mail->Port = (int)($settings['smtp_port'] ?? 587);
                $mail->SMTPAuth = !empty($settings['smtp_user']);
                if ($mail->SMTPAuth) {
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                }
                $secure = strtolower($settings['smtp_secure'] ?? 'tls');
                if (in_array($secure, ['tls','ssl'], true)) { $mail->SMTPSecure = $secure; }
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($from, $fromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $html;
                $mail->AltBody = strip_tags($html);
                return $mail->send();
            } catch (\Throwable $e) {
                error_log('Mailer SMTP error: '.$e->getMessage());
                // fall through to mail()
            }
        }

        // Fallback: use PHP mail() with basic headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: '. self::formatAddress($fromName, $from);
        $headers[] = 'Reply-To: '. self::formatAddress($fromName, $from);
        $headers[] = 'X-Mailer: QuickCart';
        return @mail($to, self::encodeSubject($subject), $html, implode("\r\n", $headers));
    }

    public static function renderTemplate(string $templateHtml, array $vars): string
    {
        $out = $templateHtml;
        foreach ($vars as $k=>$v) {
            $out = str_replace('{{'.$k.'}}', (string)$v, $out);
        }
        return $out;
    }

    private static function formatAddress(string $name, string $email): string
    {
        $name = trim($name);
        if ($name === '') { return $email; }
        return '"'.addslashes($name).'" <'.$email.'>';
    }

    private static function encodeSubject(string $subject): string
    {
        // Encode UTF-8 subject line per RFC 2047
        return '=?UTF-8?B?'.base64_encode($subject).'?=';
    }

    private static function settings(): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->query('SELECT `key`,`value` FROM settings');
            $res = [];
            foreach ($stmt->fetchAll() as $row) { $res[$row['key']] = $row['value']; }
            return $res;
        } catch (\Throwable $e) {
            return [];
        }
    }
}


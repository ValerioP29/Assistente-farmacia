<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class WhatsappService
{
    public static function resolveBaseUrl(int $pharmaId = null): string
    {
        $base = defined('WHATSAPP_BASE_URL') ? WHATSAPP_BASE_URL : 'https://waservice.jungleteam.it';
        return rtrim($base, '/');
    }

    public static function service(string $path): string
    {
        $base = self::resolveBaseUrl();
        $normalizedPath = '/' . ltrim($path, '/');
        return $base . $normalizedPath;
    }

    public static function linkForPharma(int $pharmaId, string $message = ''): string
    {
        $pharma = db_fetch_one("SELECT phone_number FROM jta_pharmas WHERE id = ? AND status != 'deleted'", [$pharmaId]);

        if (!$pharma || empty($pharma['phone_number'])) {
            throw new InvalidArgumentException('Numero WhatsApp non configurato per la farmacia');
        }

        $normalized = preg_replace('/\D+/', '', $pharma['phone_number']);
        if (!$normalized) {
            throw new InvalidArgumentException('Numero WhatsApp non configurato per la farmacia');
        }

        $text = trim($message ?? '');
        $encodedMessage = $text === '' ? '' : rawurlencode($text);
        $link = "https://wa.me/{$normalized}";

        if ($encodedMessage !== '') {
            $link .= '?text=' . $encodedMessage;
        }

        return $link;
    }
}

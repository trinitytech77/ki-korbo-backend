<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS to a Bangladeshi phone number.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        $driver = config('services.sms.driver', env('SMS_DRIVER', 'log'));

        // Format phone number to standard 880 format if necessary
        $formattedPhone = $this->formatBangladeshiPhone($phone);

        Log::info("Sending SMS via [{$driver}] to {$formattedPhone}: {$message}");

        switch ($driver) {
            case 'greenweb':
                return $this->sendViaGreenweb($formattedPhone, $message);

            case 'bulksmsbd':
                return $this->sendViaBulkSmsBd($formattedPhone, $message);

            case 'sslwireless':
                return $this->sendViaSslWireless($formattedPhone, $message);

            case 'twilio':
                return $this->sendViaTwilio($formattedPhone, $message);

            case 'log':
            default:
                // Development driver: Logs to storage/logs/laravel.log
                Log::info("[DEV MOCK SMS] To: {$formattedPhone} | Message: {$message}");
                return true;
        }
    }

    /**
     * Format phone number to standard format (e.g., 88018XXXXXXXX).
     */
    protected function formatBangladeshiPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '01')) {
            $phone = '88' . $phone;
        }

        return $phone;
    }

    /**
     * Greenweb SMS gateway integration.
     */
    protected function sendViaGreenweb(string $phone, string $message): bool
    {
        $token = env('GREENWEB_TOKEN');
        if (!$token) {
            Log::warning('Greenweb token not set.');
            return false;
        }

        $response = Http::get('http://api.greenweb.com.bd/api.php', [
            'token' => $token,
            'to' => $phone,
            'message' => $message,
        ]);

        return $response->successful();
    }

    /**
     * BulkSMSBD SMS gateway integration.
     */
    protected function sendViaBulkSmsBd(string $phone, string $message): bool
    {
        $apiKey = env('BULKSMSBD_API_KEY');
        $senderId = env('BULKSMSBD_SENDER_ID', '880961761xxxx');

        if (!$apiKey) {
            Log::warning('BulkSMSBD API key not set.');
            return false;
        }

        $response = Http::get('http://bulksmsbd.net/api/smsapi', [
            'api_key' => $apiKey,
            'type' => 'text',
            'number' => $phone,
            'senderid' => $senderId,
            'message' => $message,
        ]);

        return $response->successful();
    }

    /**
     * SSL Wireless SMS gateway integration.
     */
    protected function sendViaSslWireless(string $phone, string $message): bool
    {
        $apiToken = env('SSL_SMS_API_TOKEN');
        $sid = env('SSL_SMS_SID');
        $domain = env('SSL_SMS_DOMAIN');

        if (!$apiToken || !$sid) {
            Log::warning('SSL Wireless credentials not set.');
            return false;
        }

        $response = Http::post("{$domain}/api/v3/send-sms", [
            'api_token' => $apiToken,
            'sid' => $sid,
            'msisdn' => $phone,
            'sms' => $message,
            'csms_id' => uniqid(),
        ]);

        return $response->successful();
    }

    /**
     * Twilio SMS integration.
     */
    protected function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_FROM');

        if (!$sid || !$token || !$from) {
            Log::warning('Twilio credentials not set.');
            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => '+' . $phone,
                'From' => $from,
                'Body' => $message,
            ]);

        return $response->successful();
    }
}

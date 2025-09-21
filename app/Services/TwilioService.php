<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendSms($to, $message)
    {
        return $this->client->messages->create(
            $to,
            [
                // Use one or the other:
                // 'from' => config('services.twilio.from'),
                'messagingServiceSid' => config('services.twilio.messaging_service_sid'),
                'body' => $message,
            ]
        );
    }
}

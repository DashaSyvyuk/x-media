<?php

namespace App\Utils;

use Exception;
use GuzzleHttp\Client;

class TurboSms
{
    /**
     * @throws Exception
     */
    public static function send(string $to, string $text)
    {
        try {
            $client = new Client(['base_uri' => $_SERVER['TURBO_SMS_ENDPOINT']]);

            $result = $client->request('GET', 'message/send.json', [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $_SERVER['TURBO_SMS_TOKEN'])
                ],
                'query' => [
                    'recipients' => [
                        $to
                    ],
                   'sms' => [
                       'sender' => $_SERVER['TURBO_SMS_SENDER'],
                       'text' => $text
                   ]
                ]
            ]);
        } catch(Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MicrosoftGraphMailer
{
    public function send(string $to, string $subject, string $body): void
    {
        $tenant = config('services.microsoft_graph.tenant_id');
        $clientId = config('services.microsoft_graph.client_id');
        $clientSecret = config('services.microsoft_graph.client_secret');
        $sender = config('services.microsoft_graph.mail_from');

        if (!$tenant || !$clientId || !$clientSecret || !$sender) {
            throw new RuntimeException('Microsoft Graph mail configuration is incomplete.');
        }

        $tokenResponse = Http::asForm()
            ->timeout(20)
            ->post(
                "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            )
            ->throw();

        $accessToken = $tokenResponse->json('access_token');

        if (!$accessToken) {
            throw new RuntimeException('Microsoft Graph did not return an access token.');
        }

        Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30)
            ->post(
                'https://graph.microsoft.com/v1.0/users/' . rawurlencode($sender) . '/sendMail',
                [
                    'message' => [
                        'subject' => $subject,
                        'body' => [
                            'contentType' => 'Text',
                            'content' => $body,
                        ],
                        'toRecipients' => [
                            ['emailAddress' => ['address' => $to]],
                        ],
                    ],
                    'saveToSentItems' => false,
                ]
            )
            ->throw();
    }
}

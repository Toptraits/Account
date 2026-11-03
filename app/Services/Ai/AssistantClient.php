<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * One place that talks to the school AI service (POST /api/assistant).
 *
 * The service rules: only "user"/"assistant" messages (NO "system" role),
 * 1–12 messages, and the last one must be a user message of at most 2,000 characters.
 * So we put the instructions + data + question together in ONE user message and cut it to fit.
 */
class AssistantClient
{
    public const MAX_CHARS = 2000;

    public function url(): string
    {
        return rtrim((string) config('services.accountant_api.url', 'http://host.docker.internal:3001'), '/');
    }

    /**
     * @return string  the AI reply text
     *
     * @throws \Illuminate\Http\Client\ConnectionException  when the service cannot be reached / times out
     * @throws \RuntimeException  when the service answers with an error
     */
    public function ask(string $instructions, string $question, string $context = '', int $timeout = 25): string
    {
        return $this->send(self::build($instructions, $question, $context), $timeout);
    }

    /** Sends one ready-made message (cut to 2,000 characters). */
    public function send(string $message, int $timeout = 25): string
    {
        $response = Http::baseUrl($this->url())
            ->acceptJson()->connectTimeout(5)->timeout($timeout)
            ->post('/api/assistant', ['messages' => [
                ['role' => 'user', 'content' => mb_substr(trim($message), 0, self::MAX_CHARS)],
            ]]);

        if (! $response->successful()) {
            $error = (string) ($response->json('error') ?? $response->body());
            throw new \RuntimeException("AI service error {$response->status()}: " . mb_substr($error, 0, 300));
        }

        return trim((string) ($response->json('reply') ?? $response->json('answer') ?? $response->json('message') ?? ''));
    }

    /** Instructions + (trimmed) context + question, always <= 2,000 characters. The context is cut first. */
    public static function build(string $instructions, string $question, string $context = ''): string
    {
        $instructions = trim($instructions);
        $question = mb_substr(trim($question), 0, 600);
        $tail = "\n\nQuestion: {$question}";

        if ($context !== '') {
            $room = self::MAX_CHARS - mb_strlen($instructions) - mb_strlen($tail) - 20;
            $context = $room > 100 ? "\n\nData:\n" . mb_substr(trim($context), 0, $room) : '';
        }

        return mb_substr($instructions . $context . $tail, 0, self::MAX_CHARS);
    }
}

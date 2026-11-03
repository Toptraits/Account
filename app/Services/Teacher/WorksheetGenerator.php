<?php

namespace App\Services\Teacher;

use App\Services\Ai\AssistantClient;

/**
 * Writes a 3-level worksheet (Support / Core / Challenge) with an answer key using the
 * school's AI service (the same /api/assistant that Copilot uses).
 * If the AI is offline or replies badly, it falls back to the built-in templates.
 */
class WorksheetGenerator
{
    public const LEVELS = ['Support', 'Core', 'Challenge'];

    /**
     * @param  array<string,int>  $groupSizes  students per level, e.g. ['Support'=>1,'Core'=>4,'Challenge'=>2]
     * @param  array<string,list<string>>  $fallback  template questions per level
     * @return array{source:string, questions:array<string,list<string>>, answers:array<string,list<string>>, note:string}
     */
    public function generate(string $subject, string $topic, int $grade, int $perLevel, array $groupSizes, array $fallback): array
    {
        $ai = new AssistantClient();
        $url = $ai->url();
        $timeout = (int) config('services.accountant_api.worksheet_timeout', 150);
        $reason = '';

        try {
            // ONE user message only — the AI service rejects "system" messages.
            $reply = $ai->send($this->prompt($subject, $topic, $grade, $perLevel, $groupSizes), $timeout);
            $found = self::extract($reply);

            if (array_sum(array_map('count', $found)) > 0) {
                // Use the AI questions; any level the AI missed is filled from the templates.
                $questions = $answers = [];
                $missed = [];
                foreach (self::LEVELS as $level) {
                    $pairs = array_slice($found[$level], 0, $perLevel);
                    if ($pairs === []) {
                        $missed[] = $level;
                        $questions[$level] = $fallback[$level] ?? [];
                        $answers[$level] = array_fill(0, count($questions[$level]), '');
                    } else {
                        $questions[$level] = array_column($pairs, 'q');
                        $answers[$level] = array_column($pairs, 'a');
                    }
                }
                $note = 'Written by AI for Class ' . $grade . ' · check every question and answer before use.';
                if ($missed) {
                    $note .= ' The AI did not finish ' . implode(', ', $missed) . ', so template questions are shown there.';
                }

                return ['source' => 'ai', 'questions' => $questions, 'answers' => $answers, 'note' => $note];
            }
            $reason = 'The AI replied, but no questions could be read from the reply.';
            logger()->warning('Worksheet AI reply could not be parsed', ['reply' => mb_substr($reply, 0, 2000)]);
        } catch (\RuntimeException $e) {
            $reason = 'The AI service refused the request (details are in storage/logs/laravel.log).';
            report($e);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $reason = str_contains(mb_strtolower($e->getMessage()), 'timed out')
                ? "The AI took longer than {$timeout} seconds. Try again, or use fewer questions per level."
                : "Cannot connect to the AI service at {$url}. Check that it is running and that ACCOUNTANT_API_URL in .env is correct.";
            report($e);
        } catch (\Throwable $e) {
            $reason = 'Unexpected error while contacting the AI service (details are in storage/logs/laravel.log).';
            report($e);
        }

        return [
            'source' => 'template',
            'questions' => $fallback,
            'answers' => array_map(fn ($qs) => array_fill(0, count($qs), ''), $fallback),
            'note' => $reason . ' Template questions were used instead — edit them or try again.',
        ];
    }

    public function prompt(string $subject, string $topic, int $grade, int $perLevel, array $groupSizes): string
    {
        $sizes = implode(', ', array_map(fn ($l) => "{$l}: " . ($groupSizes[$l] ?? 0) . ' students', self::LEVELS));

        return <<<TXT
        You are a CBSE school teacher. Write a practice worksheet for Class {$grade} {$subject}, topic "{$topic}".
        Write exactly {$perLevel} questions for each level:
        SUPPORT = easy, one step, small numbers.
        CORE = normal textbook level, include one word problem.
        CHALLENGE = multi-step or "explain why".
        Each question must be on one line, complete, with all numbers or facts needed. Each answer: at most 12 words.
        Use EXACTLY this format and nothing else:
        SUPPORT
        Q: question
        A: answer
        CORE
        Q: question
        A: answer
        CHALLENGE
        Q: question
        A: answer
        TXT;
    }

    /**
     * Tolerant reader for small AI models. Reads question/answer pairs from
     *  - the line format  "SUPPORT / Q: ... / A: ..."  (what we ask for), or
     *  - broken or cut-off JSON  ("q":"...","a":"..."),
     * and puts each pair under the last level heading seen before it.
     *
     * @return array<string, list<array{q:string,a:string}>>
     */
    public static function extract(string $reply): array
    {
        $out = array_fill_keys(self::LEVELS, []);
        $reply = str_replace(["\r\n", "\r"], "\n", $reply);

        // Where each level starts: a heading line ("SUPPORT", "**Core:**", "## Challenge level") or a JSON key ("Support":).
        preg_match_all('/(?:^|\n)[ \t#*\-]*(support|core|challenge)(?: level)?[ \t*]*:?[ \t*]*(?=\n)|"(support|core|challenge)"\s*:/i', $reply, $heads, PREG_OFFSET_CAPTURE);
        $marks = [];
        foreach ($heads[0] as $i => $h) {
            $name = $heads[1][$i][0] !== '' ? $heads[1][$i][0] : $heads[2][$i][0];
            $marks[] = [$h[1], ucfirst(strtolower($name))];
        }
        $levelAt = function (int $pos) use ($marks): string {
            $level = 'Support'; // text before any heading counts as Support
            foreach ($marks as [$at, $name]) {
                if ($at <= $pos) {
                    $level = $name;
                }
            }

            return $level;
        };

        $pairs = [];
        // JSON-like pairs (string escapes handled by json_decode)
        if (preg_match_all('/"(?:q|question)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"\s*,\s*"(?:a|answer)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/i', $reply, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $i => $full) {
                $pairs[] = [$full[1], json_decode('"' . $m[1][$i][0] . '"') ?? $m[1][$i][0], json_decode('"' . $m[2][$i][0] . '"') ?? $m[2][$i][0]];
            }
        }
        // Line format: "Q: ..." followed by "A: ..." (also "Q1.", "1. Q:", "Answer:")
        if (preg_match_all('/(?:^|\n)[ \t*\-]*(?:\d+[.)]\s*)?(?:Q|Question)\s*\d*\s*[:.)]\s*(.+?)\s*\n[ \t*\-]*(?:A|Ans|Answer)\s*\d*\s*[:.)]\s*(.+?)\s*(?=\n|$)/i', $reply, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $i => $full) {
                $pairs[] = [$full[1], $m[1][$i][0], $m[2][$i][0]];
            }
        }

        usort($pairs, fn ($x, $y) => $x[0] <=> $y[0]);
        $seen = [];
        foreach ($pairs as [$pos, $q, $a]) {
            $q = trim(preg_replace('/\s+/', ' ', (string) $q), " *\t");
            $a = trim(preg_replace('/\s+/', ' ', (string) $a), " *\t");
            $key = mb_strtolower($q);
            if (mb_strlen($q) < 8 || $q === '...' || preg_match('/^question\b|[{}]/i', $q) || isset($seen[$key])) {
                continue; // too short, placeholder, broken JSON or duplicate
            }
            $seen[$key] = true;
            $out[$levelAt($pos)][] = ['q' => mb_substr($q, 0, 600), 'a' => mb_substr($a, 0, 400)];
        }

        return $out;
    }

    /**
     * Turns the AI reply into questions + answers. Accepts JSON wrapped in ``` fences or extra text.
     * Returns null if the reply is unusable, so the caller can fall back.
     */
    public static function parse(string $reply, int $perLevel = 3): ?array
    {
        $start = strpos($reply, '{');
        $end = strrpos($reply, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $data = json_decode(substr($reply, $start, $end - $start + 1), true);
        if (! is_array($data)) {
            return null;
        }

        $questions = $answers = [];
        foreach (self::LEVELS as $level) {
            $items = $data[$level] ?? $data[strtolower($level)] ?? null;
            if (! is_array($items)) {
                return null;
            }
            foreach (array_slice($items, 0, max($perLevel, 1) + 2) as $item) {
                $q = is_array($item) ? trim((string) ($item['q'] ?? $item['question'] ?? '')) : trim((string) $item);
                $a = is_array($item) ? trim((string) ($item['a'] ?? $item['answer'] ?? '')) : '';
                if (mb_strlen($q) >= 8 && $q !== '...' && ! preg_match('/[{}]/', $q)) {
                    $questions[$level][] = mb_substr($q, 0, 600);
                    $answers[$level][] = mb_substr($a, 0, 400);
                }
            }
            if (count($questions[$level] ?? []) < 2) {
                return null; // too few usable questions → use the templates instead
            }
        }

        return ['questions' => $questions, 'answers' => $answers];
    }
}

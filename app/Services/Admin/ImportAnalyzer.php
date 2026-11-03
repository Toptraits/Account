<?php

namespace App\Services\Admin;

/**
 * Reads a pasted student list (CSV / Excel copy-paste), guesses which column is which,
 * checks every row and suggests fixes — BEFORE anything is imported.
 *
 * Works on real input: paste any sheet with headers like "Student Name, Std, Div, Mobile, DOB".
 */
class ImportAnalyzer
{
    /** Our field => header words that usually mean it. */
    public const FIELDS = [
        'admission_no' => ['admission no', 'admission number', 'adm no', 'admn no', 'admission', 'roll no', 'student id', 'id'],
        'name' => ['student name', 'name of student', 'full name', 'name', 'student'],
        'class' => ['class', 'std', 'standard', 'grade'],
        'section' => ['section', 'sec', 'div', 'division'],
        'dob' => ['date of birth', 'dob', 'birth date', 'd.o.b'],
        'gender' => ['gender', 'sex'],
        'parent' => ['father name', 'father', 'parent name', 'parent', 'guardian', 'mother name'],
        'phone' => ['parent phone', 'mobile', 'phone', 'contact', 'mobile no', 'phone number', 'whatsapp'],
        'email' => ['email', 'e-mail', 'mail'],
        'address' => ['address', 'locality', 'area'],
    ];

    public const SAMPLE = "Adm No,Student Name,Std,Div,DOB,Gender,Father Name,Mobile,Email\n"
        . "A2401,aarav sharma,6,a,12/03/2015,M,Ravi Sharma,9876543210,ravi.sharma@example.com\n"
        . "A2402,Diya  Nair,6,A,2015-07-21,F,Suresh Nair,+91 98450 12345,suresh.nair@example\n"
        . "A2403,Kabir Khan,7,B,05-11-2014,M,Imran Khan,98450123,imran@example.com\n"
        . "A2404,Meera Iyer,8a,,19/09/2013,F,Kiran Iyer,9845012399,\n"
        . "A2405,aarav sharma,6,A,12/03/2015,M,Ravi Sharma,9876543210,ravi.sharma@example.com\n"
        . "A2406,Tara Rao,12,A,01/01/2016,F,Manoj Rao,7012345678,manoj.rao@example.com\n"
        . "A2407,Ishaan Gupta,9,B,30/02/2011,M,Anil Gupta,9988776655,anil.g@example.com";

    /** @param  list<array{name:string,class:string}>  $existing  students already in the school */
    public function analyse(string $text, array $existing = [], ?\DateTimeInterface $today = null): array
    {
        $today = $today ?? new \DateTimeImmutable();
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return ['ok' => false, 'message' => 'Paste your student list (with a header row) first.'];
        }
        $lines = array_values(array_filter(explode("\n", $text), fn ($l) => trim($l) !== ''));
        $delim = $this->delimiter($lines[0]);
        $header = str_getcsv($lines[0], $delim, '"', '\\');
        $rows = array_map(fn ($l) => str_getcsv($l, $delim, '"', '\\'), array_slice($lines, 1));
        if (! $rows) {
            return ['ok' => false, 'message' => 'Only a header row was found. Add the student rows below it.'];
        }

        // 1. Column mapping with a confidence for each guess.
        $mapping = [];
        $used = [];
        foreach ($header as $i => $h) {
            [$field, $conf] = $this->guess($h, array_column($rows, $i));
            if ($field && ! isset($used[$field])) {
                $used[$field] = $i;
                $mapping[] = ['column' => trim($h), 'field' => $field, 'confidence' => $conf];
            } else {
                $mapping[] = ['column' => trim($h), 'field' => null, 'confidence' => 0];
            }
        }

        // 2. Check every row.
        $existingKeys = array_flip(array_map(fn ($s) => mb_strtolower(trim($s['name'])) . '|' . $s['class'], $existing));
        $seen = [];
        $checked = [];
        $fixable = 0;
        foreach ($rows as $n => $cells) {
            $get = fn ($f) => isset($used[$f]) ? trim((string) ($cells[$used[$f]] ?? '')) : '';
            $problems = [];
            $fixes = [];

            $name = $get('name');
            $cleanName = $this->titleName($name);
            if ($name === '') {
                $problems[] = 'Name is empty';
            } elseif ($cleanName !== $name) {
                $fixes['name'] = $cleanName;
            }

            [$class, $section] = $this->classSection($get('class'), $get('section'));
            if ($class === null) {
                $problems[] = 'Class “' . $get('class') . '” does not exist (school has 6–9)';
            } elseif ($section === null) {
                $problems[] = 'Section is missing';
            } elseif ($get('class') . ($get('section') ? '-' . $get('section') : '') !== "{$class}-{$section}") {
                $fixes['class'] = "{$class}-{$section}";
            }

            $phone = $get('phone');
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $digits = substr($digits, 2);
            }
            if ($phone === '') {
                $problems[] = 'Parent phone is missing';
            } elseif (! preg_match('/^[6-9]\d{9}$/', $digits)) {
                $problems[] = "Phone “{$phone}” is not a valid 10-digit mobile";
            } elseif ($phone !== $digits) {
                $fixes['phone'] = $digits;
            }

            $dobRaw = $get('dob');
            if ($dobRaw !== '') {
                $dob = $this->date($dobRaw);
                if (! $dob) {
                    $problems[] = "Date of birth “{$dobRaw}” is not a real date";
                } else {
                    if ($dob->format('d/m/Y') !== $dobRaw) {
                        $fixes['dob'] = $dob->format('d/m/Y');
                    }
                    $age = (int) $dob->diff($today)->y;
                    if ($class !== null && abs($age - ($class + 5)) > 2) {
                        $problems[] = "Age {$age} looks wrong for Class {$class}";
                    }
                }
            }

            $email = $get('email');
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $problems[] = "Email “{$email}” is incomplete";
            }

            $key = mb_strtolower($cleanName) . '|' . ($class !== null && $section !== null ? "{$class}-{$section}" : '');
            $duplicate = null;
            if (isset($seen[$key])) {
                $duplicate = 'Same as row ' . $seen[$key];
            } elseif (isset($existingKeys[$key])) {
                $duplicate = 'Already a student in ' . "{$class}-{$section}";
            }
            $seen[$key] ??= $n + 2;

            $status = $duplicate ? 'duplicate' : ($problems ? 'error' : ($fixes ? 'fix' : 'ok'));
            $fixable += $status === 'fix' ? 1 : 0;
            $checked[] = [
                'line' => $n + 2,
                'name' => $cleanName ?: '—',
                'class' => $class !== null ? $class . '-' . ($section ?? '?') : $get('class'),
                'phone' => $fixes['phone'] ?? $phone,
                'status' => $status,
                'problems' => $duplicate ? [$duplicate] : $problems,
                'fixes' => $fixes,
            ];
        }

        $count = fn ($s) => count(array_filter($checked, fn ($r) => $r['status'] === $s));
        $missingFields = array_values(array_diff(['name', 'class', 'phone'], array_keys($used)));

        return [
            'ok' => true,
            'delimiter' => $delim === "\t" ? 'tab' : $delim,
            'mapping' => $mapping,
            'rows' => $checked,
            'total' => count($checked),
            'ready' => $count('ok') + $count('fix'),
            'auto_fix' => $fixable,
            'errors' => $count('error'),
            'duplicates' => $count('duplicate'),
            'missing_fields' => $missingFields,
            'confidence' => $mapping ? (int) round(array_sum(array_column(array_filter($mapping, fn ($m) => $m['field']), 'confidence')) / max(1, count(array_filter($mapping, fn ($m) => $m['field'])))) : 0,
        ];
    }

    /** Applies every safe automatic fix and removes repeated rows of the same child. Returns the corrected text. */
    public function fix(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        $lines = array_values(array_filter(explode("\n", $text), fn ($l) => trim($l) !== ''));
        if (count($lines) < 2) {
            return $text;
        }
        $delim = $this->delimiter($lines[0]);
        $header = str_getcsv($lines[0], $delim, '"', '\\');
        $rows = array_map(fn ($l) => str_getcsv($l, $delim, '"', '\\'), array_slice($lines, 1));
        $used = [];
        foreach ($header as $i => $h) {
            [$field] = $this->guess($h, array_column($rows, $i));
            if ($field && ! isset($used[$field])) {
                $used[$field] = $i;
            }
        }

        $out = [$this->csvLine($header, $delim)];
        $seen = [];
        foreach ($rows as $cells) {
            if (isset($used['name'])) {
                $cells[$used['name']] = $this->titleName($cells[$used['name']] ?? '');
            }
            if (isset($used['class'])) {
                [$c, $s] = $this->classSection($cells[$used['class']] ?? '', isset($used['section']) ? ($cells[$used['section']] ?? '') : '');
                if ($c !== null) {
                    $cells[$used['class']] = (string) $c;
                    if (isset($used['section']) && $s !== null) {
                        $cells[$used['section']] = $s;
                    }
                }
            }
            if (isset($used['phone'])) {
                $d = preg_replace('/\D/', '', (string) ($cells[$used['phone']] ?? ''));
                if (strlen($d) === 12 && str_starts_with($d, '91')) {
                    $d = substr($d, 2);
                }
                if (preg_match('/^[6-9]\d{9}$/', $d)) {
                    $cells[$used['phone']] = $d;
                }
            }
            if (isset($used['dob']) && ($dt = $this->date((string) ($cells[$used['dob']] ?? '')))) {
                $cells[$used['dob']] = $dt->format('d/m/Y');
            }
            // Same child entered twice: same name + class + section + DOB + phone (admission no. / email may differ).
            $identity = array_filter(['name', 'class', 'section', 'dob', 'phone'], fn ($f) => isset($used[$f]));
            $k = count($identity) >= 3
                ? mb_strtolower(implode('|', array_map(fn ($f) => trim((string) ($cells[$used[$f]] ?? '')), $identity)))
                : mb_strtolower(implode('|', array_map('trim', $cells)));
            if (isset($seen[$k])) {
                continue; // duplicate of an earlier row → keep the first one
            }
            $seen[$k] = true;
            $out[] = $this->csvLine($cells, $delim);
        }

        return implode("\n", $out);
    }

    // ---------------------------------------------------------------- helpers

    private function delimiter(string $line): string
    {
        $counts = [',' => substr_count($line, ','), "\t" => substr_count($line, "\t"), ';' => substr_count($line, ';'), '|' => substr_count($line, '|')];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /** @return array{0:?string,1:int} field + confidence 0–100 (header words, then the values themselves) */
    private function guess(string $header, array $values): array
    {
        $h = mb_strtolower(trim(preg_replace('/[_\.]+/', ' ', $header)));
        $best = [null, 0];
        foreach (self::FIELDS as $field => $words) {
            foreach ($words as $w) {
                $score = $h === $w ? 98 : (str_contains($h, $w) ? 85 - max(0, mb_strlen($h) - mb_strlen($w)) : 0);
                if ($score > $best[1]) {
                    $best = [$field, $score];
                }
            }
        }
        if ($best[1] >= 60) {
            return $best;
        }
        // Header is unclear — look at the values.
        $vals = array_filter(array_map(fn ($v) => trim((string) $v), $values));
        if ($vals === []) {
            return $best;
        }
        $share = fn (string $re) => count(array_filter($vals, fn ($v) => preg_match($re, $v))) / count($vals);
        return match (true) {
            $share('/^(\+?91[\s-]?)?[6-9][\d\s-]{8,12}$/') > 0.6 => ['phone', 70],
            $share('/@/') > 0.6 => ['email', 70],
            $share('/^\d{1,4}[\/\-.]\d{1,2}[\/\-.]\d{1,4}$/') > 0.6 => ['dob', 65],
            $share('/^(m|f|male|female|boy|girl)$/i') > 0.6 => ['gender', 70],
            default => $best,
        };
    }

    private function titleName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name === '' ? '' : mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
    }

    /** "8a", "VIII", "8 - A", "Class 8" + "a" → [8, 'A'] */
    private function classSection(string $class, string $section): array
    {
        $raw = strtoupper(trim($class));
        $roman = ['VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9];
        $sec = strtoupper(trim($section)) ?: null;
        if (preg_match('/^(?:CLASS|STD)?\s*(VIII|VII|VI|IX|\d{1,2})\s*[-\s]?\s*([A-Z])?$/', $raw, $m)) {
            $num = $roman[$m[1]] ?? (int) $m[1];
            $sec = $sec ?? ($m[2] ?? null);

            return [$num >= 6 && $num <= 9 ? $num : null, $sec !== null && in_array($sec, ['A', 'B'], true) ? $sec : ($sec === null ? null : $sec)];
        }

        return [null, $sec];
    }

    private function date(string $v): ?\DateTimeImmutable
    {
        $v = trim($v);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'd/m/y'] as $f) {
            $d = \DateTimeImmutable::createFromFormat('!' . $f, $v);
            if ($d && $d->format($f) === $v) {
                return $d;
            }
        }

        return null;
    }

    private function csvLine(array $cells, string $delim): string
    {
        return implode($delim, array_map(fn ($c) => str_contains((string) $c, $delim) ? '"' . str_replace('"', '""', (string) $c) . '"' : (string) $c, $cells));
    }
}

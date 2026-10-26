<?php

namespace App\Services\Lyrics;

class LyricsNormalizer
{
    /**
     * Normalize lyrics raw string into unified structured format.
     *
     * @return array{type: string, lines: array<int, array{start_ms: int|null, end_ms: int|null, text: string, words?: array}>}
     */
    public function normalize(?string $raw, ?string $suggestedType = null): array
    {
        if (empty($raw)) {
            return [
                'type'  => 'plain',
                'lines' => [],
            ];
        }

        $raw = trim($raw);

        // Check if word-synced or syllable-synced
        if ($suggestedType === 'word_synced' || preg_match('/<\d{2}:\d{2}[\.:]\d{2,3}>/', $raw)) {
            $parsed = $this->parseWordSynced($raw);
            if (!empty($parsed)) {
                return [
                    'type'  => 'word_synced',
                    'lines' => $parsed,
                ];
            }
        }

        // Check if line-synced LRC: [mm:ss.xx]
        if ($suggestedType === 'line_synced' || preg_match('/\[\d{2}:\d{2}[\.:]\d{2,3}\]/', $raw)) {
            $parsed = $this->parseLrc($raw);
            if (!empty($parsed)) {
                return [
                    'type'  => 'line_synced',
                    'lines' => $parsed,
                ];
            }
        }

        // Fallback to plain text
        return [
            'type'  => 'plain',
            'lines' => $this->parsePlain($raw),
        ];
    }

    /**
     * Parse LRC format: [01:23.45] Some lyrics text
     *
     * @return array<int, array{start_ms: int, end_ms: int, text: string}>
     */
    public function parseLrc(string $lrc): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $lrc);
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Match all timestamp tags in line (e.g. [01:23.45])
            if (preg_match_all('/\[(\d{2}):(\d{2})(?:[\.:](\d{2,3}))?\]/', $line, $matches, PREG_SET_ORDER)) {
                $text = trim(preg_replace('/\[\d{2}:\d{2}(?:[\.:]\d{2,3})?\]/', '', $line));

                foreach ($matches as $match) {
                    $min = (int) $match[1];
                    $sec = (int) $match[2];
                    $msPart = $match[3] ?? '0';

                    // Normalize 2 digits (centiseconds) to milliseconds or 3 digits
                    if (strlen($msPart) === 2) {
                        $ms = ((int) $msPart) * 10;
                    } else {
                        $ms = (int) str_pad($msPart, 3, '0');
                    }

                    $startMs = ($min * 60 * 1000) + ($sec * 1000) + $ms;

                    $parsed[] = [
                        'start_ms' => $startMs,
                        'end_ms'   => null,
                        'text'     => $text,
                    ];
                }
            }
        }

        // Sort by start_ms
        usort($parsed, fn($a, $b) => $a['start_ms'] <=> $b['start_ms']);

        // Compute end_ms from next line's start_ms
        $count = count($parsed);
        for ($i = 0; $i < $count; $i++) {
            if ($i + 1 < $count) {
                $parsed[$i]['end_ms'] = $parsed[$i + 1]['start_ms'];
            } else {
                // For last line, default +4000ms duration
                $parsed[$i]['end_ms'] = $parsed[$i]['start_ms'] + 4000;
            }
        }

        return $parsed;
    }

    /**
     * Parse word-synced lyrics: e.g. [00:10.00] <00:10.00> word1 <00:10.50> word2
     */
    public function parseWordSynced(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Extract line start time if present: [00:10.00]
            $lineStartMs = 0;
            if (preg_match('/^\[(\d{2}):(\d{2})(?:[\.:](\d{2,3}))?\]/', $line, $m)) {
                $lineStartMs = $this->timeToMs($m[1], $m[2], $m[3] ?? '0');
                $line = trim(substr($line, strlen($m[0])));
            }

            // Extract word tokens: <00:10.00>word
            if (preg_match_all('/<(\d{2}):(\d{2})(?:[\.:](\d{2,3}))?>\s*([^<]+)/', $line, $wordMatches, PREG_SET_ORDER)) {
                $words = [];
                $fullText = '';

                foreach ($wordMatches as $wm) {
                    $wStart = $this->timeToMs($wm[1], $wm[2], $wm[3] ?? '0');
                    $wText = trim($wm[4]);
                    $fullText .= ($fullText === '' ? '' : ' ') . $wText;

                    $words[] = [
                        'start_ms' => $wStart,
                        'end_ms'   => $wStart + 500, // interim
                        'text'     => $wText,
                    ];
                }

                // Adjust word end_ms
                $wCount = count($words);
                for ($k = 0; $k < $wCount; $k++) {
                    if ($k + 1 < $wCount) {
                        $words[$k]['end_ms'] = $words[$k + 1]['start_ms'];
                    }
                }

                $firstWordStart = !empty($words) ? $words[0]['start_ms'] : $lineStartMs;
                $lastWordEnd = !empty($words) ? end($words)['end_ms'] : $lineStartMs + 3000;

                $result[] = [
                    'start_ms' => $firstWordStart,
                    'end_ms'   => $lastWordEnd,
                    'text'     => $fullText,
                    'words'    => $words,
                ];
            }
        }

        return $result;
    }

    /**
     * Parse plain text into array of lines without timestamps.
     */
    public function parsePlain(string $text): array
    {
        $rawLines = preg_split('/\r\n|\r|\n/', $text);
        $lines = [];

        foreach ($rawLines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $lines[] = [
                    'start_ms' => null,
                    'end_ms'   => null,
                    'text'     => $line,
                ];
            }
        }

        return $lines;
    }

    protected function timeToMs(string $min, string $sec, string $msPart): int
    {
        $m = (int) $min;
        $s = (int) $sec;

        if (strlen($msPart) === 2) {
            $ms = ((int) $msPart) * 10;
        } else {
            $ms = (int) str_pad($msPart, 3, '0');
        }

        return ($m * 60 * 1000) + ($s * 1000) + $ms;
    }
}

<?php

namespace App\Services\Lyrics;

class LyricsMatcher
{
    protected float $minScore;
    protected float $titleWeight;
    protected float $artistWeight;
    protected float $durationWeight;
    protected float $albumWeight;
    protected int $durationToleranceSeconds;

    public function __construct()
    {
        if (function_exists('app') && app()->bound('config')) {
            $this->minScore = (float) config('dhunzo.lyrics.matcher.min_score', 0.70);
            $weights = config('dhunzo.lyrics.matcher.weights', []);
            $this->titleWeight = (float) ($weights['title'] ?? 0.40);
            $this->artistWeight = (float) ($weights['artist'] ?? 0.30);
            $this->durationWeight = (float) ($weights['duration'] ?? 0.20);
            $this->albumWeight = (float) ($weights['album'] ?? 0.10);
            $this->durationToleranceSeconds = (int) config('dhunzo.lyrics.matcher.duration_tolerance_seconds', 3);
        } else {
            $this->minScore = 0.70;
            $this->titleWeight = 0.40;
            $this->artistWeight = 0.30;
            $this->durationWeight = 0.20;
            $this->albumWeight = 0.10;
            $this->durationToleranceSeconds = 3;
        }
    }

    /**
     * Compute matching score between query track info and candidate lyrics track info.
     *
     * @param string $queryTitle
     * @param string|null $queryArtist
     * @param string|null $queryAlbum
     * @param int|null $queryDuration In seconds
     * @param string $candTitle
     * @param string|null $candArtist
     * @param string|null $candAlbum
     * @param int|null $candDuration In seconds
     * @return float Score between 0.0 and 1.0
     */
    public function score(
        string $queryTitle,
        ?string $queryArtist = null,
        ?string $queryAlbum = null,
        ?int $queryDuration = null,
        string $candTitle = '',
        ?string $candArtist = null,
        ?string $candAlbum = null,
        ?int $candDuration = null
    ): float {
        $cleanQTitle = $this->normalizeString($queryTitle);
        $cleanCTitle = $this->normalizeString($candTitle);
        $baseQTitle = $this->extractBaseTitle($queryTitle);
        $baseCTitle = $this->extractBaseTitle($candTitle);

        if (empty($cleanQTitle) || empty($cleanCTitle)) {
            return 0.0;
        }

        // Title Similarity: evaluate both normalized full string and canonical base title
        $simFull = $this->calculateStringSimilarity($cleanQTitle, $cleanCTitle);
        $simBase = (!empty($baseQTitle) && !empty($baseCTitle))
            ? $this->calculateStringSimilarity($baseQTitle, $baseCTitle)
            : 0.0;
        $titleSim = max($simFull, $simBase);

        // Reject immediately if title match is very weak
        if ($titleSim < 0.45) {
            return 0.0;
        }

        // Artist Similarity
        $artistSim = 1.0;
        if (!empty($queryArtist) && !empty($candArtist)) {
            $cleanQArtist = $this->normalizeString($queryArtist);
            $cleanCArtist = $this->normalizeString($candArtist);
            $artistSim = $this->calculateArtistSimilarity($cleanQArtist, $cleanCArtist);

            // If artist specified but strongly conflicts
            if ($artistSim < 0.30) {
                return 0.0;
            }
        } elseif (!empty($queryArtist) && empty($candArtist)) {
            $artistSim = 0.6; // neutral penalty
        }

        // Duration Similarity
        $durationSim = 1.0;
        if ($queryDuration !== null && $candDuration !== null && $queryDuration > 0 && $candDuration > 0) {
            $diff = abs($queryDuration - $candDuration);
            if ($diff <= $this->durationToleranceSeconds) {
                $durationSim = 1.0;
            } elseif ($diff <= 6) {
                $durationSim = 0.7;
            } elseif ($diff <= 12) {
                $durationSim = 0.3;
            } else {
                $durationSim = 0.0;
            }
        } elseif ($queryDuration !== null && ($candDuration === null || $candDuration <= 0)) {
            $durationSim = 0.7; // Candidate didn't provide duration, neutral
        }

        // Album Similarity
        $albumSim = 1.0;
        if (!empty($queryAlbum) && !empty($candAlbum)) {
            $cleanQAlbum = $this->normalizeString($queryAlbum);
            $cleanCAlbum = $this->normalizeString($candAlbum);
            $albumSim = $this->calculateStringSimilarity($cleanQAlbum, $cleanCAlbum);
        } elseif (!empty($queryAlbum) && empty($candAlbum)) {
            $albumSim = 0.5;
        }

        // Weighted total
        $total = ($titleSim * $this->titleWeight)
            + ($artistSim * $this->artistWeight)
            + ($durationSim * $this->durationWeight)
            + ($albumSim * $this->albumWeight);

        return round(min(1.0, max(0.0, $total)), 4);
    }

    /**
     * Check if score meets minimum acceptance threshold.
     */
    public function isMatch(float $score): bool
    {
        return $score >= $this->minScore;
    }

    /**
     * Extract canonical base title by stripping parenthesized metadata and trailing hyphen subtitles.
     */
    public function extractBaseTitle(string $input): string
    {
        // Decode HTML entities
        $str = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip everything in parentheses, brackets, or braces
        $str = preg_replace('/\s*[\(\[\{].*?[\)\]\}]\s*/u', ' ', $str);

        // If hyphen exists, take part before hyphen (e.g. "Song - Subtitle")
        if (str_contains($str, ' - ')) {
            $str = explode(' - ', $str, 2)[0];
        }

        // Strip generic feat/ft
        $str = preg_replace('/\b(feat\.?|ft\.?)\s+.+$/i', '', $str);

        // Strip punctuation & normalize
        $str = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $str);
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $str)));
    }

    /**
     * Clean and normalize track or artist string for fair comparison.
     */
    public function normalizeString(string $input): string
    {
        // Decode HTML entities
        $str = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove all parenthesized or bracketed expressions
        $str = preg_replace('/\s*[\(\[\{].*?[\)\]\}]\s*/u', ' ', $str);

        // Remove generic feat/ft additions
        $str = preg_replace('/\b(feat\.?|ft\.?)\s+.+$/i', '', $str);

        // Remove special punctuation
        $str = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $str);

        // Normalize whitespace and lowercase
        $str = mb_strtolower(trim(preg_replace('/\s+/', ' ', $str)));

        return $str;
    }

    /**
     * Compute string similarity between 0.0 and 1.0.
     */
    protected function calculateStringSimilarity(string $s1, string $s2): float
    {
        if ($s1 === $s2) {
            return 1.0;
        }

        // Check if one contains the other
        if (str_contains($s1, $s2) || str_contains($s2, $s1)) {
            $shorter = min(mb_strlen($s1), mb_strlen($s2));
            $longer = max(mb_strlen($s1), mb_strlen($s2));
            return $longer > 0 ? min(1.0, ($shorter / $longer) * 0.9 + 0.1) : 0.0;
        }

        // Use similar_text
        similar_text($s1, $s2, $percent);
        return $percent / 100.0;
    }

    /**
     * Calculate artist similarity, supporting multiple artists or comma-separated names.
     */
    protected function calculateArtistSimilarity(string $qArtist, string $cArtist): float
    {
        if ($qArtist === $cArtist) {
            return 1.0;
        }

        // Split artist lists
        $qTokens = preg_split('/[\s,\/&]+/', $qArtist);
        $cTokens = preg_split('/[\s,\/&]+/', $cArtist);

        $qTokens = array_filter(array_map('trim', $qTokens));
        $cTokens = array_filter(array_map('trim', $cTokens));

        $intersection = array_intersect($qTokens, $cTokens);
        if (!empty($intersection)) {
            return min(1.0, count($intersection) / max(1, count($qTokens)));
        }

        return $this->calculateStringSimilarity($qArtist, $cArtist);
    }
}

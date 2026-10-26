<?php

namespace Tests\Unit\Lyrics;

use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use Tests\TestCase;

class LyricsMatcherTest extends TestCase
{
    protected LyricsMatcher $matcher;
    protected LyricsNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new LyricsMatcher();
        $this->normalizer = new LyricsNormalizer();
    }

    public function test_exact_match_yields_high_score(): void
    {
        $score = $this->matcher->score(
            queryTitle: 'Tum Hi Ho',
            queryArtist: 'Arijit Singh',
            queryAlbum: 'Aashiqui 2',
            queryDuration: 262,
            candTitle: 'Tum Hi Ho',
            candArtist: 'Arijit Singh',
            candAlbum: 'Aashiqui 2',
            candDuration: 262
        );

        $this->assertGreaterThanOrEqual(0.95, $score);
        $this->assertTrue($this->matcher->isMatch($score));
    }

    public function test_cleaned_title_with_official_video_and_brackets(): void
    {
        $score = $this->matcher->score(
            queryTitle: 'Kesariya (Official Video) [4K]',
            queryArtist: 'Pritam, Arijit Singh',
            queryDuration: 268,
            candTitle: 'Kesariya',
            candArtist: 'Arijit Singh',
            candDuration: 269 // within 3s tolerance
        );

        $this->assertGreaterThanOrEqual(0.70, $score);
        $this->assertTrue($this->matcher->isMatch($score));
    }

    public function test_regional_bengali_transliterated_track(): void
    {
        $score = $this->matcher->score(
            queryTitle: 'Bojhena Shey Bojhena',
            queryArtist: 'Arijit Singh',
            queryDuration: 280,
            candTitle: 'Bojhena Shey Bojhena (Title Track)',
            candArtist: 'Arijit Singh',
            candDuration: 281
        );

        $this->assertGreaterThanOrEqual(0.70, $score);
        $this->assertTrue($this->matcher->isMatch($score));
    }

    public function test_mismatched_title_is_rejected(): void
    {
        $score = $this->matcher->score(
            queryTitle: 'Tum Hi Ho',
            queryArtist: 'Arijit Singh',
            candTitle: 'Shape of You',
            candArtist: 'Ed Sheeran'
        );

        $this->assertLessThan(0.70, $score);
        $this->assertFalse($this->matcher->isMatch($score));
    }

    public function test_normalizer_parses_lrc_lines_with_exact_milliseconds(): void
    {
        $lrc = <<<LRC
[00:12.50]Line 1 of song
[00:15.80]Line 2 of song
[00:20.100]Line 3 of song
LRC;

        $normalized = $this->normalizer->normalize($lrc, 'line_synced');

        $this->assertEquals('line_synced', $normalized['type']);
        $this->assertCount(3, $normalized['lines']);

        $line1 = $normalized['lines'][0];
        $this->assertEquals(12500, $line1['start_ms']);
        $this->assertEquals(15800, $line1['end_ms']);
        $this->assertEquals('Line 1 of song', $line1['text']);

        $line2 = $normalized['lines'][1];
        $this->assertEquals(15800, $line2['start_ms']);
        $this->assertEquals(20100, $line2['end_ms']);
        $this->assertEquals('Line 2 of song', $line2['text']);
    }
}

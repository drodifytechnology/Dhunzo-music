<?php

namespace Tests\Unit\Lyrics;

use App\Services\Lyrics\LyricsMatcher;
use Tests\TestCase;

class MultilingualBenchmarkTest extends TestCase
{
    protected LyricsMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new LyricsMatcher();
    }

    /**
     * @dataProvider multilingualTracksProvider
     */
    public function test_multilingual_track_matching_passes_threshold(
        string $language,
        string $queryTitle,
        ?string $queryArtist,
        ?int $queryDuration,
        string $candTitle,
        ?string $candArtist,
        ?int $candDuration
    ): void {
        $score = $this->matcher->score(
            queryTitle: $queryTitle,
            queryArtist: $queryArtist,
            queryDuration: $queryDuration,
            candTitle: $candTitle,
            candArtist: $candArtist,
            candDuration: $candDuration
        );

        $this->assertGreaterThanOrEqual(
            0.70,
            $score,
            "Failed matching {$language} track: query '{$queryTitle}' against candidate '{$candTitle}' (score: {$score})"
        );
        $this->assertTrue($this->matcher->isMatch($score));
    }

    public static function multilingualTracksProvider(): array
    {
        return [
            // 1. Hindi tracks
            'Hindi - Tum Hi Ho' => [
                'language'      => 'Hindi',
                'queryTitle'    => 'Tum Hi Ho',
                'queryArtist'   => 'Arijit Singh',
                'queryDuration' => 262,
                'candTitle'     => 'Tum Hi Ho (From "Aashiqui 2")',
                'candArtist'    => 'Arijit Singh',
                'candDuration'  => 263,
            ],
            'Hindi - Kesariya' => [
                'language'      => 'Hindi',
                'queryTitle'    => 'Kesariya (From "Brahmastra")',
                'queryArtist'   => 'Pritam, Arijit Singh',
                'queryDuration' => 268,
                'candTitle'     => 'Kesariya',
                'candArtist'    => 'Arijit Singh',
                'candDuration'  => 268,
            ],

            // 2. Bengali tracks
            'Bengali - Tumi Robe Nirobe' => [
                'language'      => 'Bengali',
                'queryTitle'    => 'Tumi Robe Nirobe',
                'queryArtist'   => 'Rabindranath Tagore',
                'queryDuration' => 210,
                'candTitle'     => 'Tumi Robe Nirobe (Rabindra Sangeet)',
                'candArtist'    => 'Rabindranath Tagore',
                'candDuration'  => 211,
            ],
            'Bengali - Bojhena Shey Bojhena' => [
                'language'      => 'Bengali',
                'queryTitle'    => 'Bojhena Shey Bojhena (Title Track)',
                'queryArtist'   => 'Arijit Singh',
                'queryDuration' => 280,
                'candTitle'     => 'Bojhena Shey Bojhena',
                'candArtist'    => 'Arijit Singh',
                'candDuration'  => 282,
            ],

            // 3. Tamil track
            'Tamil - Arabic Kuthu' => [
                'language'      => 'Tamil',
                'queryTitle'    => 'Arabic Kuthu - Halamithi Habibo',
                'queryArtist'   => 'Anirudh Ravichander, Jonita Gandhi',
                'queryDuration' => 279,
                'candTitle'     => 'Arabic Kuthu (From "Beast")',
                'candArtist'    => 'Anirudh Ravichander',
                'candDuration'  => 280,
            ],

            // 4. Telugu track
            'Telugu - Naatu Naatu' => [
                'language'      => 'Telugu',
                'queryTitle'    => 'Naatu Naatu (From "RRR")',
                'queryArtist'   => 'Rahul Sipligunj, Kaala Bhairava',
                'queryDuration' => 215,
                'candTitle'     => 'Naatu Naatu',
                'candArtist'    => 'Rahul Sipligunj',
                'candDuration'  => 216,
            ],

            // 5. Punjabi track
            'Punjabi - Lover' => [
                'language'      => 'Punjabi',
                'queryTitle'    => 'Lover (MoonChild Era)',
                'queryArtist'   => 'Diljit Dosanjh',
                'queryDuration' => 190,
                'candTitle'     => 'Lover',
                'candArtist'    => 'Diljit Dosanjh',
                'candDuration'  => 192,
            ],

            // 6. International track
            'International - Blinding Lights' => [
                'language'      => 'English',
                'queryTitle'    => 'Blinding Lights',
                'queryArtist'   => 'The Weeknd',
                'queryDuration' => 200,
                'candTitle'     => 'Blinding Lights (Official Audio)',
                'candArtist'    => 'The Weeknd',
                'candDuration'  => 200,
            ],
        ];
    }
}

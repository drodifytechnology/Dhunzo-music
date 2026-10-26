<?php

namespace Tests\Unit\Lyrics;

use App\Services\Lyrics\LyricsMatcher;
use App\Services\Lyrics\LyricsNormalizer;
use App\Services\Lyrics\Providers\BetterLyricsProvider;
use App\Services\Lyrics\Providers\GeniusProvider;
use App\Services\Lyrics\Providers\KuGouProvider;
use App\Services\Lyrics\Providers\LrcLibProvider;
use App\Services\Lyrics\Providers\MusixmatchProvider;
use App\Services\Lyrics\Providers\NetEaseProvider;
use App\Services\Lyrics\Providers\YouTubeCaptionProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LyricsProvidersTest extends TestCase
{
    protected LyricsNormalizer $normalizer;
    protected LyricsMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new LyricsNormalizer();
        $this->matcher = new LyricsMatcher();
    }

    public function test_lrclib_provider_resolves_synced_lyrics(): void
    {
        $provider = new LrcLibProvider($this->normalizer, $this->matcher);

        Http::fake([
            'lrclib.net/api/get*' => Http::response([
                'id' => 1234,
                'trackName' => 'Tum Hi Ho',
                'artistName' => 'Arijit Singh',
                'albumName' => 'Aashiqui 2',
                'duration' => 262,
                'syncedLyrics' => "[00:10.50]Hum tere bin ab reh nahi sakte\n[00:15.20]Tere bina kya wajood mera",
                'plainLyrics' => "Hum tere bin ab reh nahi sakte\nTere bina kya wajood mera",
            ], 200),
        ]);

        $lyrics = $provider->searchLyrics('Tum Hi Ho', 'Arijit Singh', 'Aashiqui 2', 262);

        $this->assertNotNull($lyrics);
        $this->assertEquals('lrclib:1234', $lyrics->id);
        $this->assertEquals('lrclib', $lyrics->provider);
        $this->assertEquals('line_synced', $lyrics->type);
        $this->assertCount(2, $lyrics->lines);
        $this->assertEquals(10500, $lyrics->lines[0]['start_ms']);
    }

    public function test_kugou_provider_decodes_base64_lrc(): void
    {
        $provider = new KuGouProvider($this->normalizer, $this->matcher);

        $rawLrc = "[00:05.00]Asian Song Lyric Line\n[00:10.00]Second Line";
        $encoded = base64_encode($rawLrc);

        Http::fake([
            'lyrics.kugou.com/search*' => Http::response([
                'candidates' => [
                    [
                        'id' => 'kg_99',
                        'accesskey' => 'key_abc',
                        'song' => 'Asian Song',
                        'singer' => 'Singer',
                        'duration' => 180000,
                    ]
                ]
            ], 200),
            'lyrics.kugou.com/download*' => Http::response([
                'content' => $encoded,
            ], 200),
        ]);

        $lyrics = $provider->searchLyrics('Asian Song', 'Singer', null, 180);

        $this->assertNotNull($lyrics);
        $this->assertEquals('kugou:kg_99', $lyrics->id);
        $this->assertEquals('kugou', $lyrics->provider);
        $this->assertEquals('line_synced', $lyrics->type);
        $this->assertCount(2, $lyrics->lines);
        $this->assertEquals(5000, $lyrics->lines[0]['start_ms']);
    }

    public function test_musixmatch_provider_parses_subtitles(): void
    {
        $provider = new MusixmatchProvider($this->normalizer, $this->matcher);

        Http::fake([
            'apic-desktop.musixmatch.com/ws/1.1/macro.subtitles.get*' => Http::response([
                'message' => [
                    'body' => [
                        'macro_calls' => [
                            'track.subtitles.get' => [
                                'message' => [
                                    'body' => [
                                        'subtitle_list' => [
                                            [
                                                'subtitle' => [
                                                    'subtitle_id' => 'mx_sub_1',
                                                    'subtitle_body' => "[00:08.00]Musixmatch Synced Line",
                                                    'subtitle_language' => 'en',
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $lyrics = $provider->searchLyrics('Test Track', 'Test Artist');

        $this->assertNotNull($lyrics);
        $this->assertEquals('musixmatch', $lyrics->provider);
        $this->assertEquals('line_synced', $lyrics->type);
        $this->assertEquals('Musixmatch Synced Line', $lyrics->lines[0]['text']);
    }

    public function test_netease_provider_searches_and_fetches_lrc(): void
    {
        $provider = new NetEaseProvider($this->normalizer, $this->matcher);

        Http::fake([
            'music.163.com/api/search/get/web*' => Http::response([
                'result' => [
                    'songs' => [
                        [
                            'id' => 8888,
                            'name' => 'Chinese Track',
                            'artists' => [['name' => 'Artist']],
                            'duration' => 200000,
                        ]
                    ]
                ]
            ], 200),
            'music.163.com/api/song/lyric*' => Http::response([
                'lrc' => [
                    'lyric' => "[00:06.00]NetEase Lyric Line",
                ]
            ], 200),
        ]);

        $lyrics = $provider->searchLyrics('Chinese Track', 'Artist', null, 200);

        $this->assertNotNull($lyrics);
        $this->assertEquals('netease:8888', $lyrics->id);
        $this->assertEquals('NetEase Lyric Line', $lyrics->lines[0]['text']);
    }
}

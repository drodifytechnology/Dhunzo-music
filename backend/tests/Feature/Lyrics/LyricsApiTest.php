<?php

namespace Tests\Feature\Lyrics;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LyricsApiTest extends TestCase
{
    public function test_lyrics_search_endpoint_returns_standard_envelope(): void
    {
        Http::fake([
            'lrclib.net/api/get*' => Http::response([
                'id' => 999,
                'trackName' => 'Tum Hi Ho',
                'artistName' => 'Arijit Singh',
                'syncedLyrics' => "[00:10.00]Line 1\n[00:15.00]Line 2",
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/lyrics/search?title=Tum+Hi+Ho&artist=Arijit+Singh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'provider', 'type', 'lines', 'raw'],
            'meta' => ['provider', 'type', 'request_id'],
            'error',
            'request_id',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('lrclib', $response->json('data.provider'));
        $this->assertEquals('line_synced', $response->json('data.type'));
        $this->assertCount(2, $response->json('data.lines'));
    }

    public function test_lyrics_search_requires_title(): void
    {
        $response = $this->getJson('/api/v1/lyrics/search');

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'INVALID_REQUEST');
        $this->assertNotEmpty($response->json('error.details.title'));
    }

    public function test_lyrics_search_returns_404_when_no_match(): void
    {
        Http::fake([
            'lrclib.net/api/*' => Http::response(null, 404),
            'lyrics.cx/*' => Http::response([], 404),
            'lyrics.kugou.com/*' => Http::response([], 404),
            'music.163.com/*' => Http::response([], 404),
            'apic-desktop.musixmatch.com/*' => Http::response([], 404),
            'api.genius.com/*' => Http::response([], 404),
            'www.youtube.com/*' => Http::response(null, 404),
        ]);

        $response = $this->getJson('/api/v1/lyrics/search?title=NonExistentTrackXYZ');

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_lyrics_by_song_id(): void
    {
        Http::fake([
            'www.jiosaavn.com/api.php*' => Http::response([
                'song_100' => [
                    'id' => 'song_100',
                    'title' => 'Kesariya',
                    'image' => 'https://c.saavncdn.com/test.jpg',
                    'more_info' => [
                        'duration' => 268,
                        'primary_artists' => 'Arijit Singh',
                    ]
                ]
            ], 200),
            'lrclib.net/api/get*' => Http::response([
                'id' => 888,
                'trackName' => 'Kesariya',
                'artistName' => 'Arijit Singh',
                'syncedLyrics' => "[00:05.00]Kesariya tera ishq hai piya",
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/lyrics/saavn:song_100');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('lrclib:888', $response->json('data.id'));
    }
}

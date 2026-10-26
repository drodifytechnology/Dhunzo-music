<?php

namespace Tests\Feature\Music;

use App\DTOs\Music\SongDTO;
use App\Services\Music\MusicService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MusicApiTest extends TestCase
{
    public function test_search_endpoint_returns_standard_envelope(): void
    {
        Http::fake([
            'www.jiosaavn.com/api.php*' => Http::response([
                'total' => 1,
                'results' => [
                    [
                        'id' => '12345',
                        'title' => 'Tum Hi Ho',
                        'image' => 'https://c.saavncdn.com/123/tum-hi-ho-150x150.jpg',
                        'more_info' => [
                            'album' => 'Aashiqui 2',
                            'duration' => 262,
                            'primary_artists' => 'Arijit Singh',
                            'media_preview_url' => 'https://preview.saavncdn.com/123/sample_96_p.mp4',
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/music/search?q=Tum+Hi+Ho');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'provider', 'provider_id', 'title', 'artists', 'album', 'duration', 'artwork', 'playback']
            ],
            'meta' => ['provider', 'page', 'limit', 'request_id'],
            'error',
            'request_id',
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Tum Hi Ho', $json['data'][0]['title']);
        $this->assertEquals('saavn', $json['meta']['provider']);
    }

    public function test_search_validation_returns_invalid_request_envelope(): void
    {
        $response = $this->getJson('/api/v1/music/search');

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'INVALID_REQUEST');
        $this->assertNotEmpty($response->json('error.details.q'));
    }

    public function test_song_details_endpoint_returns_song(): void
    {
        Http::fake([
            'www.jiosaavn.com/api.php*' => Http::response([
                '999' => [
                    'id' => '999',
                    'title' => 'Channa Mereya',
                    'image' => 'https://c.saavncdn.com/999/channa-150x150.jpg',
                    'more_info' => [
                        'album' => 'Ae Dil Hai Mushkil',
                        'duration' => 289,
                        'music' => 'Pritam',
                        'media_preview_url' => 'https://preview.saavncdn.com/999/sample_96_p.mp4',
                    ]
                ]
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/music/songs/saavn:999');

        $response->assertStatus(200);
        $this->assertEquals('Channa Mereya', $response->json('data.title'));
        $this->assertEquals('saavn:999', $response->json('data.id'));
    }

    public function test_youtube_search_endpoint_returns_youtube_songs(): void
    {
        Http::fake([
            'www.googleapis.com/youtube/v3/search*' => Http::response([
                'items' => [
                    [
                        'id' => ['videoId' => 'rick_123'],
                        'snippet' => [
                            'title' => 'Rick Astley',
                            'channelTitle' => 'RickAstleyVEVO',
                            'thumbnails' => ['default' => ['url' => 'https://i.ytimg.com/test.jpg']]
                        ]
                    ]
                ]
            ], 200),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => []], 200),
        ]);

        $response = $this->getJson('/api/v1/music/search?q=Rick&provider=youtube');

        $response->assertStatus(200);
        $this->assertEquals('youtube', $response->json('meta.provider'));
        $this->assertEquals('youtube:rick_123', $response->json('data.0.id'));
        $this->assertEquals('provider_reference', $response->json('data.0.playback.type'));
    }
}

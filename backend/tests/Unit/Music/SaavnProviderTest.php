<?php

namespace Tests\Unit\Music;

use App\DTOs\Music\SongDTO;
use App\Exceptions\ProviderException;
use App\Services\Music\Providers\SaavnProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SaavnProviderTest extends TestCase
{
    protected SaavnProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new SaavnProvider();
    }

    public function test_search_transforms_payload_into_song_dtos_correctly(): void
    {
        Http::fake([
            'www.jiosaavn.com/api.php*' => Http::response(json_encode([
                'total' => 1,
                'start' => 0,
                'results' => [
                    [
                        'id' => 'abc12345',
                        'title' => 'Tum Hi Ho',
                        'subtitle' => 'Aashiqui 2',
                        'image' => 'https://c.saavncdn.com/123/tum-hi-ho-150x150.jpg',
                        'year' => '2013',
                        'language' => 'hindi',
                        'more_info' => [
                            'album' => 'Aashiqui 2',
                            'album_id' => 'alb999',
                            'duration' => 262,
                            'primary_artists' => 'Arijit Singh',
                            'media_preview_url' => 'https://preview.saavncdn.com/123/sample_96_p.mp4',
                            'has_lyrics' => 'true',
                        ]
                    ]
                ]
            ]), 200),
        ]);

        $results = $this->provider->search('Tum Hi Ho', 1, 10);

        $this->assertCount(1, $results);
        $song = $results[0];
        $this->assertInstanceOf(SongDTO::class, $song);
        $this->assertEquals('saavn:abc12345', $song->id);
        $this->assertEquals('saavn', $song->provider);
        $this->assertEquals('abc12345', $song->provider_id);
        $this->assertEquals('Tum Hi Ho', $song->title);
        $this->assertEquals(262, $song->duration);
        $this->assertEquals('hindi', $song->language);

        // Check artwork scaling
        $this->assertEquals('https://c.saavncdn.com/123/tum-hi-ho-500x500.jpg', $song->artwork['large']);
        $this->assertEquals('https://c.saavncdn.com/123/tum-hi-ho-150x150.jpg', $song->artwork['medium']);
        $this->assertEquals('https://c.saavncdn.com/123/tum-hi-ho-50x50.jpg', $song->artwork['small']);

        // Check playback url
        $this->assertEquals('https://aac.saavncdn.com/123/sample_320.mp4', $song->playback['url']);
        $this->assertEquals('stream', $song->playback['type']);
    }

    public function test_get_song_details_handles_nested_response(): void
    {
        Http::fake([
            'www.jiosaavn.com/api.php*' => Http::response(json_encode([
                'song_001' => [
                    'id' => 'song_001',
                    'title' => 'Kesariya',
                    'image' => 'https://c.saavncdn.com/456/kesariya-150x150.jpg',
                    'more_info' => [
                        'album' => 'Brahmastra',
                        'album_id' => 'alb_brah',
                        'duration' => 268,
                        'music' => 'Pritam, Arijit Singh',
                        'media_preview_url' => 'https://preview.saavncdn.com/456/kesariya_96_p.mp4',
                    ]
                ]
            ]), 200),
        ]);

        $song = $this->provider->getSongDetails('saavn:song_001');

        $this->assertNotNull($song);
        $this->assertEquals('Kesariya', $song->title);
        $this->assertEquals('Brahmastra', $song->album['title']);
        $this->assertCount(2, $song->artists);
        $this->assertEquals('Pritam', $song->artists[0]['name']);
        $this->assertEquals('Arijit Singh', $song->artists[1]['name']);
    }

    public function test_provider_throws_provider_exception_on_server_error(): void
    {
        Http::fake([
            'www.jiosaavn.com/api.php*' => Http::response('Gateway Timeout', 504),
        ]);

        $this->expectException(ProviderException::class);
        $this->provider->search('test');
    }
}

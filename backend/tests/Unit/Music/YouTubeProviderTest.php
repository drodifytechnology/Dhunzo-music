<?php

namespace Tests\Unit\Music;

use App\DTOs\Music\SongDTO;
use App\Exceptions\ProviderUnavailableException;
use App\Services\Music\Providers\YouTubeProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeProviderTest extends TestCase
{
    protected YouTubeProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        config(['dhunzo.music.providers.youtube.api_key' => 'fake-test-key']);
        $this->provider = new YouTubeProvider();
    }

    public function test_search_transforms_youtube_items_with_compliant_playback(): void
    {
        Http::fake([
            'www.googleapis.com/youtube/v3/search*' => Http::response([
                'items' => [
                    [
                        'id' => ['videoId' => 'dQw4w9WgXcQ'],
                        'snippet' => [
                            'title' => 'Rick Astley - Never Gonna Give You Up',
                            'channelTitle' => 'Rick Astley',
                            'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                            'publishedAt' => '2009-10-25T06:57:33Z',
                            'thumbnails' => [
                                'default' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg'],
                                'medium'  => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg'],
                                'high'    => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'],
                            ]
                        ]
                    ]
                ]
            ], 200),
            'www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'dQw4w9WgXcQ',
                        'contentDetails' => ['duration' => 'PT3M33S']
                    ]
                ]
            ], 200),
        ]);

        $results = $this->provider->search('Never Gonna Give You Up');

        $this->assertCount(1, $results);
        $song = $results[0];
        $this->assertInstanceOf(SongDTO::class, $song);
        $this->assertEquals('youtube:dQw4w9WgXcQ', $song->id);
        $this->assertEquals('youtube', $song->provider);
        $this->assertEquals('dQw4w9WgXcQ', $song->provider_id);
        $this->assertEquals('Rick Astley - Never Gonna Give You Up', $song->title);
        $this->assertEquals(213, $song->duration); // 3*60 + 33 = 213 seconds
        $this->assertEquals('2009', $song->year);

        // Verify compliant playback
        $this->assertEquals('provider_reference', $song->playback['type']);
        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $song->playback['url']);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $song->playback['embed_url']);

        // Verify album graceful fallback
        $this->assertNull($song->album['id']);
        $this->assertNull($song->album['title']);
    }

    public function test_youtube_quota_exceeded_throws_provider_unavailable_exception(): void
    {
        Http::fake([
            'www.googleapis.com/youtube/v3/search*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'Quota Exceeded',
                ]
            ], 403),
        ]);

        $this->expectException(ProviderUnavailableException::class);
        $this->provider->search('test');
    }
}

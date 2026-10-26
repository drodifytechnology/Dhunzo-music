<?php

namespace Tests\Unit\Music;

use App\DTOs\Music\AlbumDTO;
use App\DTOs\Music\ArtistDTO;
use App\DTOs\Music\PlaylistDTO;
use App\DTOs\Music\SongDTO;
use PHPUnit\Framework\TestCase;

class MusicDTOContractsTest extends TestCase
{
    public function test_song_dto_has_strict_structure_and_serialization(): void
    {
        $song = new SongDTO(
            id: 'saavn:12345',
            provider: 'saavn',
            provider_id: '12345',
            title: 'Tum Hi Ho',
            subtitle: 'Aashiqui 2',
            artists: [
                ['id' => 'art_1', 'name' => 'Arijit Singh', 'role' => 'singer'],
            ],
            album: ['id' => 'alb_1', 'title' => 'Aashiqui 2'],
            duration: 262,
            language: 'hindi',
            year: '2013',
            artwork: [
                'small'  => 'https://c.saavncdn.com/123/tum-hi-ho-50x50.jpg',
                'medium' => 'https://c.saavncdn.com/123/tum-hi-ho-150x150.jpg',
                'large'  => 'https://c.saavncdn.com/123/tum-hi-ho-500x500.jpg',
            ],
            playback: [
                'type'      => 'stream',
                'url'       => 'https://aac.saavncdn.com/123/sample.mp4',
                'embed_url' => null,
            ]
        );

        $array = $song->toArray();

        $this->assertEquals('saavn:12345', $array['id']);
        $this->assertEquals('saavn', $array['provider']);
        $this->assertEquals('12345', $array['provider_id']);
        $this->assertEquals('Tum Hi Ho', $array['title']);
        $this->assertEquals('Aashiqui 2', $array['subtitle']);
        $this->assertCount(1, $array['artists']);
        $this->assertEquals(['id' => 'alb_1', 'title' => 'Aashiqui 2'], $array['album']);
        $this->assertEquals(262, $array['duration']);
        $this->assertEquals('hindi', $array['language']);
        $this->assertEquals('2013', $array['year']);
        $this->assertArrayHasKey('small', $array['artwork']);
        $this->assertArrayHasKey('medium', $array['artwork']);
        $this->assertArrayHasKey('large', $array['artwork']);
        $this->assertArrayHasKey('type', $array['playback']);
        $this->assertArrayHasKey('url', $array['playback']);
        $this->assertArrayHasKey('embed_url', $array['playback']);

        // Test json serialization
        $json = json_encode($song);
        $this->assertJson($json);
    }

    public function test_youtube_song_dto_enforces_compliant_playback(): void
    {
        $ytSong = new SongDTO(
            id: 'youtube:dQw4w9WgXcQ',
            provider: 'youtube',
            provider_id: 'dQw4w9WgXcQ',
            title: 'Never Gonna Give You Up',
            playback: [
                'type'      => 'provider_reference',
                'url'       => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ]
        );

        $this->assertEquals('provider_reference', $ytSong->playback['type']);
        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $ytSong->playback['url']);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $ytSong->playback['embed_url']);
    }

    public function test_album_and_artist_and_playlist_dtos(): void
    {
        $album = new AlbumDTO(
            id: 'saavn:alb_1',
            provider: 'saavn',
            provider_id: 'alb_1',
            title: 'Aashiqui 2',
            songs: []
        );
        $this->assertEquals('Aashiqui 2', $album->title);
        $this->assertEquals(0, $album->toArray()['song_count']);

        $artist = new ArtistDTO(
            id: 'saavn:art_1',
            provider: 'saavn',
            provider_id: 'art_1',
            name: 'Arijit Singh'
        );
        $this->assertEquals('Arijit Singh', $artist->name);

        $playlist = new PlaylistDTO(
            id: 'saavn:pl_1',
            provider: 'saavn',
            provider_id: 'pl_1',
            title: 'Top Hindi Hits'
        );
        $this->assertEquals('Top Hindi Hits', $playlist->title);
    }
}

<?php

namespace Tests\Feature\Lyrics;

use App\Models\GeneratedLyric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSpeechToTextFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        GeneratedLyric::truncate();
    }

    public function test_ai_fallback_triggers_when_providers_fail_and_persists_in_database(): void
    {
        // 1. Fake existing providers to return 404/empty
        Http::fake([
            'lrclib.net/*' => Http::response(null, 404),
            'lyrics.cx/*' => Http::response([], 404),
            'lyrics.kugou.com/*' => Http::response([], 404),
            'music.163.com/*' => Http::response([], 404),
            'apic-desktop.musixmatch.com/*' => Http::response([], 404),
            'api.genius.com/*' => Http::response([], 404),
            'www.youtube.com/*' => Http::response(null, 404),
        ]);

        // Provide direct audio_url to trigger AI Speech-to-Text Fallback
        $response = $this->getJson('/api/v1/lyrics/search?title=Lollypop+Lagelu&artist=Pawan+Singh&audio_url=' . urlencode('https://example.com/audio.mp4'));

        // Assert HTTP 200 and standard envelope
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.provider', 'ai_speech_to_text');
        $response->assertJsonPath('data.type', 'line_synced');
        $response->assertJsonPath('data.language', 'bho'); // Bhojpuri auto-detection

        // Assert synchronized lines with millisecond precision
        $lines = $response->json('data.lines');
        $this->assertNotEmpty($lines);
        $this->assertArrayHasKey('start_ms', $lines[0]);
        $this->assertArrayHasKey('end_ms', $lines[0]);
        $this->assertArrayHasKey('text', $lines[0]);

        // Assert standard LRC raw output
        $raw = $response->json('data.raw');
        $this->assertStringContainsString('[', $raw);
        $this->assertStringContainsString(']', $raw);

        // 2. Assert saved in SQLite database
        $this->assertDatabaseHas('generated_lyrics', [
            'title'    => 'Lollypop Lagelu',
            'artist'   => 'Pawan Singh',
            'language' => 'bho',
            'provider' => 'ai_speech_to_text',
            'type'     => 'line_synced',
        ]);

        // 3. Second query should hit database when cache is cold
        Cache::flush();
        $secondResponse = $this->getJson('/api/v1/lyrics/search?title=Lollypop+Lagelu&artist=Pawan+Singh');
        $secondResponse->assertStatus(200);
        $secondResponse->assertJsonPath('data.extra.source', 'database');
        $secondResponse->assertJsonPath('data.extra.persisted_db', true);
    }

    public function test_existing_provider_lyrics_take_precedence_over_ai(): void
    {
        Http::fake([
            'lrclib.net/api/get*' => Http::response([
                'id'           => 12345,
                'trackName'    => 'Kesariya',
                'artistName'   => 'Arijit Singh',
                'syncedLyrics' => "[00:10.00] Mujhko itna bataye koi\n[00:15.00] Kaise tujhse dil na lagaye koi",
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/lyrics/search?title=Kesariya&artist=Arijit+Singh');

        $response->assertStatus(200);
        // Must return lrclib, NOT ai_speech_to_text
        $response->assertJsonPath('data.provider', 'lrclib');
        $this->assertDatabaseMissing('generated_lyrics', [
            'title' => 'Kesariya',
        ]);
    }
}

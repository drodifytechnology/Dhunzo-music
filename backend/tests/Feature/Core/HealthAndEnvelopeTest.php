<?php

namespace Tests\Feature\Core;

use Illuminate\Support\Str;
use Tests\TestCase;

class HealthAndEnvelopeTest extends TestCase
{
    public function test_health_check_returns_standard_envelope(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['status', 'service', 'timestamp'],
            'meta' => ['provider', 'page', 'limit', 'request_id'],
            'error',
            'request_id',
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertNull($json['error']);
        $this->assertNotEmpty($json['request_id']);
        $this->assertEquals($json['request_id'], $json['meta']['request_id']);
        $this->assertEquals($json['request_id'], $response->headers->get('X-Request-ID'));
    }

    public function test_incoming_request_id_header_is_preserved(): void
    {
        $customId = (string) Str::uuid();

        $response = $this->withHeaders([
            'X-Request-ID' => $customId,
        ])->getJson('/api/health');

        $response->assertStatus(200);
        $this->assertEquals($customId, $response->json('request_id'));
        $this->assertEquals($customId, $response->headers->get('X-Request-ID'));
    }

    public function test_not_found_endpoint_returns_standard_error_envelope(): void
    {
        $response = $this->getJson('/api/non-existent-endpoint');

        $response->assertStatus(404);
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['provider', 'page', 'limit', 'request_id'],
            'error' => ['code', 'message', 'details'],
            'request_id',
        ]);

        $json = $response->json();
        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertEquals('NOT_FOUND', $json['error']['code']);
        $this->assertNotEmpty($json['request_id']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NetworkMapRoutingTest extends TestCase
{
    private array $coordinates = ['origin_lat' => -7, 'origin_lng' => 106.7, 'dest_lat' => -7.01, 'dest_lng' => 106.71, 'purpose' => 'fiber'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.osrm.url' => 'https://routing.example.test']);
        Cache::flush();
        $this->withoutMiddleware();
        $this->actingAs((new User)->forceFill(['id' => 1, 'role' => 'admin', 'name' => 'GIS Tester']));
        Http::preventStrayRequests();
    }

    private function road(): array
    {
        return ['routes' => [['distance' => 1800, 'duration' => 130, 'geometry' => ['type' => 'LineString', 'coordinates' => [[106.7, -7], [106.708, -7], [106.71, -7.01]]]]]];
    }

    public function test_road_routes_are_cached_without_repeating_upstream_calls(): void
    {
        Http::fake(['*' => Http::response($this->road())]);
        $this->postJson('/network-map/route', $this->coordinates)->assertOk()->assertJsonPath('source', 'osrm');
        $this->postJson('/network-map/route', $this->coordinates)->assertOk()->assertJsonPath('cached', true);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'steps=false') && $request->hasHeader('User-Agent', 'VIZBILL-GIS/1.0'));
    }

    public function test_uncached_routes_are_rate_limited_across_requests(): void
    {
        Http::fake(['*' => Http::response($this->road())]);
        $this->postJson('/network-map/route', $this->coordinates)->assertOk();
        $this->postJson('/network-map/route', array_replace($this->coordinates, ['dest_lat' => -7.02]))->assertStatus(429)->assertHeader('Retry-After', '1');
        Http::assertSentCount(1);
    }

    public function test_unavailable_road_uses_explicit_straight_line_fallback(): void
    {
        Http::fake(['*' => Http::response(['code' => 'NoRoute'], 200)]);
        $this->postJson('/network-map/route', $this->coordinates)->assertOk()->assertJsonPath('status', 'fallback')->assertJsonPath('source', 'straight_line');
    }

    public function test_invalid_geometry_is_not_reported_as_a_road(): void
    {
        $road = $this->road();
        $road['routes'][0]['geometry']['coordinates'][1] = [200, -7];
        Http::fake(['*' => Http::response($road)]);
        $this->postJson('/network-map/route', $this->coordinates)->assertOk()->assertJsonPath('source', 'straight_line');
    }

    public function test_invalid_coordinate_range_is_rejected_before_external_request(): void
    {
        Http::fake();
        $this->postJson('/network-map/route', array_replace($this->coordinates, ['origin_lat' => 100]))->assertUnprocessable();
        Http::assertNothingSent();
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\GeoIPService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoIPServiceTest extends TestCase
{
    private GeoIPService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeoIPService();
    }

    /** @test */
    public function it_returns_usd_for_us_ip()
    {
        Http::fake([
            'ipapi.co/1.1.1.1/json/' => Http::response(['country_code' => 'US']),
        ]);

        $currency = $this->service->getCurrencyByIP('1.1.1.1');

        $this->assertEquals('USD', $currency);
    }

    /** @test */
    public function it_returns_cad_for_ca_ip()
    {
        Http::fake([
            'ipapi.co/2.2.2.2/json/' => Http::response(['country_code' => 'CA']),
        ]);

        $currency = $this->service->getCurrencyByIP('2.2.2.2');

        $this->assertEquals('CAD', $currency);
    }

    /** @test */
    public function it_defaults_to_cad_on_api_failure()
    {
        Http::fake([
            'ipapi.co/3.3.3.3/json/' => Http::response([], 500),
        ]);

        $currency = $this->service->getCurrencyByIP('3.3.3.3');

        $this->assertEquals('CAD', $currency);
    }

    /** @test */
    public function it_defaults_to_cad_for_unknown_country()
    {
        Http::fake([
            'ipapi.co/4.4.4.4/json/' => Http::response(['country_code' => 'FR']),
        ]);

        $currency = $this->service->getCurrencyByIP('4.4.4.4');

        $this->assertEquals('CAD', $currency);
    }
}

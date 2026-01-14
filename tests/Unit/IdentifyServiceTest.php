<?php

namespace Tests\Unit;

use App\Exceptions\Api\ApiException;
use App\Services\Api\Identify\IdentifyService;
use App\ValueObjects\Api\Identify\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdentifyServiceTest extends TestCase
{
    protected IdentifyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://identify-eu-west-1.acrcloud.com/v1/identify' => Http::response(
                file_get_contents(
                    base_path('/tests/mocks/identify/response.json'),
                ),
            ),
        ]);

        // Fake config so that service doesn't throw on construct.
        config([
            'services.acrcloud.access_key' => 'test',
            'services.acrcloud.secret_key' => 'test',
        ]);

        $this->service = new IdentifyService();
    }

    public function testSuccess()
    {
        try {
            $res = $this->service->identify(
                base_path('/tests/mocks/identify/berlioz_sample.wav'),
            );
        } catch (ApiException $e) {
            $this->fail(
                $e->getMessage(),
            );
        }

        $this->assertCount(1, $res->music);
    }
}

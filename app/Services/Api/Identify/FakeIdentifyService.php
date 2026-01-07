<?php

namespace App\Services\Api\Identify;

use App\ValueObjects\Api\Identify\Response;

class FakeIdentifyService implements IdentifyServiceInterface
{
    public function identify(string $filePath): Response
    {
        return Response::fromArray(
            json_decode(
                file_get_contents(
                    base_path('/tests/mocks/identify/response.json')
                ),
                true,
            ),
        );
    }
}

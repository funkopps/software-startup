<?php

namespace App\Services\Api\Identify;

use App\Exceptions\Api\ApiException;
use App\ValueObjects\Api\Identify\Response;

interface IdentifyServiceInterface
{
    /**
     * @throws ApiException
     */
    public function identify(string $filePath): Response;
}
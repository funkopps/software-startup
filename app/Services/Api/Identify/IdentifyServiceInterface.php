<?php

namespace App\Services\Api\Identify;

use App\ValueObjects\Api\Identify\Response;

interface IdentifyServiceInterface
{
    public function identify(string $filePath): Response;
}
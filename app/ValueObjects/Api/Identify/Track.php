<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;

class Track extends SerializableValueObject
{
    public function __construct(
        public string $id,
        public string $name,
    )
    {
    }
}
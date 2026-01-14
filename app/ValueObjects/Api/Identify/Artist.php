<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;

class Artist extends SerializableValueObject
{
    public function __construct(
        public string $id,
        public string $name,
    )
    {
    }

    public static function fromArray(array $array): static
    {
        return new self($array['id'], $array['name']);
    }
}
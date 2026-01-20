<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;
use Illuminate\Support\Arr;

class Response extends SerializableValueObject
{
    /**
     * @param Music[] $music
     */
    public function __construct(
        public array $music,
    )
    {}

    public static function fromJson(array $array): static
    {
        /** @var array $musicArr */
        $musicArr = Arr::get($array, 'metadata.music', []);
        $music = Arr::map($musicArr, fn (array $data) => Music::fromArray($data));

        return new self($music);
    }
}

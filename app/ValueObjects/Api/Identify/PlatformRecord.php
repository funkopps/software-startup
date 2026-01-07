<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;
use Illuminate\Support\Arr;

class PlatformRecord extends SerializableValueObject
{
    /**
     * @param Track $track
     * @param Artist[] $artists
     */
    public function __construct(
        public Track $track,
        public array $artists,
    )
    {}

    public static function fromArray(array $array): static
    {
        $artists = Arr::map($array['artists'], fn (array $data) => Artist::fromArray($data));

        return new self(
            Track::fromArray($array['track']),
            $artists
        );
    }
}
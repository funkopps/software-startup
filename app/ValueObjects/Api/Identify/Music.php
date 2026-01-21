<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;
use Illuminate\Support\Arr;

class Music extends SerializableValueObject
{
    /**
     * @link https://docs.acrcloud.com/metadata/music
     */
    public function __construct(
        public int $score,
        public string $title,
        public string $releaseDate,
        public PlatformRecord $spotify,
    )
    {
    }

    public static function fromArray(array $array): static
    {
        $spotify = Arr::get($array, 'external_metadata.spotify');

        return new self(
            $array['score'],
            $array['title'],
            $array['release_date'],
            PlatformRecord::fromArray($spotify),
        );
    }
}

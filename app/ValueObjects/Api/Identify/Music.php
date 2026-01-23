<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Music extends SerializableValueObject
{
    /**
     * @link https://docs.acrcloud.com/metadata/music
     */
    public function __construct(
        public int $score,
        public string $title,
        public ?string $releaseDate,
        public ?PlatformRecord $spotify,
        public string $artist,
        public ?string $acrid,
    ) {}

    public static function fromArray(array $array): static
    {
        // Artists come from `artists[]` for both music & humming
        $artists = collect($array['artists'] ?? [])
            ->pluck('name')
            ->filter()
            ->values();

        // Spotify exists only for `metadata.music`
        $spotifyData = Arr::get($array, 'external_metadata.spotify');
        $spotify = is_array($spotifyData)
            ? PlatformRecord::fromArray($spotifyData)
            : null;

        return new self(
            score: (int) ($array['score'] ?? 0),
            title: (string) ($array['title'] ?? 'Unknown'),
            releaseDate: $array['release_date'] ?? null,
            spotify: $spotify,
            artist: $artists->first() ?? 'Unknown',
            acrid: $array['acrid'] ?? null,
        );
    }
}

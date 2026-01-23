<?php

namespace App\ValueObjects\Api\Identify;

use App\ValueObjects\SerializableValueObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Response extends SerializableValueObject
{
    /**
     * @param Music[] $music
     */
    public function __construct(
        public array $music,
    ) {}

    public static function fromJson(array $array): static
    {
        $musicArr =
            Arr::get($array, 'metadata.music')
            ?? Arr::get($array, 'metadata.humming')
            ?? [];

        // Log::debug('Response::fromJson raw musicArr', [
        //     'type' => gettype($musicArr),
        //     'count' => is_array($musicArr) ? count($musicArr) : null,
        //     'example' => is_array($musicArr) ? ($musicArr[0] ?? null) : null,
        // ]);

        $music = Arr::map(
            is_array($musicArr) ? $musicArr : [],
            fn(array $data) => Music::fromArray($data)
        );

        // Log::debug('Response::fromJson mapped music', [
        //     'count' => count($music),
        //     'first' => $music[0] ?? null,
        // ]);

        return new self($music);
    }
}

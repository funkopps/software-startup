<?php

namespace App\ValueObjects;

use App\ValueObjects\Api\Identify\Music;

class RecognizeAudioChunk extends SerializableValueObject
{
    /**
     * @param int $timestamp
     * @param Music $music
     */
    public function __construct(
        public int $timestamp,
        public Music $music,
    )
    {
    }
}
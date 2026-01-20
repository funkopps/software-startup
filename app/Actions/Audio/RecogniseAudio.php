<?php

namespace App\Actions\Audio;

use App\Services\Api\Identify\IdentifyService;
use App\ValueObjects\Api\Identify\Music;
use App\ValueObjects\RecogniseAudioChunk;

final readonly class RecogniseAudio
{
    public function __construct(
        private CutAudio $cutAudioAction,
        private ChunkAudio $chunkAudioAction,
        private IdentifyService $service,
    )
    {
    }

    /**
     * @param string $path Audio file path
     * @param int $start Start time (seconds)
     * @param int $end End time (seconds)
     * @return RecogniseAudioChunk[]
     */
    public function run(
        string $path,
        int $start,
        int $end,
    ): array {
        // TODO Tim - afmaken
    }
}

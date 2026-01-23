<?php

namespace App\Actions\Audio;

use App\Services\Ffmpeg;
use LogicException;

final readonly class ChunkAudio
{
    public function __construct(
        private Ffmpeg $ffmpeg,
    ) {}

    /**
     * @param string $path
     * @param int $chunkSize Seconds
     * @param string $outDir Path to output directory
     * @return string[] New audio paths
     */
    public function run(
        string $path,
        int $chunkSize,
        string $outDir,
    ): array {
        if (! is_dir($outDir) && ! mkdir($outDir)) {
            throw new LogicException('Could not create chunk dir');
        }

        $dest = "$outDir/chunk_%03d.wav";

        $command = sprintf(
            '-y -i "%s" -f segment -segment_time %d -c copy "%s"',
            $path,
            $chunkSize,
            $dest,
        );

        $this->ffmpeg->run($command);

        return glob($outDir . '/*');
    }
}

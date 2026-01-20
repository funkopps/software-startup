<?php

namespace App\Actions\Audio;

use App\Services\Ffmpeg;
use Illuminate\Support\Str;
use LogicException;

final readonly class ChunkAudio
{
    public function __construct(
        private Ffmpeg $ffmpeg,
    )
    {

    }

    /**
     * @param string $path
     * @param int $chunkSize Seconds
     * @return string[] New audio paths
     */
    public function run(string $path, int $chunkSize): array
    {
        $dir = storage_path(
            sprintf('app/private/%s', Str::random())
        );
        $dest = "$dir/chunk_%03d.wav";

        if (! mkdir($dir)) {
            throw new LogicException('Could not create chunk dir');
        }

        $command = sprintf(
            '-y -i %s -f segment -segment_time %s -c copy %s',
            escapeshellarg($path),
            $chunkSize,
            escapeshellcmd($dest),
        );

        $this->ffmpeg->run($command);

        return glob($dir . '/*');
    }
}

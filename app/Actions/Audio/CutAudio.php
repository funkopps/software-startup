<?php

namespace App\Actions\Audio;

use App\Services\Ffmpeg;

final readonly class CutAudio
{
    public function __construct(private Ffmpeg $ffmpeg) {}

    /**
     * @param string $inputPath
     * @param string $outputPath
     * @param int $start Start time (seconds)
     * @param int $end End time (seconds)
     * @return string
     */
    public function run(
        string $inputPath,
        string $outputPath,
        int $start,
        int $end,
    ): string {
        $this->ffmpeg->run(sprintf(
            '-y -ss %d -to %d -i "%s" -c copy "%s"',
            $start,
            $end,
            $inputPath,
            $outputPath,
        ));

        return $outputPath;
    }
}

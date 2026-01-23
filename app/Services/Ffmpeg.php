<?php

namespace App\Services;

use LogicException;

final readonly class Ffmpeg
{
    private string $ffmpegPath;

    public function __construct()
    {
        $this->ffmpegPath = config('services.ffmpeg.path');

        if (! file_exists($this->ffmpegPath)) {
            throw new LogicException('Could not find FFMPEG executable, configure this in .env!');
        }
    }

    public function run(string $command): bool
    {
        $fullCommand = "$this->ffmpegPath $command";

        exec($fullCommand, $outputLines, $exitCode);

        if ($exitCode !== 0) {
            throw new LogicException(sprintf(
                "FFmpeg failed (%s):\n%s\nCommand:\n%s",
                $exitCode,
                implode(PHP_EOL, $outputLines),
                $fullCommand,
            ));
        }

        return true;
    }
}

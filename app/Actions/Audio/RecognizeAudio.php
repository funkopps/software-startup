<?php

namespace App\Actions\Audio;

use App\Exceptions\Api\ApiException;
use App\Services\Api\Identify\IdentifyServiceInterface;
use App\ValueObjects\Api\Identify\Music;
use App\ValueObjects\RecognizeAudioChunk;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LogicException;

final readonly class RecognizeAudio
{
    private const int MAX_SAMPLE_SIZE = 3;

    private const int AUDIO_CHUNK_SIZE = 3;

    public function __construct(
        private CutAudio $cutAudioAction,
        private ChunkAudio $chunkAudioAction,
        private IdentifyServiceInterface $service,
    )
    {}

    /**
     * @param string $path Audio file path
     * @param int $start Start time (seconds)
     * @param int $end End time (seconds)
     * @return RecognizeAudioChunk[]
     */
    public function run(
        string $path,
        int $start,
        int $end,
    ): array {
        $dirPath = storage_path('app/private/' . Str::random());
        if (! mkdir($dirPath)) {
            throw new LogicException('Could not create working directory');
        }

        $samplePaths = $this->buildSamples($path, $dirPath, $start, $end);
        $out = collect();

        foreach ($samplePaths as $timestamp => $samplePath) {
            try {
                $response = $this->service->identify($samplePath);
            } catch (ApiException $e) {
                throw new LogicException(
                    'Failed to recognize audio; API failed.',
                    previous: $e,
                );
            }

            $out[$timestamp] = $response->music;
        }

        // Clean up files
        File::deleteDirectory($dirPath);

        return $out
            ->flatMap(fn (array $music, int $timestamp) => Arr::map(
                $music,
                fn (Music $music) => new RecognizeAudioChunk(
                    $timestamp,
                    $music,
                ),
            ))
            ->unique('spotify.track.id')
            ->all();
    }

    /**
     * @return Collection<int, string> Paths keyed by timestamp (seconds)
     */
    private function buildSamples(
        string $audioPath,
        string $dirPath,
        int $start,
        int $end,
    ): Collection {
        $cutAudioPath = "$dirPath/cut_audio.wav";
        $this->cutAudioAction->run($audioPath, $cutAudioPath, $start, $end);

        $chunkedAudioPaths = collect(
            $this->chunkAudioAction->run(
                $cutAudioPath,
                self::AUDIO_CHUNK_SIZE,
                $dirPath . '/chunks',
            ),
        );

        // Key the array w/ correct timestamps (relative to entire track)
        $chunkedAudioPaths->keyBy(
            fn ($_, int $i) => $start + (self::AUDIO_CHUNK_SIZE * $i)
        );

        return $this->evenlySpaceItems($chunkedAudioPaths);
    }

    /**
     * @return Collection<int, string>
     */
    private function evenlySpaceItems(Collection $items): Collection
    {
        $n = $items->count();
        $sampleSize = self::MAX_SAMPLE_SIZE;

        if ($n <= $sampleSize) {
            return $items;
        }

        $keys   = $items->keys()->values();
        $values = $items->values();

        $step = ($n - 1) / ($sampleSize - 1);

        $result = [];
        for ($i = 0; $i < $sampleSize; $i++) {
            $index = (int) round($i * $step);
            $key   = $keys[$index];
            $result[$key] = $values[$index];
        }

        return collect($result);
    }
}

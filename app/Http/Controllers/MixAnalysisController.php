<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Api\Identify\IdentifyServiceInterface;
use App\ValueObjects\Api\Identify\Response;
use App\ValueObjects\Api\Identify\Music;
use Illuminate\Http\JsonResponse;

class MixAnalysisController extends Controller
{
    private IdentifyServiceInterface $identifyService;

    public function __construct(IdentifyServiceInterface $identifyService)
    {
        $this->identifyService = $identifyService;
    }

    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'soundcloud_url' => ['required', 'url'],
        ]);

        // Placeholder local file path
        $audioFilePath = base_path('tests/mocks/identify/berlioz_sample.wav');

        if (!file_exists($audioFilePath)) {
            return response()->json([
                'error' => 'Sample audio file not found',
            ], 500);
        }

        $response = $this->identifyService->identify($audioFilePath);

        $tracklist = $this->mapAcrResponseToTracklist($response);

        return response()->json([
            'source_url' => $request->soundcloud_url,
            'tracks' => $tracklist,
        ]);
    }

    private function mapAcrResponseToTracklist(Response $response): array
    {
        if (empty($response->music)) {
            return [];
        }

        return collect($response->music)->map(function (Music $track) {
            return [
                'title' => $track->title,
                'artist' => $track->spotify->artists[0]->name ?? 'Unknown',
                'album' => null,
                'release_date' => $track->releaseDate,
                'score' => $track->score,
                'spotify_track_id' => $track->spotify->track->id,
            ];
        })->values()->toArray();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Api\Identify\IdentifyServiceInterface;
use App\ValueObjects\Api\Identify\Response;
use App\ValueObjects\Api\Identify\Music;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MixAnalysisController extends Controller
{
    private IdentifyServiceInterface $identifyService;

    public function __construct(IdentifyServiceInterface $identifyService)
    {
        $this->identifyService = $identifyService;
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'audio_file' => ['required', 'file', 'mimetypes:audio/*', 'max:10240'],
        ]);

        $path = $request->file('audio_file')->store('uploads');

        return response()->json([
            'file_path' => $path,
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'file_path' => ['required', 'string'],
        ]);

        $relativePath = $request->file_path;

        if (!str_starts_with($relativePath, 'uploads/')) {
            abort(403, 'Invalid file path');
        }

        if (Storage::exists($relativePath)) {
            Storage::delete($relativePath);
        }

        return response()->json(['deleted' => true]);
    }

    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'soundcloud_url' => ['required', 'url'],
            'file_path' => ['required', 'string'],
            'start_time' => ['required', 'numeric', 'min:0'],
            'end_time' => ['required', 'numeric', 'gt:start_time'],
        ]);

        $relativePath = $request->file_path;
        $audioFilePath = Storage::path($relativePath);

        if (!file_exists($audioFilePath)) {
            return response()->json([
                'error' => 'Uploaded audio file not found',
            ], 404);
        }

        $response = $this->identifyService->identify($audioFilePath);

        $tracklist = $this->mapAcrResponseToTracklist($response);

        //Storage::delete($relativePath);

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

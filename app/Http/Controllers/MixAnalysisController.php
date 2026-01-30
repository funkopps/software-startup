<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ValueObjects\Api\Identify\Response;
use App\ValueObjects\Api\Identify\Music;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Actions\Audio\RecogniseAudio;

class MixAnalysisController extends Controller
{
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

    public function analyze(Request $request, RecogniseAudio $recogniseAudio): JsonResponse
    {
        $validated = $request->validate([
            'soundcloud_url' => ['nullable', 'url', 'required_without:file_path'],
            'file_path' => ['nullable', 'string', 'required_without:soundcloud_url'],
            'start_time' => ['required', 'numeric', 'min:0'],
            'end_time' => ['required', 'numeric', 'gt:start_time'],
        ]);

        $relativePath = $validated['file_path'];

        $audioFilePath = Storage::path($relativePath);

        if (!file_exists($audioFilePath)) {
            return response()->json([
                'error' => 'Uploaded audio file not found',
            ], 404);
        }

        $chunks = $recogniseAudio->run(
            path: $audioFilePath,
            start: (int) $validated['start_time'],
            end: (int) $validated['end_time'],
        );

        Log::debug('Recognised chunks', [
            'count' => count($chunks),
            'first' => $chunks[0] ?? null,
        ]);


        return response()->json([
            'source_url' => $validated['soundcloud_url'],
            'tracks' => collect($chunks)->map(fn($chunk) => [
                'timestamp' => $chunk->timestamp,
                'title' => $chunk->music->title,
                'artist' => $chunk->music->artist,
                'album' => null,
                'release_date' => $chunk->music->releaseDate,
                'score' => $chunk->music->score,
                'acrid' => $chunk->music->acrid,
                'spotify_track_id' => $chunk->music->spotify->track->id ?? null,
            ])->values(),
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

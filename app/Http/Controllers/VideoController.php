<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideo;
use App\Models\Set;
use App\Models\Video;
use App\Videos\MultipartUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Uploading films (Videographer plan). The browser sends the file straight
 * to the bucket in parts, using URLs signed here; PHP never handles the
 * film itself.
 *
 *   POST start → {id, part_size}; POST parts → signed PUT URL per part;
 *   browser PUTs each part, keeps each ETag; POST complete → processing.
 */
class VideoController extends Controller
{
    public function __construct(private MultipartUploads $uploads) {}

    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        $limits = $user->videoLimits();
        abort_unless($limits, 403, 'Video is part of the Videographer plan.');
        if (! $this->uploads->available()) {
            throw ValidationException::withMessages(['file' => 'Video needs object storage, which isn\'t set up on this server.']);
        }

        $data = $request->validate([
            'set_id' => ['required', 'string'],
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);
        $set = Set::with('collection')->findOrFail($data['set_id']);
        abort_unless($set->collection?->user_id === $user->id, 404);

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (! in_array($extension, config('videos.extensions'), true)) {
            throw ValidationException::withMessages(['file' => 'Upload an MP4 or MOV film (H.264 or H.265).']);
        }
        if ($data['size'] > $limits['max_file']) {
            throw ValidationException::withMessages(['file' => 'That film is bigger than your plan allows ('.$this->mb($limits['max_file']).' per film).']);
        }
        $used = (int) $user->videos()->sum('size');
        if ($used + $data['size'] > $limits['max_total']) {
            throw ValidationException::withMessages(['file' => 'Your films would use more than your plan\'s '.$this->mb($limits['max_total']).'. Delete some to make room.']);
        }

        $mime = in_array($data['type'] ?? '', config('videos.mimes'), true) ? $data['type'] : ($extension === 'mov' ? 'video/quicktime' : 'video/mp4');
        $video = new Video([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'original_filename' => Str::limit(basename($data['filename']), 250, ''),
            'mime' => $mime,
            'size' => $data['size'],
            'status' => Video::UPLOADING,
            'sort_order' => (int) $set->videos()->max('sort_order') + 1,
        ]);
        $video->id = (string) Str::uuid();
        $video->key = 'videos/'.$user->id.'/'.$video->id.'.'.$extension;
        $video->forceFill(['upload_id' => $this->uploads->create($video->key, $mime)])->save();

        return response()->json([
            'id' => $video->id,
            'part_size' => config('videos.part_size_mb') * 1048576,
            'parts' => (int) ceil($data['size'] / (config('videos.part_size_mb') * 1048576)),
        ]);
    }

    public function parts(Request $request, Video $video): JsonResponse
    {
        $this->own($request, $video, Video::UPLOADING);
        $data = $request->validate(['part_numbers' => ['required', 'array', 'min:1', 'max:100'], 'part_numbers.*' => ['integer', 'min:1', 'max:10000']]);

        return response()->json(['urls' => $this->uploads->partUrls($video->key, $video->upload_id, $data['part_numbers'])]);
    }

    public function complete(Request $request, Video $video): JsonResponse
    {
        $this->own($request, $video, Video::UPLOADING);
        $data = $request->validate([
            'parts' => ['required', 'array', 'min:1', 'max:10000'],
            'parts.*.part_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'parts.*.etag' => ['required', 'string', 'max:200'],
        ]);
        $parts = collect($data['parts'])->sortBy('part_number')->values()
            ->map(fn ($p) => ['PartNumber' => (int) $p['part_number'], 'ETag' => $p['etag']])->all();

        $this->uploads->complete($video->key, $video->upload_id, $parts);
        $video->forceFill(['upload_id' => null, 'status' => Video::PROCESSING])->save();
        ProcessVideo::dispatch($video->id);

        return response()->json(['id' => $video->id, 'status' => $video->status]);
    }

    public function abort(Request $request, Video $video): JsonResponse
    {
        $this->own($request, $video, Video::UPLOADING);
        $this->remove($video);

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $this->own($request, $video);
        $video->update($request->validate(['title' => ['nullable', 'string', 'max:120']]));

        return back()->with('success', 'Film renamed.');
    }

    public function destroy(Request $request, Video $video): RedirectResponse
    {
        $this->own($request, $video);
        $this->remove($video);

        return back()->with('success', 'Film deleted.');
    }

    private function remove(Video $video): void
    {
        if ($video->upload_id) {
            try {
                $this->uploads->abort($video->key, $video->upload_id);
            } catch (\Throwable) {
                // already gone
            }
        }
        Storage::disk('s3')->delete(array_filter([$video->key, $video->poster_key]));
        $video->delete();
    }

    private function own(Request $request, Video $video, ?string $status = null): void
    {
        abort_unless($video->user_id === $request->user()->id, 404);
        abort_if($status && $video->status !== $status, 409, 'This upload has already finished.');
    }

    private function mb(int $bytes): string
    {
        return $bytes >= 1073741824 ? round($bytes / 1073741824, 1).' GB' : round($bytes / 1048576).' MB';
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\CompressionMethod;
use ZipStream\ZipStream;

/**
 * A buyer's downloads (signed links, from the delivery email). Paid photos
 * are the untouched originals, never watermarked.
 */
class DigitalDownloadController extends Controller
{
    public function show(Order $order): View
    {
        $photos = $this->photos($order);
        $expires = $order->downloadsExpireAt();

        return view('downloads.show', [
            'order' => $order->load('user', 'collection'),
            'photos' => $photos,
            'expires' => $expires,
            'photoUrl' => fn (Photo $photo) => URL::temporarySignedRoute('downloads.photo', $expires, ['order' => $order->id, 'photo' => $photo->id]),
            'zipUrl' => $photos->count() > 1 ? URL::temporarySignedRoute('downloads.zip', $expires, ['order' => $order->id]) : null,
        ]);
    }

    public function photo(Order $order, Photo $photo): RedirectResponse
    {
        $photos = $this->photos($order);
        $index = $photos->search(fn (Photo $p) => $p->id === $photo->id);
        abort_if($index === false, 404);

        $name = $this->fileName($order, $photo, $index);

        return redirect()->away(Storage::disk('s3')->temporaryUrl($photo->url, now()->addMinutes(10), [
            'ResponseContentDisposition' => 'attachment; filename="'.$name.'"',
        ]));
    }

    /** Every photo in one ZIP, streamed as it's read (nothing is built or stored first). */
    public function zip(Order $order): StreamedResponse
    {
        $photos = $this->photos($order);
        $name = Str::slug($order->collection?->name ?: 'photos').'.zip';

        return new StreamedResponse(function () use ($order, $photos) {
            @set_time_limit(0);
            $zip = new ZipStream(
                sendHttpHeaders: false,
                defaultCompressionMethod: CompressionMethod::STORE, // photos are already compressed
                flushOutput: true,
            );
            $disk = Storage::disk('s3');
            foreach ($photos as $index => $photo) {
                try {
                    $stream = $disk->readStream($photo->url);
                } catch (\Throwable) {
                    $stream = null; // storage errors throw on S3
                }
                if (! $stream) {
                    \Log::warning('Paid download missing from storage', ['order_id' => $order->id, 'photo_id' => $photo->id]);

                    continue;
                }
                $zip->addFileFromStream($this->fileName($order, $photo, $index), $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            $zip->finish();
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'X-Accel-Buffering' => 'no', // stream through nginx rather than buffering
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** The order's photos, only while it's paid and the link is in date. */
    private function photos(Order $order): Collection
    {
        abort_unless($order->isDigital() && $order->paid_at && $order->status === 'completed', 404);

        return $order->items()->with('product.photo')->orderBy('id')->get()
            ->map(fn ($item) => $item->product?->photo)
            ->filter()
            ->values();
    }

    private function fileName(Order $order, Photo $photo, int $index): string
    {
        $extension = strtolower(pathinfo($photo->url, PATHINFO_EXTENSION)) ?: 'jpg';

        return Str::slug($order->collection?->name ?: 'photo').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'.'.$extension;
    }
}

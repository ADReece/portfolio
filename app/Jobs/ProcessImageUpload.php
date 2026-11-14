<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Makes an uploaded photo's thumbnail and watermarked copy, in the queue
 * worker (never the web request), one photo per job. Memory stays bounded
 * whatever the upload size:
 *  - JPEGs are decoded at reduced size (libjpeg's "jpeg:size" hint), so a
 *    6000×4000 file is never fully expanded in memory;
 *  - Imagick's resource limits cap its own (native) memory, spilling to
 *    disk instead of growing past them;
 *  - the watermark is drawn on a display-size copy (2048 px), not the original.
 * The original is stored untouched by the upload request.
 */
class ProcessImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    // Real photos take 1-5 s. Kept below the queue's retry_after so a slow
    // job is never handed out twice.
    public int $timeout = 80;
    public int $backoff = 30;

    /** Longest edge of the thumbnail and of the watermarked display copy. */
    public const THUMB_EDGE = 800;
    public const DISPLAY_EDGE = 2048;

    public function __construct(public string $photoId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $photo = Photo::find($this->photoId);
        if (! $photo || $photo->status !== Photo::PROCESSING) {
            return; // deleted meanwhile, or already done
        }

        $original = self::downloadOriginal($photo->url);
        [$width, $height] = self::originalSize($original);

        try {
            $derivatives = self::makeDerivatives($original, $photo->user->watermark_text ?: '@'.$photo->user->username);
            Storage::disk('s3')->put(self::derivativeKey($photo->url, 'thumbnails'), $derivatives['thumbnail']);
            Storage::disk('s3')->put(self::derivativeKey($photo->url, 'watermarked'), $derivatives['watermarked']);
            Storage::disk('s3')->put(self::derivativeKey($photo->url, 'display'), $derivatives['display']);
        } finally {
            @unlink($original);
        }

        $photo->update(['status' => Photo::READY, 'processing_error' => null, 'has_display' => true, 'width' => $width ?: null, 'height' => $height ?: null]);
    }

    /** After the last attempt: mark the photo failed so the owner sees why. */
    public function failed(?Throwable $e): void
    {
        Log::warning('Photo processing failed', ['photo_id' => $this->photoId, 'error' => $e?->getMessage()]);

        Photo::whereKey($this->photoId)->update([
            'status' => Photo::FAILED,
            'processing_error' => Photo::FAILED_MESSAGE,
        ]);
    }

    /**
     * The thumbnail, the web-sized display copy and its watermarked twin for
     * an image file. The original is decoded once, at reduced size, into the
     * display copy; the others are made from that copy.
     *
     * @return array{thumbnail: string, display: string, watermarked: string} encoded images
     */
    public static function makeDerivatives(string $path, string $watermarkText): array
    {
        self::limitImagick();

        $display = self::load($path, self::DISPLAY_EDGE);
        $display->thumbnailImage(self::DISPLAY_EDGE, self::DISPLAY_EDGE, true);

        $thumb = clone $display;
        $thumb->thumbnailImage(self::THUMB_EDGE, self::THUMB_EDGE, true);
        $thumbnail = self::webJpeg($thumb, 80);
        $thumb->clear();

        $clean = clone $display;
        $web = self::webJpeg($clean, 82);
        $clean->clear();

        $image = (new ImageManager(['driver' => 'imagick']))->make($display);
        self::applyTextWatermark($image, $watermarkText);
        $watermarked = (string) $image->stream(null, 85);
        $image->destroy();

        return ['thumbnail' => $thumbnail, 'display' => $web, 'watermarked' => $watermarked];
    }

    /** The web-sized copy alone (for photos uploaded before display copies existed). */
    public static function makeDisplay(string $path): string
    {
        self::limitImagick();
        $display = self::load($path, self::DISPLAY_EDGE);
        $display->thumbnailImage(self::DISPLAY_EDGE, self::DISPLAY_EDGE, true);
        $web = self::webJpeg($display, 82);
        $display->clear();

        return $web;
    }

    /** A progressive sRGB JPEG: small, and shows a full preview while it loads. */
    private static function webJpeg(\Imagick $image, int $quality): string
    {
        if ($image->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
            $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        }
        if ($image->getImageAlphaChannel()) {
            // JPEG has no transparency: put transparent PNGs on white, not black.
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        }
        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality($quality);
        $image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        $image->setOption('jpeg:sampling-factor', '4:2:0'); // as cameras save them: smaller files

        return $image->getImageBlob();
    }

    public static function derivativeKey(string $originalKey, string $folder): string
    {
        // A derived copy must never land on the original's key (it would overwrite it).
        if (! str_starts_with($originalKey, 'photos/')) {
            throw new \InvalidArgumentException("Can't derive a {$folder} copy for a photo stored outside photos/: {$originalKey}");
        }

        return preg_replace('#^photos/#', $folder.'/', $originalKey);
    }

    /**
     * The image's size the right way up (EXIF rotation applied), read from
     * its header without decoding it.
     *
     * @return array{0: int, 1: int}
     */
    public static function originalSize(string $path): array
    {
        try {
            $im = new \Imagick();
            $im->pingImage($path.'[0]');
            $size = [$im->getImageWidth(), $im->getImageHeight()];
            $sideways = in_array($im->getImageOrientation(), [
                \Imagick::ORIENTATION_LEFTTOP, \Imagick::ORIENTATION_RIGHTTOP,
                \Imagick::ORIENTATION_RIGHTBOTTOM, \Imagick::ORIENTATION_LEFTBOTTOM,
            ], true);
            $im->clear();

            return $sideways ? [$size[1], $size[0]] : $size;
        } catch (\Throwable) {
            return [0, 0];
        }
    }

    /** Caps Imagick's native memory so one image can't take the container down. */
    public static function limitImagick(): void
    {
        $mb = 1024 * 1024;
        $limits = [
            // Guard rails only, sized so a normal photo (decoded at reduced
            // size) stays in RAM: anything bigger spills to the slower disk
            // cache instead of being killed.
            // A JPEG (decoded at reduced size) stays well inside these; only a
            // huge non-JPEG (e.g. a 24 MP PNG) reaches them and spills to disk.
            'RESOURCETYPE_MEMORY' => 160 * $mb,
            'RESOURCETYPE_MAP' => 160 * $mb,
            'RESOURCETYPE_AREA' => 40 * 1000 * 1000,
            'RESOURCETYPE_DISK' => 2048 * $mb,
            'RESOURCETYPE_WIDTH' => 20000,
            'RESOURCETYPE_HEIGHT' => 20000,
            'RESOURCETYPE_THREAD' => 1,
        ];
        foreach ($limits as $constant => $value) {
            if (defined(\Imagick::class.'::'.$constant)) {
                \Imagick::setResourceLimit(constant(\Imagick::class.'::'.$constant), $value);
            }
        }
    }

    /** Streams the original to a temporary file (never into PHP memory). */
    public static function downloadOriginal(string $key): string
    {
        $path = tempnam(sys_get_temp_dir(), 'photo-');
        $in = Storage::disk('s3')->readStream($key);
        if (! $in) {
            throw new \RuntimeException('Original photo is missing: '.$key);
        }
        $out = fopen($path, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($out);
        if (is_resource($in)) {
            fclose($in);
        }

        return $path;
    }

    /**
     * Reads the first frame, decoding JPEGs at roughly $edge px, then applies
     * the EXIF orientation and strips metadata (camera details, GPS).
     */
    private static function load(string $path, int $edge): \Imagick
    {
        // Ask libjpeg to decode at the smallest scale (1/2, 1/4, 1/8) whose
        // long edge is still at least $edge: a cheap header read gives the shape.
        $probe = new \Imagick();
        $probe->pingImage($path.'[0]');
        $landscape = $probe->getImageWidth() >= $probe->getImageHeight();
        $probe->clear();

        $im = new \Imagick();
        $im->setOption('jpeg:size', $landscape ? $edge.'x1' : '1x'.$edge);
        $im->readImage($path.'[0]');
        self::orient($im);
        $im->stripImage();

        return $im;
    }

    private static function orient(\Imagick $im): void
    {
        switch ($im->getImageOrientation()) {
            case \Imagick::ORIENTATION_TOPRIGHT: $im->flopImage(); break;
            case \Imagick::ORIENTATION_BOTTOMRIGHT: $im->rotateImage('#000', 180); break;
            case \Imagick::ORIENTATION_BOTTOMLEFT: $im->flipImage(); break;
            case \Imagick::ORIENTATION_LEFTTOP: $im->transposeImage(); break;
            case \Imagick::ORIENTATION_RIGHTTOP: $im->rotateImage('#000', 90); break;
            case \Imagick::ORIENTATION_RIGHTBOTTOM: $im->transverseImage(); break;
            case \Imagick::ORIENTATION_LEFTBOTTOM: $im->rotateImage('#000', -90); break;
        }
        $im->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
    }

    /** A dense tiled text watermark, scaled to the (display-size) image. */
    private static function applyTextWatermark($image, string $text): void
    {
        $scale = max($image->width(), $image->height()) / 2048;
        $fontSize = max(16, (int) round(36 * $scale));
        $color = 'rgba(255, 255, 255, 0.55)';
        $dx = max(160, (int) round(340 * $scale)); // between marks in a row
        $dy = max(90, (int) round(190 * $scale));  // between rows
        $fontPath = public_path('fonts/OpenSans-Bold.ttf');

        $draw = function ($x, $y, $size, $angle) use ($image, $text, $color, $fontPath) {
            $image->text($text, $x, $y, function ($font) use ($size, $angle, $color, $fontPath) {
                if (file_exists($fontPath)) {
                    $font->file($fontPath);
                }
                $font->size($size);
                $font->color($color);
                $font->align('center');
                $font->valign('middle');
                $font->angle($angle);
            });
        };

        // Staggered rows of diagonal marks, then a lighter cross-hatch layer
        // between them, so no part of the photo is left clean.
        $row = 0;
        for ($y = 0; $y < $image->height() + $dy; $y += $dy, $row++) {
            $offset = $row % 2 ? (int) ($dx / 2) : 0;
            for ($x = $offset; $x < $image->width() + $dx; $x += $dx) {
                $draw($x, $y, $fontSize, -30);
            }
        }
        $row = 0;
        for ($y = (int) ($dy / 2); $y < $image->height(); $y += $dy, $row++) {
            $offset = $row % 2 ? 0 : (int) ($dx / 2);
            for ($x = $offset; $x < $image->width(); $x += $dx) {
                $draw($x, $y, (int) round($fontSize * 0.7), 30);
            }
        }
    }
}

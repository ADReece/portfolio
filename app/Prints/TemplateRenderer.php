<?php

namespace App\Prints;

use App\Models\Font;
use App\Models\Photo;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

/**
 * Draws a print template (an overlay and text fields) on a photo. Field
 * positions are percentages; font sizes are pixels on the full-size original,
 * so a smaller image (a preview) passes $scale = its width / the original's.
 */
class TemplateRenderer
{
    /**
     * @param  \Intervention\Image\Image  $image  the photo, already the right way up
     * @param  array<string, string>  $values  field key => text
     */
    public static function apply($image, Template $template, array $values, float $scale = 1.0): void
    {
        $manager = new \Intervention\Image\ImageManager(['driver' => 'imagick']);

        if ($template->overlay_path) {
            $overlay = $manager->make(Storage::disk('s3')->get($template->overlay_path));
            $overlay->resize($image->width(), $image->height());
            $image->insert($overlay, 'top-left', 0, 0);
            $overlay->destroy();
        }

        $defaultFont = public_path('fonts/OpenSans-Bold.ttf');
        $tempFonts = [];
        try {
            foreach ($template->fields as $field) {
                $value = trim((string) ($values[$field['key']] ?? ''));
                if ($value === '') {
                    continue;
                }

                $fontPath = $defaultFont;
                if (! empty($field['font_id']) && ($font = Font::find($field['font_id']))) {
                    $fontPath = $tempFonts[] = $font->downloadToTemp();
                }

                $image->text($value,
                    (int) round(($field['x'] / 100) * $image->width()),
                    (int) round(($field['y'] / 100) * $image->height()),
                    function ($f) use ($fontPath, $field, $scale) {
                        if (file_exists($fontPath)) {
                            $f->file($fontPath);
                        }
                        $f->size(max(1, (int) round(((int) ($field['font_size'] ?? 60)) * $scale)));
                        $f->color($field['color'] ?? '#ffffff');
                        $f->align($field['align'] ?? 'center');
                        $f->valign('middle');
                    });
            }
        } finally {
            foreach ($tempFonts as $tmp) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Only the template's own fields, as trimmed text of a sensible length.
     *
     * @return array<string, string>
     */
    public static function cleanValues(Template $template, array $input): array
    {
        $values = [];
        foreach ($template->fields as $field) {
            $values[$field['key']] = mb_substr(trim((string) ($input[$field['key']] ?? '')), 0, 120);
        }

        return $values;
    }

    /**
     * A small watermarked preview (JPEG) of a personalised print, made from the
     * photo's watermarked display copy, so it never gives away a clean image.
     */
    public static function preview(Photo $photo, Template $template, array $values, int $edge = 1200): string
    {
        \App\Jobs\ProcessImageUpload::limitImagick();
        $manager = new \Intervention\Image\ImageManager(['driver' => 'imagick']);
        $image = $manager->make(Storage::disk('s3')->get(\App\Jobs\ProcessImageUpload::derivativeKey($photo->url, 'watermarked')));
        $image->resize($edge, $edge, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        });

        [$originalWidth] = $photo->originalSize();
        self::apply($image, $template, $values, $originalWidth > 0 ? $image->width() / $originalWidth : 1.0);

        $jpeg = (string) $image->encode('jpg', 80);
        $image->destroy();

        return $jpeg;
    }
}

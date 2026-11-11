<?php

namespace App\Prints;

use App\Models\ProdigiProduct;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Our own product mockups for the print catalogue (Prodigi's API has no
 * product images): a sample photo from our licensed marketing set, shown as
 * the product (framed with or without a mount, canvas, paper print or a
 * gloss panel) in its real proportions and frame colour, on a soft wall.
 *
 * Products of the same shape share one stored file. An uploaded image always
 * takes priority over the mockup.
 */
class MockupGenerator
{
    /** Bump to redraw every mockup after a style change. */
    public const VERSION = 1;

    public const SIZE = 800;

    private const FRAME_COLOURS = [
        'black' => '#1d1d1f',
        'white' => '#f3f2ef',
        'natural' => '#b98b5d',
        'oak' => '#b98b5d',
        'wood' => '#b98b5d',
        'walnut' => '#6b4a32',
        'brown' => '#6b4a32',
        'silver' => '#b8babd',
        'gold' => '#b8955a',
        'grey' => '#7b7d80',
        'gray' => '#7b7d80',
    ];

    /** What to draw, or null when we have no mockup style for it (cards, books, gifts). */
    public function kind(ProdigiProduct $product): ?string
    {
        $family = preg_replace('/^GLOBAL-/', '', $product->groupKey());
        $description = strtolower((string) $product->description.' '.$product->name);

        return match (true) {
            in_array($family, ['CFPM', 'BOXM'], true) => 'framed_mount',
            $product->category === 'Framed' => str_contains($description, 'mount') ? 'framed_mount' : 'framed',
            $product->category === 'Canvas' => 'canvas',
            $product->category === 'Wall art' => 'panel',
            in_array($product->category, ['Prints', 'Fine art prints'], true) => 'print',
            default => null,
        };
    }

    /** Width ÷ height of the product, from Prodigi's dimensions or the SKU's size. */
    public function aspect(ProdigiProduct $product): float
    {
        $dims = $product->raw_payload['productDimensions'] ?? [];
        if (is_numeric($dims['width'] ?? null) && is_numeric($dims['height'] ?? null) && $dims['width'] > 0 && $dims['height'] > 0) {
            return (float) $dims['width'] / (float) $dims['height'];
        }
        if (preg_match('/-(\d+(?:\.\d+)?)X(\d+(?:\.\d+)?)$/i', $product->sku, $m) && (float) $m[2] > 0) {
            return (float) $m[1] / (float) $m[2];
        }
        if (preg_match('/-A\d$/i', $product->sku)) {
            return 1 / M_SQRT2;
        }

        return 0.8;
    }

    /** The frame colour: the first offered option, e.g. black. */
    public function frameColour(ProdigiProduct $product): string
    {
        $offered = $product->offeredAttributes();
        $first = strtolower((string) (($offered['color'] ?? $offered['frameColour'] ?? $offered['frameColor'] ?? [])[0] ?? 'black'));
        foreach (self::FRAME_COLOURS as $name => $hex) {
            if (str_contains($first, $name)) {
                return $hex;
            }
        }

        return self::FRAME_COLOURS['black'];
    }

    /**
     * Draws (or reuses) the product's mockup and records it on the product.
     * Returns the stored path, or null if there's no style for it.
     */
    public function generate(ProdigiProduct $product, bool $force = false): ?string
    {
        $kind = $this->kind($product);
        if ($kind === null) {
            if ($product->mockup_path) {
                $product->forceFill(['mockup_path' => null])->save();
            }

            return null;
        }

        $aspect = round($this->aspect($product), 3);
        $colour = str_starts_with($kind, 'framed') ? $this->frameColour($product) : '';
        $photo = $this->samplePhoto($aspect);
        $path = 'catalogue/mockups/'.$this->keyFor($product).'.jpg';

        $disk = Storage::disk('s3');
        if ($force || ! $disk->exists($path)) {
            $disk->put($path, $this->render($kind, $aspect, $colour, $photo));
        }
        if ($product->mockup_path !== $path) {
            $product->forceFill(['mockup_path' => $path])->save();
        }

        return $path;
    }

    /** Products with the same key share one mockup file. */
    public function keyFor(ProdigiProduct $product): string
    {
        $kind = (string) $this->kind($product);
        $aspect = round($this->aspect($product), 3);
        $colour = str_starts_with($kind, 'framed') ? $this->frameColour($product) : '';

        return substr(md5(implode('|', [self::VERSION, $kind, $aspect, $colour, basename($this->samplePhoto($aspect))])), 0, 16);
    }

    /** The JPEG for one shape. */
    public function render(string $kind, float $aspect, string $frameColour, string $photoPath): string
    {
        $size = self::SIZE;
        $scene = new Imagick();
        $scene->newPseudoImage($size, $size, 'gradient:#f1efeb-#e3dfd9');
        $scene->setImageFormat('png');

        // The product's outer size: as large as fits, leaving wall around it.
        $maxW = $size * 0.74;
        $maxH = $size * 0.74;
        $w = $aspect >= 1 ? $maxW : $maxH * $aspect;
        $h = $aspect >= 1 ? $maxW / $aspect : $maxH;
        if ($h > $maxH) {
            [$w, $h] = [$maxH * $aspect, $maxH];
        }
        $w = (int) round($w);
        $h = (int) round($h);
        $short = min($w, $h);

        $product = match ($kind) {
            'framed', 'framed_mount' => $this->framed($w, $h, $short, $frameColour, $kind === 'framed_mount', $photoPath),
            'canvas' => $this->canvas($w, $h, $short, $photoPath),
            'panel' => $this->panel($w, $h, $photoPath),
            default => $this->paper($w, $h, $short, $photoPath),
        };

        // A soft shadow below and to the right, as if hung on the wall.
        $shadow = clone $product;
        $shadow->setImageBackgroundColor(new ImagickPixel('black'));
        $shadow->shadowImage(38, max(6, $short * 0.025), 0, 0);
        $x = (int) (($size - $product->getImageWidth()) / 2);
        $y = (int) (($size - $product->getImageHeight()) / 2);
        $offset = (int) max(4, $short * 0.018);
        $scene->compositeImage($shadow, Imagick::COMPOSITE_OVER, $x - (int) (($shadow->getImageWidth() - $product->getImageWidth()) / 2) + $offset, $y - (int) (($shadow->getImageHeight() - $product->getImageHeight()) / 2) + $offset * 2);
        $scene->compositeImage($product, Imagick::COMPOSITE_OVER, $x, $y);

        $scene->setImageFormat('jpeg');
        $scene->setImageCompressionQuality(84);
        $scene->stripImage();
        $blob = $scene->getImageBlob();
        foreach ([$scene, $shadow, $product] as $im) {
            $im->clear();
        }

        return $blob;
    }

    /** A sample photo whose shape is close to the product's, from the licensed marketing set. */
    public function samplePhoto(float $aspect): string
    {
        $photos = config('prints.mockup_photos');
        $key = $aspect > 1.15 ? 'landscape' : ($aspect < 0.87 ? 'portrait' : 'square');

        return public_path($photos[$key]);
    }

    private function photo(string $path, int $w, int $h): Imagick
    {
        $im = new Imagick($path);
        $im->cropThumbnailImage(max(1, $w), max(1, $h));
        $im->setImagePage(0, 0, 0, 0);

        return $im;
    }

    private function layer(int $w, int $h, string $colour = 'none'): Imagick
    {
        $im = new Imagick();
        $im->newImage($w, $h, new ImagickPixel($colour), 'png');

        return $im;
    }

    private function framed(int $w, int $h, int $short, string $colour, bool $mount, string $photoPath): Imagick
    {
        $frame = (int) max(12, round($short * 0.055));
        $im = $this->layer($w, $h, $colour);

        // A lighter inner edge so the frame reads as moulding.
        $edge = new ImagickDraw();
        $edge->setFillColor(new ImagickPixel('none'));
        $edge->setStrokeColor(new ImagickPixel('rgba(255,255,255,0.18)'));
        $edge->setStrokeWidth(2);
        $edge->rectangle(1, 1, $w - 2, $h - 2);
        $edge->setStrokeColor(new ImagickPixel('rgba(0,0,0,0.35)'));
        $edge->rectangle($frame - 1, $frame - 1, $w - $frame, $h - $frame);
        $im->drawImage($edge);

        $innerW = $w - 2 * $frame;
        $innerH = $h - 2 * $frame;
        if ($mount) {
            $im->compositeImage($this->layer($innerW, $innerH, '#f7f5f0'), Imagick::COMPOSITE_OVER, $frame, $frame);
            $margin = (int) round(min($innerW, $innerH) * 0.14);
            $photo = $this->photo($photoPath, $innerW - 2 * $margin, $innerH - 2 * $margin);
            $im->compositeImage($photo, Imagick::COMPOSITE_OVER, $frame + $margin, $frame + $margin);
            // The mount's bevelled cut.
            $bevel = new ImagickDraw();
            $bevel->setFillColor(new ImagickPixel('none'));
            $bevel->setStrokeColor(new ImagickPixel('rgba(0,0,0,0.12)'));
            $bevel->setStrokeWidth(2);
            $bevel->rectangle($frame + $margin - 1, $frame + $margin - 1, $w - $frame - $margin, $h - $frame - $margin);
            $im->drawImage($bevel);
        } else {
            $im->compositeImage($this->photo($photoPath, $innerW, $innerH), Imagick::COMPOSITE_OVER, $frame, $frame);
        }

        return $im;
    }

    private function canvas(int $w, int $h, int $short, string $photoPath): Imagick
    {
        // The front, plus the right and bottom edges of the stretched canvas.
        $depth = (int) max(8, round($short * 0.035));
        $im = $this->layer($w + $depth, $h + $depth);
        $front = $this->photo($photoPath, $w, $h);

        $right = clone $front;
        $right->cropImage(max(1, $depth), $h, $w - $depth, 0);
        $right->setImagePage(0, 0, 0, 0);
        $right->modulateImage(62, 90, 100);
        $bottom = clone $front;
        $bottom->cropImage($w, max(1, $depth), 0, $h - $depth);
        $bottom->setImagePage(0, 0, 0, 0);
        $bottom->modulateImage(50, 90, 100);

        // Shear the edges back at 45° so they read as depth.
        $right->setImageBackgroundColor(new ImagickPixel('none'));
        $right->shearImage(new ImagickPixel('none'), 0, 45);
        $right->setImagePage(0, 0, 0, 0);
        $bottom->setImageBackgroundColor(new ImagickPixel('none'));
        $bottom->shearImage(new ImagickPixel('none'), 45, 0);
        $bottom->setImagePage(0, 0, 0, 0);
        $im->compositeImage($right, Imagick::COMPOSITE_OVER, $w, 0);
        $im->compositeImage($bottom, Imagick::COMPOSITE_OVER, 0, $h);
        $im->compositeImage($front, Imagick::COMPOSITE_OVER, 0, 0);

        return $im;
    }

    private function paper(int $w, int $h, int $short, string $photoPath): Imagick
    {
        $im = $this->layer($w, $h, '#fbfaf7');
        $margin = (int) round($short * 0.06);
        $im->compositeImage($this->photo($photoPath, $w - 2 * $margin, $h - 2 * $margin), Imagick::COMPOSITE_OVER, $margin, $margin);

        return $im;
    }

    private function panel(int $w, int $h, string $photoPath): Imagick
    {
        $im = $this->photo($photoPath, $w, $h);
        // A faint diagonal sheen for acrylic or metal.
        $sheen = new Imagick();
        $sheen->newPseudoImage($h, $w, 'gradient:rgba(255,255,255,0.22)-rgba(255,255,255,0)');
        $sheen->rotateImage(new ImagickPixel('none'), -90);
        $sheen->setImagePage(0, 0, 0, 0);
        $im->compositeImage($sheen, Imagick::COMPOSITE_SCREEN, 0, 0);

        return $im;
    }
}

<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Prints\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

/**
 * After a personalised print is paid for: draws the photographer's template
 * (overlay and the client's text) on the full-size original, and stores it
 * for Prodigi to print. Runs before SubmitOrderToProdigi (a job chain).
 */
class RenderPersonalisedPrints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $timeout = 300;

    public function __construct(public string $orderId) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (! $order || ! $order->paid_at) {
            return;
        }

        $items = OrderItem::with('template', 'product.photo')->where('order_id', $order->id)
            ->whereNotNull('template_id')->whereNull('print_asset_path')->get();

        foreach ($items as $item) {
            $photo = $item->product?->photo;
            if (! $photo || ! $item->template) {
                throw new \RuntimeException("Personalised print {$item->id} has no photo or template");
            }

            ProcessImageUpload::limitImagick(); // big originals spill to disk rather than exhaust memory
            $original = ProcessImageUpload::downloadOriginal($photo->url);
            try {
                $image = (new \Intervention\Image\ImageManager(['driver' => 'imagick']))->make($original);
                $image->orientate();
                TemplateRenderer::apply($image, $item->template, (array) $item->template_data);
                $path = 'prints/orders/'.$order->id.'/'.$item->id.'.jpg';
                Storage::disk('s3')->put($path, (string) $image->encode('jpg', 92));
                $image->destroy();
            } finally {
                @unlink($original);
            }

            $item->update(['print_asset_path' => $path]);
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Rendering a personalised print failed', ['order_id' => $this->orderId, 'error' => $e->getMessage()]);
        Order::whereKey($this->orderId)->update([
            'prodigi_status' => 'failed',
            'prodigi_error_message' => 'Personalised print could not be made: '.$e->getMessage(),
        ]);
    }
}

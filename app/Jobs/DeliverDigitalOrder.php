<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * After a digital order is paid: emails the buyer their download link and
 * tells the photographer about the sale. Runs once per order.
 */
class DeliverDigitalOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public string $orderId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $order = Order::with('user', 'collection')->find($this->orderId);
        if (! $order || ! $order->isDigital() || ! $order->paid_at || $order->delivered_at) {
            return;
        }

        $count = $order->items()->count();
        $photographer = $order->user;
        $photographerName = $photographer->name ?: $photographer->username;
        $collectionName = $order->collection?->name ?? 'your photos';

        Mail::send('emails.digital-order-ready', [
            'order' => $order,
            'count' => $count,
            'collectionName' => $collectionName,
            'photographerName' => $photographerName,
            'downloadUrl' => $order->downloadUrl(),
            'expiresAt' => $order->downloadsExpireAt()->format('j F Y'),
        ], fn ($message) => $message->to($order->customer_email)
            ->replyTo($photographer->email, $photographerName)
            ->subject("Your photos from {$photographerName} are ready to download"));

        // Marked first, so a failed notification below can't resend the buyer's email.
        DB::table('orders')->where('id', $order->id)->update(['delivered_at' => now()]);

        try {
            Mail::send('emails.digital-sale', [
                'order' => $order,
                'count' => $count,
                'collectionName' => $collectionName,
            ], fn ($message) => $message->to($photographer->email)
                ->subject('You sold '.$count.' '.Str::plural('photo', $count).' from '.$collectionName));
        } catch (\Throwable $e) {
            \Log::warning('Digital sale notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}

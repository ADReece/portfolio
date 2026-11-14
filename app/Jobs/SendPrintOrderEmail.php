<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the client about their print order: confirmed once paid, and shipped
 * (with tracking) once Prodigi dispatches it. The photographer hears about
 * the sale with the confirmation.
 */
class SendPrintOrderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CONFIRMED = 'confirmed';
    public const SHIPPED = 'shipped';

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public string $orderId, public string $kind)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $order = Order::with('user', 'items.product.photo', 'items.template')->find($this->orderId);
        if (! $order || ! $order->paid_at || ! $order->hasPrintItems()) {
            return;
        }

        $photographer = $order->user;
        $photographerName = $photographer->name ?: $photographer->username;
        $data = [
            'order' => $order,
            'photographerName' => $photographerName,
            'items' => $order->items,
            'reference' => strtoupper(substr(str_replace('-', '', $order->id), 0, 8)),
        ];

        if ($this->kind === self::SHIPPED) {
            Mail::send('emails.print-order-shipped', $data, fn ($message) => $message->to($order->customer_email)
                ->replyTo($photographer->email, $photographerName)
                ->subject("Your prints from {$photographerName} are on their way"));

            return;
        }

        Mail::send('emails.print-order-confirmed', $data, fn ($message) => $message->to($order->customer_email)
            ->replyTo($photographer->email, $photographerName)
            ->subject("Your print order from {$photographerName} is confirmed"));

        try {
            Mail::send('emails.print-sale', $data, fn ($message) => $message->to($photographer->email)
                ->subject('You sold a print order ('.\App\Support\Currency::format($order->total).')'));
        } catch (\Throwable $e) {
            \Log::warning('Print sale notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}

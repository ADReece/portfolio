<?php

namespace App\Seo;

use App\Billing\LaunchOffer;
use App\Models\SubscriptionPlan;
use App\Support\Currency;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Everything the homepage says about itself to people and to crawlers: the
 * meta description, the FAQ and the JSON-LD. Both get the same content (the
 * FAQ JSON-LD mirrors the visible FAQ), and the launch offer drops out of all
 * of it automatically once LAUNCH_OFFER_ENDS_AT has passed.
 */
class HomePage
{
    /** @param Collection<int, SubscriptionPlan> $plans active plans, in display order */
    public function __construct(
        private LaunchOffer $offer,
        private Collection $plans,
        private ?CarbonInterface $now = null,
    ) {}

    public function offerActive(): bool
    {
        return $this->offer->isActive($this->now);
    }

    /** @return Collection<int, SubscriptionPlan> */
    public function paidPlans(): Collection
    {
        return $this->plans->filter(fn (SubscriptionPlan $p) => ! $p->isFree() && (float) $p->price > 0)->values();
    }

    public function cheapestPaidPlan(): ?SubscriptionPlan
    {
        return $this->paidPlans()->sortBy(fn ($p) => (float) $p->price)->first();
    }

    /** "1.99" during the offer, "3.99" after it. */
    public function fromPrice(): ?string
    {
        $plan = $this->cheapestPaidPlan();
        if (! $plan) {
            return null;
        }

        return $this->offerActive() ? $this->offer->discountedMonthlyPrice($plan) : $plan->priceFor('month');
    }

    /** "Launch offer: 50% off your first 3 months, sign up by 10 Feb 2027" */
    public function offerLine(): ?string
    {
        if (! $this->offerActive()) {
            return null;
        }

        return "Launch offer: {$this->offer->percentOff()}% off your first {$this->offer->months()} months, sign up by {$this->offer->endsLabel()}";
    }

    public function metaDescription(): string
    {
        $symbol = Currency::symbol();
        $text = 'Portfolio websites and private client galleries for photographers and videographers.';

        if ($line = $this->offerLine()) {
            return "{$text} {$line}. Plans from {$symbol}{$this->fromPrice()}/month.";
        }

        return $this->fromPrice()
            ? "{$text} Start free, no card needed. Paid plans from {$symbol}{$this->fromPrice()}/month."
            : "{$text} Start free, no card needed.";
    }

    /** @return list<array{q: string, a: string}> the visible FAQ, in order */
    public function faqs(): array
    {
        $symbol = Currency::symbol();
        $faqs = [];

        if ($this->offerActive() && $this->paidPlans()->isNotEmpty()) {
            $prices = $this->paidPlans()
                ->map(fn ($p) => "{$p->name} is {$symbol}{$this->offer->discountedMonthlyPrice($p)}/month instead of {$symbol}{$p->priceFor('month')}")
                ->implode(', and ');
            $saving = (int) $this->paidPlans()->max('annual_discount_percent');
            $faqs[] = [
                'q' => 'Is there a launch discount?',
                'a' => "Yes. Sign up for a monthly paid plan by {$this->offer->endsLabel()} and get {$this->offer->percentOff()}% off your first {$this->offer->months()} months: {$prices}. "
                    ."After {$this->offer->months()} months you pay the normal monthly price, and you can cancel any time."
                    .($saving ? " Yearly plans already save {$saving}% and aren't part of the offer." : ''),
            ];
        }

        $faqs[] = [
            'q' => 'Do I need a card to start?',
            'a' => 'No. The free plan needs no card. You only add one if you upgrade to a paid plan.',
        ];
        $faqs[] = [
            'q' => 'Can I cancel any time?',
            'a' => "Yes. Change or cancel your plan whenever you like. If you cancel, you keep your plan until the end of the period you've paid for.",
        ];
        $faqs[] = [
            'q' => 'Can my clients see their photos privately?',
            'a' => 'Yes. On a paid plan you can make any gallery private and protect it with a password, so only the clients you share it with can see it.',
        ];
        $faqs[] = [
            'q' => 'Can I protect my photos with a watermark?',
            'a' => 'Yes. Paid plans add your own watermark to photos in your galleries, and you can add your logo to your portfolio.',
        ];
        $faqs[] = [
            'q' => 'Is video supported?',
            'a' => "Video galleries are coming soon on the Videographer plan. For now, every plan shows photos.",
        ];
        $faqs[] = [
            'q' => 'Can my clients buy prints?',
            'a' => "Print sales are coming soon. When they're ready, clients will order prints from your galleries and you'll be paid the price you set, minus the cost of printing and delivery and our fee.",
        ];

        return $faqs;
    }

    /** @return list<array<string, mixed>> JSON-LD blocks for the page */
    public function structuredData(string $url, ?string $image = null): array
    {
        $currency = Currency::code();
        $offers = [];

        foreach ($this->paidPlans() as $plan) {
            $offers[] = [
                '@type' => 'Offer',
                'name' => "{$plan->name} plan (monthly)",
                'price' => $plan->priceFor('month'),
                'priceCurrency' => $currency,
                'url' => route('pricing'),
                'availability' => 'https://schema.org/InStock',
            ];
            if ($this->offerActive()) {
                $offers[] = [
                    '@type' => 'Offer',
                    'name' => "{$plan->name} plan launch offer",
                    'description' => "{$this->offer->percentOff()}% off the first {$this->offer->months()} months",
                    'price' => $this->offer->discountedMonthlyPrice($plan),
                    'priceCurrency' => $currency,
                    'priceValidUntil' => $this->offer->endsAt()->setTimezone('Europe/London')->toDateString(),
                    'url' => route('pricing'),
                    'availability' => 'https://schema.org/InStock',
                ];
            }
        }

        $app = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => config('app.name'),
            'url' => $url,
            'image' => $image,
            'description' => $this->metaDescription(),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'offers' => $offers ?: null,
        ]);

        $faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $this->faqs()),
        ];

        return [$app, $faq];
    }
}

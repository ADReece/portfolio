<?php

namespace App\Billing;

use App\Models\SubscriptionPlan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Laravel\Cashier\SubscriptionBuilder;

/**
 * The launch offer (config/billing.php): a percentage off the first few
 * months of a monthly subscription, while now is before the end date and a
 * coupon id is configured. Yearly plans keep their normal yearly saving,
 * because a repeating coupon on a yearly invoice would discount the whole year.
 */
class LaunchOffer
{
    public function couponId(): ?string
    {
        $id = trim((string) config('billing.launch_offer.coupon_id'));

        return $id === '' ? null : $id;
    }

    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(config('billing.launch_offer.ends_at'));
    }

    public function isActive(?CarbonInterface $now = null): bool
    {
        return $this->couponId() !== null && ($now ?? CarbonImmutable::now())->lessThanOrEqualTo($this->endsAt());
    }

    /** The coupon to attach to a new subscription at this interval, if any. */
    public function couponFor(string $interval, ?CarbonInterface $now = null): ?string
    {
        return $interval === 'month' && $this->isActive($now) ? $this->couponId() : null;
    }

    /** Attaches the coupon to a new subscription when the offer applies. */
    public function apply(SubscriptionBuilder $builder, string $interval, ?CarbonInterface $now = null): SubscriptionBuilder
    {
        if ($coupon = $this->couponFor($interval, $now)) {
            $builder->withCoupon($coupon);
        }

        return $builder;
    }

    /** Whether the offer should be shown for this plan and interval. */
    public function appliesTo(SubscriptionPlan $plan, string $interval = 'month', ?CarbonInterface $now = null): bool
    {
        return ! $plan->isFree() && (float) $plan->price > 0 && $this->couponFor($interval, $now) !== null;
    }

    public function percentOff(): int
    {
        return (int) config('billing.launch_offer.percent_off');
    }

    public function months(): int
    {
        return (int) config('billing.launch_offer.months');
    }

    /**
     * Monthly price during the offer, e.g. "1.99" for a 3.99 plan at 50% off:
     * the discount is rounded half up to the penny (199.5p -> 200p off), as
     * Stripe does when it applies a percentage coupon.
     */
    public function discountedMonthlyPrice(SubscriptionPlan $plan): string
    {
        $pence = (int) round((float) $plan->price * 100);
        $discounted = $pence - (int) round($pence * $this->percentOff() / 100);

        return number_format($discounted / 100, 2, '.', '');
    }

    /** "10 Feb 2027", the last day of the offer in UK time. */
    public function endsLabel(): string
    {
        return $this->endsAt()->setTimezone('Europe/London')->format('j M Y');
    }

    /** Parameters for the Stripe coupon (created by stripe:sync-plans). */
    public function stripeCouponParams(): array
    {
        return [
            'id' => $this->couponId(),
            'name' => config('billing.launch_offer.name'),
            'percent_off' => $this->percentOff(),
            'duration' => 'repeating',
            'duration_in_months' => $this->months(),
            // Redeemable up to the end of the last offer day.
            'redeem_by' => $this->endsAt()->addSecond()->getTimestamp(),
        ];
    }
}

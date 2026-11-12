<?php

namespace Tests\Support;

use Stripe\StripeObject;

/**
 * Stands in for Portfolio's Connect StripeClient ('stripe.connect'): records
 * every call and returns just enough for the code under test.
 */
class FakeConnectStripe
{
    /** @var list<array{0: string, 1: mixed, 2: mixed}> */
    public array $calls = [];

    /** What accounts->retrieve returns. */
    public array $account = ['charges_enabled' => true, 'payouts_enabled' => true];

    public bool $fail = false;

    public object $accounts;

    public object $accountLinks;

    public object $checkout;

    public function __construct()
    {
        $fake = $this;
        $this->accounts = new class($fake) {
            public function __construct(private FakeConnectStripe $fake) {}

            public function create(array $params, array $opts = [])
            {
                return $this->fake->record('accounts.create', $params, $opts, ['id' => 'acct_new']);
            }

            public function retrieve(string $id)
            {
                return $this->fake->record('accounts.retrieve', $id, null, ['id' => $id] + $this->fake->account);
            }

            public function createLoginLink(string $id)
            {
                return $this->fake->record('accounts.createLoginLink', $id, null, ['url' => 'https://connect.stripe.test/express/'.$id]);
            }
        };
        $this->accountLinks = new class($fake) {
            public function __construct(private FakeConnectStripe $fake) {}

            public function create(array $params)
            {
                return $this->fake->record('accountLinks.create', $params, null, ['url' => 'https://connect.stripe.test/setup/'.$params['account']]);
            }
        };
        $this->checkout = (object) ['sessions' => new class($fake) {
            public function __construct(private FakeConnectStripe $fake) {}

            public function create(array $params)
            {
                return $this->fake->record('checkout.sessions.create', $params, null, ['id' => 'cs_test', 'url' => 'https://checkout.stripe.test/cs_test']);
            }
        }];
    }

    public function record(string $method, mixed $params, mixed $opts, array $result): StripeObject
    {
        if ($this->fail) {
            throw new \Stripe\Exception\ApiConnectionException('Stripe is down');
        }
        $this->calls[] = [$method, $params, $opts];

        return StripeObject::constructFrom($result);
    }

    /** @return list<mixed> the params of each call to $method */
    public function paramsFor(string $method): array
    {
        return array_values(array_map(fn ($c) => $c[1], array_filter($this->calls, fn ($c) => $c[0] === $method)));
    }

    /** Sets the Connect keys and swaps this fake in. */
    public static function install(\Illuminate\Contracts\Foundation\Application $app): self
    {
        config([
            'services.portfolio_connect.secret' => 'sk_test_connect',
            'services.portfolio_connect.webhook_secret' => 'whsec_test_connect',
        ]);
        $fake = new self;
        $app->instance('stripe.connect', $fake);

        return $fake;
    }
}

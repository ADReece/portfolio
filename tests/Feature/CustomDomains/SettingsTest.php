<?php

namespace Tests\Feature\CustomDomains;

use App\Models\CustomDomain;

class SettingsTest extends CustomDomainTestCase
{
    public function test_add_a_domain_and_see_the_dns_records(): void
    {
        $steph = $this->photographer();

        $this->actingAs($steph)->post(route('custom-domains.store'), ['hostname' => 'StephShoots.com'])
            ->assertRedirect(route('custom-domains.index'));

        $domain = $steph->customDomains()->sole();
        $this->assertSame('stephshoots.com', $domain->hostname);
        $this->assertTrue($domain->include_www);
        $this->assertSame('pending', $domain->status);

        $this->actingAs($steph)->get(route('custom-domains.index'))->assertOk()
            ->assertSee('_portfolio-verify.stephshoots.com')
            ->assertSee($domain->verification_token)
            ->assertSee('2.28.38.210')
            ->assertSee('Waiting for DNS');
    }

    public function test_verify_button_marks_it_live(): void
    {
        $steph = $this->photographer();
        $domain = CustomDomain::factory()->create(['user_id' => $steph->id, 'hostname' => 'stephshoots.com']);
        $this->pointDns($domain);

        $this->actingAs($steph)->post(route('custom-domains.verify', $domain))->assertSessionHas('success');
        $this->assertTrue($domain->fresh()->isVerified());
    }

    public function test_plan_limits(): void
    {
        $free = $this->photographer('freebie', 'free');
        $this->actingAs($free)->post(route('custom-domains.store'), ['hostname' => 'freebie.com'])->assertSessionHasErrors('hostname');
        $this->actingAs($free)->get(route('custom-domains.index'))->assertSee('Custom domains are included on paid plans');

        $steph = $this->photographer();
        $this->actingAs($steph)->post(route('custom-domains.store'), ['hostname' => 'one.com']);
        $this->actingAs($steph)->post(route('custom-domains.store'), ['hostname' => 'two.com'])->assertSessionHasErrors('hostname');
        $this->assertSame(1, $steph->customDomains()->count());

        config(['custom_domains.plans' => ['photographer' => 2]]);
        $this->actingAs($steph)->post(route('custom-domains.store'), ['hostname' => 'two.com'])->assertSessionHasNoErrors();
    }

    public function test_a_domain_can_only_belong_to_one_portfolio_and_must_be_valid(): void
    {
        $this->liveDomain($this->photographer('ava'), 'taken.com');
        $steph = $this->photographer();

        foreach (['taken.com', 'www.taken.com', 'portfolio.ascend-digital.co.uk', '1.2.3.4', 'not a domain'] as $bad) {
            $this->actingAs($steph)->post(route('custom-domains.store'), ['hostname' => $bad])->assertSessionHasErrors('hostname');
        }
    }

    public function test_only_the_owner_can_change_or_remove_it(): void
    {
        $domain = $this->liveDomain($this->photographer('ava'), 'avaphoto.com');
        $steph = $this->photographer();

        $this->actingAs($steph)->delete(route('custom-domains.destroy', $domain))->assertNotFound();
        $this->actingAs($steph)->post(route('custom-domains.verify', $domain))->assertNotFound();
        $this->assertModelExists($domain);
    }

    public function test_adding_www_to_a_live_domain_needs_checking_again(): void
    {
        $steph = $this->photographer();
        $domain = $this->liveDomain($steph, 'stephshoots.com', ['include_www' => false]);

        $this->actingAs($steph)->patch(route('custom-domains.update', $domain), ['include_www' => '1', 'primary_www' => '0']);

        $this->assertSame('pending', $domain->fresh()->status);
    }
}

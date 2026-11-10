<?php

namespace Tests\Feature\CustomDomains;

class HostRoutingTest extends CustomDomainTestCase
{
    public function test_portfolio_is_served_at_the_root_of_its_domain_with_clean_urls(): void
    {
        $steph = $this->photographer();
        $collection = $this->publicCollection($steph);
        $this->liveDomain($steph);

        $html = $this->get('https://stephshoots.com/')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="https://stephshoots.com"', $html);
        $this->assertStringNotContainsString('/@stephshoots', $html);

        $this->get('https://stephshoots.com/collections')->assertOk()->assertSee('Spring Wedding');
        $this->get("https://stephshoots.com/collections/{$collection->id}")->assertOk()
            ->assertSee('<link rel="canonical" href="https://stephshoots.com/collections/'.$collection->id.'"', false);
    }

    public function test_unknown_and_unverified_hosts_get_404_never_the_marketing_site(): void
    {
        $this->get('https://random-site.com/')->assertNotFound()->assertDontSee('Launch offer');

        $steph = $this->photographer();
        $this->liveDomain($steph, 'pending-photos.com', ['status' => 'pending', 'verified_at' => null]);
        $this->get('https://pending-photos.com/')->assertNotFound();
    }

    public function test_domain_stops_serving_when_the_plan_no_longer_includes_it(): void
    {
        $free = $this->photographer('freebie', 'free');
        $this->liveDomain($free, 'freebie.com');

        $this->get('https://freebie.com/')->assertNotFound();
    }

    public function test_www_and_apex_redirect_to_the_primary(): void
    {
        $this->liveDomain($this->photographer());
        $this->get('https://www.stephshoots.com/collections?page=2')->assertStatus(301)
            ->assertRedirect('https://stephshoots.com/collections?page=2');

        $this->liveDomain($this->photographer('ava'), 'avaphoto.com', ['primary_www' => true]);
        $this->get('https://avaphoto.com/')->assertStatus(301)->assertRedirect('https://www.avaphoto.com/');
        $this->get('https://www.avaphoto.com/')->assertOk();
    }

    public function test_platform_url_redirects_to_the_custom_domain(): void
    {
        $steph = $this->photographer();
        $collection = $this->publicCollection($steph);
        $this->liveDomain($steph);

        $this->get('https://portfolio.ascend-digital.co.uk/@stephshoots')->assertStatus(301)->assertRedirect('https://stephshoots.com/');
        $this->get("https://portfolio.ascend-digital.co.uk/@stephshoots/collections/{$collection->id}")
            ->assertStatus(301)->assertRedirect("https://stephshoots.com/collections/{$collection->id}");

        // Without a live domain the platform URL works as before.
        $this->get('https://portfolio.ascend-digital.co.uk/@'.$this->photographer('ava')->username)->assertOk();
    }

    public function test_account_pages_on_a_custom_domain_go_to_the_main_site(): void
    {
        $this->liveDomain($this->photographer());

        $this->get('https://stephshoots.com/login')->assertRedirect('https://portfolio.ascend-digital.co.uk/login');
        $this->get('https://stephshoots.com/dashboard')->assertRedirect('https://portfolio.ascend-digital.co.uk/dashboard');
        $this->get('https://stephshoots.com/pricing')->assertRedirect('https://portfolio.ascend-digital.co.uk/pricing');
    }

    public function test_private_gallery_unlock_works_on_the_custom_domain(): void
    {
        $steph = $this->photographer();
        $collection = \App\Models\Collection::factory()->private()->published()->create(['user_id' => $steph->id]);
        $this->liveDomain($steph);

        $this->post("https://stephshoots.com/collections/{$collection->id}/unlock", ['password' => 'password123'])
            ->assertRedirect("https://stephshoots.com/collections/{$collection->id}");
    }

    public function test_robots_and_sitemap_are_per_domain(): void
    {
        $steph = $this->photographer();
        $collection = $this->publicCollection($steph);
        $this->liveDomain($steph);

        $this->get('https://stephshoots.com/robots.txt')->assertOk()->assertSee('Sitemap: https://stephshoots.com/sitemap.xml');
        $this->get('https://stephshoots.com/sitemap.xml')->assertOk()
            ->assertSee('<loc>https://stephshoots.com</loc>', false)
            ->assertSee("<loc>https://stephshoots.com/collections/{$collection->id}</loc>", false)
            ->assertDontSee('pricing');

        $this->get('https://portfolio.ascend-digital.co.uk/sitemap.xml')->assertOk()->assertSee('/pricing</loc>', false);
        $this->get('https://portfolio.ascend-digital.co.uk/robots.txt')->assertOk()->assertSee('Disallow:');
    }

    public function test_platform_hosts_are_unaffected(): void
    {
        $this->get('https://portfolio.ascend-digital.co.uk/')->assertOk();
        $this->get('http://localhost/up')->assertOk();
        $this->get('http://10.0.1.5/up')->assertOk();
        $this->get('http://web-abc123:8080/up')->assertOk();
    }
}

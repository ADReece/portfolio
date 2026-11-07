<?php

namespace Tests\Feature\Ux;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_missing_page_shows_friendly_404(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee("We can't find that page");
    }
}

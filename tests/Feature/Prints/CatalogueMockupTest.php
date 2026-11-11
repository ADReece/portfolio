<?php

namespace Tests\Feature\Prints;

use App\Models\ProdigiProduct;
use App\Prints\MockupGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogueMockupTest extends PrintCatalogueTestCase
{
    private function product(string $sku, string $category, array $payload = []): ProdigiProduct
    {
        return ProdigiProduct::create([
            'sku' => $sku, 'name' => $sku, 'category' => $category, 'is_active' => true,
            'raw_payload' => $payload + ['sku' => $sku],
        ]);
    }

    public function test_style_shape_and_frame_colour_come_from_the_product(): void
    {
        $mockups = app(MockupGenerator::class);

        $mounted = $this->product('GLOBAL-CFPM-16X20', 'Framed', ['attributes' => ['color' => ['white', 'black']]]);
        $this->assertSame('framed_mount', $mockups->kind($mounted));
        $this->assertEqualsWithDelta(0.8, $mockups->aspect($mounted), 0.001);
        $this->assertSame('#f3f2ef', $mockups->frameColour($mounted));

        $this->assertSame('canvas', $mockups->kind($this->product('GLOBAL-CAN-30X20', 'Canvas')));
        $this->assertEqualsWithDelta(1.5, $mockups->aspect($this->product('GLOBAL-CAN-30X21', 'Canvas', ['productDimensions' => ['width' => 30, 'height' => 20, 'units' => 'in']])), 0.001, 'Prodigi dimensions win');
        $this->assertEqualsWithDelta(1 / M_SQRT2, $mockups->aspect($this->product('GLOBAL-FAP-A4', 'Fine art prints')), 0.001);
        $this->assertSame('print', $mockups->kind($this->product('GLOBAL-FAP-8X10', 'Fine art prints')));
        $this->assertSame('panel', $mockups->kind($this->product('GLOBAL-ACRY-8X10', 'Wall art')));
        $this->assertNull($mockups->kind($this->product('GLOBAL-MUG-11OZ', 'Gifts')));
    }

    public function test_products_of_the_same_shape_share_one_mockup_file(): void
    {
        $a = $this->product('GLOBAL-CAN-16X20', 'Canvas');
        $b = $this->product('GLOBAL-SLIMCAN-16X20', 'Canvas');
        $c = $this->product('GLOBAL-CAN-20X16', 'Canvas');

        $this->artisan('prodigi:mockups')->expectsOutputToContain('Mockups for 3 products')->assertSuccessful();

        $this->assertSame($a->fresh()->mockup_path, $b->fresh()->mockup_path);
        $this->assertNotSame($a->fresh()->mockup_path, $c->fresh()->mockup_path, 'landscape differs');
        Storage::disk('s3')->assertExists($a->fresh()->mockup_path);
        $image = new \Imagick();
        $image->readImageBlob(Storage::disk('s3')->get($a->fresh()->mockup_path));
        $this->assertSame([MockupGenerator::SIZE, MockupGenerator::SIZE], [$image->getImageWidth(), $image->getImageHeight()]);
    }

    public function test_uploaded_images_win_and_products_without_a_style_keep_the_drawing(): void
    {
        $canvas = $this->product('GLOBAL-CAN-16X20', 'Canvas');
        $mug = $this->product('GLOBAL-MUG-11OZ', 'Gifts');
        $this->artisan('prodigi:mockups')->assertSuccessful();

        $this->assertStringContainsString('catalogue/mockups/', $canvas->fresh()->imageUrl());
        $this->assertNull($mug->fresh()->imageUrl());

        $this->actingAs($this->admin())->put(route('admin.print-catalogue.update', $canvas), [
            'name' => 'Canvas', 'category' => 'Canvas', 'sort_order' => 0, 'image' => UploadedFile::fake()->image('real.jpg', 300, 300),
        ])->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('catalogue/mockups/', $canvas->fresh()->imageUrl());

        $this->actingAs($this->admin())->get(route('admin.print-catalogue.edit', $mug))->assertOk()->assertDontSee('Remove image');
    }

    public function test_command_only_fills_gaps_unless_asked_to_redraw(): void
    {
        $canvas = $this->product('GLOBAL-CAN-16X20', 'Canvas');
        $canvas->forceFill(['mockup_path' => 'catalogue/mockups/old.jpg'])->save();

        $this->artisan('prodigi:mockups')->expectsOutputToContain('Mockups for 0 products')->assertSuccessful();
        $this->assertSame('catalogue/mockups/old.jpg', $canvas->fresh()->mockup_path);

        $this->artisan('prodigi:mockups', ['--all' => true])->expectsOutputToContain('Mockups for 1 product ')->assertSuccessful();
        $this->assertNotSame('catalogue/mockups/old.jpg', $canvas->fresh()->mockup_path);
    }

    public function test_admin_button_queues_mockups_and_imports_get_one(): void
    {
        $canvas = $this->product('GLOBAL-CAN-16X20', 'Canvas');
        $this->actingAs($this->admin())->post(route('admin.print-catalogue.mockups'))
            ->assertSessionHas('success', 'Drawing mockups for 1 product in the background.');
        $this->assertNotNull($canvas->fresh()->mockup_path); // the queue is synchronous in tests

        $this->fakeProdigi('GLOBAL-CFPM-16X20');
        $this->actingAs($this->admin())->post(route('admin.print-catalogue.store-many'), ['skus' => 'GLOBAL-CFPM-16X20']);
        $this->assertNotNull(ProdigiProduct::where('sku', 'GLOBAL-CFPM-16X20')->value('mockup_path'));
    }
}

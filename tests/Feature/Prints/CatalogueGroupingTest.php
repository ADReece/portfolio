<?php

namespace Tests\Feature\Prints;

use App\Models\ProdigiProduct;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogueGroupingTest extends PrintCatalogueTestCase
{
    private function product(string $sku, array $attrs = []): ProdigiProduct
    {
        return ProdigiProduct::create($attrs + [
            'sku' => $sku,
            'name' => $sku,
            'category' => 'Canvas',
            'is_active' => false,
            'raw_payload' => ['sku' => $sku],
        ]);
    }

    public function test_skus_group_by_product_without_their_size(): void
    {
        $this->assertSame('GLOBAL-CAN', (new ProdigiProduct(['sku' => 'GLOBAL-CAN-16X20']))->groupKey());
        $this->assertSame('GLOBAL-FAP', (new ProdigiProduct(['sku' => 'global-fap-a4']))->groupKey());
        $this->assertSame('GLOBAL-CFPM', (new ProdigiProduct(['sku' => 'GLOBAL-CFPM-11.7X16.5']))->groupKey());
        $this->assertSame('GLOBAL-MUG', (new ProdigiProduct(['sku' => 'GLOBAL-MUG-11OZ']))->groupKey());
        $this->assertSame('ODD', (new ProdigiProduct(['sku' => 'ODD']))->groupKey());

        $this->assertSame('Canvas', ProdigiProduct::groupLabel('GLOBAL-CAN', collect()));
        $this->assertSame('Metal print, gloss', ProdigiProduct::groupLabel('X-MET', collect([new ProdigiProduct(['name' => 'Metal print, 16x20", gloss'])])));
    }

    public function test_catalogue_lists_categories_then_products_with_sizes_smallest_first(): void
    {
        $this->product('GLOBAL-CAN-16X20');
        $this->product('GLOBAL-CAN-8X10');
        $this->product('GLOBAL-CFPM-16X20', ['category' => 'Framed']);

        $html = $this->actingAs($this->admin())->get(route('admin.print-catalogue.index'))->assertOk()
            ->assertSee('Select every size of Canvas')
            ->assertSee('Classic frames with mount')
            ->assertSee('2 sizes · 0 active', false)
            ->getContent();

        $this->assertLessThan(strpos($html, 'GLOBAL-CAN-16X20'), strpos($html, 'GLOBAL-CAN-8X10'), '8x10 is listed before 16x20');
        $this->assertLessThan(strpos($html, 'id="cat-framed"'), strpos($html, 'id="cat-canvas"'), 'categories in order');
    }

    public function test_bulk_status_updates_the_chosen_products(): void
    {
        $a = $this->product('GLOBAL-CAN-8X10');
        $b = $this->product('GLOBAL-CAN-16X20');
        $c = $this->product('GLOBAL-CAN-20X30');

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.bulk-status'), ['ids' => [$a->id, $b->id], 'status' => 'active'])
            ->assertSessionHas('success', '2 products now offered to photographers.');
        $this->assertSame([true, true, false], [$a->fresh()->is_active, $b->fresh()->is_active, $c->fresh()->is_active]);

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.bulk-status'), ['ids' => [$a->id], 'status' => 'inactive'])->assertSessionHas('success');
        $this->assertFalse($a->fresh()->is_active);

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.bulk-status'), ['ids' => [], 'status' => 'active'])->assertSessionHasErrors('ids');
        $this->actingAs($this->admin())->post(route('admin.print-catalogue.bulk-status'), ['ids' => [$a->id], 'status' => 'deleted'])->assertSessionHasErrors('status');
        $this->actingAs(User::factory()->create())->post(route('admin.print-catalogue.bulk-status'), ['ids' => [$a->id], 'status' => 'active'])->assertForbidden();
    }

    public function test_group_header_offers_activate_all_until_every_size_is_active(): void
    {
        $this->product('GLOBAL-CAN-8X10', ['is_active' => true]);
        $this->product('GLOBAL-CAN-16X20');

        $this->actingAs($this->admin())->get(route('admin.print-catalogue.index'))->assertSee('Activate all')->assertDontSee('Deactivate all');
        ProdigiProduct::query()->update(['is_active' => true]);
        $this->actingAs($this->admin())->get(route('admin.print-catalogue.index'))->assertSee('Deactivate all');
    }

    public function test_one_image_for_every_size_of_a_product_and_shared_files_are_kept(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('catalogue/own.jpg', 'own');
        $small = $this->product('GLOBAL-CAN-8X10', ['image_path' => 'catalogue/own.jpg']);
        $large = $this->product('GLOBAL-CAN-16X20');
        $other = $this->product('GLOBAL-CANVAS-PRO-8X10');

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.group-image'), [
            'group' => 'GLOBAL-CAN', 'image' => UploadedFile::fake()->image('canvas.jpg', 400, 400),
        ])->assertSessionHas('success', 'Image set for 1 size.');
        $shared = $large->fresh()->image_path;
        $this->assertNotNull($shared);
        $this->assertSame('catalogue/own.jpg', $small->fresh()->image_path, 'sizes keep their own image');
        $this->assertNull($other->fresh()->image_path, 'only this group');

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.group-image'), [
            'group' => 'GLOBAL-CAN', 'image' => UploadedFile::fake()->image('canvas2.jpg', 400, 400), 'replace' => '1',
        ])->assertSessionHas('success', 'Image set for 2 sizes.');
        $newShared = $small->fresh()->image_path;
        $this->assertSame($newShared, $large->fresh()->image_path);
        Storage::disk('s3')->assertMissing('catalogue/own.jpg');
        Storage::disk('s3')->assertMissing($shared);

        // Removing one size's image keeps the file the other size still uses.
        $this->actingAs($this->admin())->put(route('admin.print-catalogue.update', $small), [
            'name' => 'Small', 'category' => 'Canvas', 'sort_order' => 0, 'remove_image' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertNull($small->fresh()->image_path);
        Storage::disk('s3')->assertExists($newShared);
    }

    public function test_find_images_reports_image_fields_in_stored_prodigi_data(): void
    {
        $this->product('GLOBAL-CAN-8X10', ['raw_payload' => ['sku' => 'GLOBAL-CAN-8X10', 'description' => 'Canvas']]);
        $this->artisan('prodigi:find-images')->expectsOutputToContain('no image URLs')->assertSuccessful();

        $this->product('GLOBAL-CAN-16X20', ['raw_payload' => ['sku' => 'GLOBAL-CAN-16X20', 'variants' => [['imageUrls' => ['https://img.example/a.jpg']]]]]);
        $this->artisan('prodigi:find-images')->expectsOutputToContain('1 have image-like fields')->assertSuccessful();
    }

    public function test_products_without_an_image_get_a_category_drawing(): void
    {
        $this->product('GLOBAL-CAN-8X10', ['is_active' => true]);
        $html = $this->actingAs($this->admin())->get(route('admin.print-catalogue.index'))->getContent();
        $this->assertMatchesRegularExpression('/<svg viewBox="0 0 48 48"\s+aria-hidden="true"/', $html);
    }
}

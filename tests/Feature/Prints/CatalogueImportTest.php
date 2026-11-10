<?php

namespace Tests\Feature\Prints;

use App\Jobs\ImportProdigiProduct;
use App\Models\ProdigiImport;
use App\Models\ProdigiProduct;
use App\Models\User;
use App\Prints\CatalogueImporter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class CatalogueImportTest extends PrintCatalogueTestCase
{
    private function fakeKnown(array $skus): void
    {
        $fakes = [];
        foreach ($skus as $sku) {
            $fakes[self::BASE.'/products/'.$sku] = Http::response($this->productPayload($sku, []));
        }
        Http::fake($fakes + [
            self::BASE.'/products/*' => Http::response(['outcome' => 'NotFound'], 404),
            self::BASE.'/quotes' => Http::response($this->quotePayload()),
        ]);
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_csv_with_a_header_below_title_rows_uses_its_sku_name_and_category_columns(): void
    {
        $path = $this->csv("\xEF\xBB\xBFProdigi price sheet,,\nGenerated 10/11/2026,,\n"
            ."Product SKU;Product name;Category;Price (GBP)\n"
            ."global-fap-16x24;Fine art print 16x24;Fine art prints;12.50\n"
            ."GLOBAL-CAN-16X20;Canvas;canvas;30\n"
            ."GLOBAL-FAP-16X24;Duplicate row;;12.50\n"
            .";;;\n");

        $entries = CatalogueImporter::skusFromCsv($path);

        $this->assertSame(['GLOBAL-FAP-16X24', 'GLOBAL-CAN-16X20'], array_keys($entries));
        $this->assertSame(['name' => 'Fine art print 16x24', 'category' => 'Fine art prints'], $entries['GLOBAL-FAP-16X24']);
        $this->assertSame(['name' => 'Canvas', 'category' => 'Canvas'], $entries['GLOBAL-CAN-16X20']);
    }

    public function test_csv_without_a_sku_header_picks_out_sku_shaped_cells(): void
    {
        $path = $this->csv("Item,Code,Cost\nFrame,GLOBAL-CFPM-16X20,20\nCanvas,global-can-10x10,9\nNote,not-a-sku,1\n");

        $this->assertSame(['GLOBAL-CFPM-16X20', 'GLOBAL-CAN-10X10'], array_keys(CatalogueImporter::skusFromCsv($path)));
    }

    public function test_discovery_candidates_cover_both_orientations_and_paper_sizes(): void
    {
        config(['prints.discovery.inch_sizes' => ['8X10', '12X12'], 'prints.discovery.paper_sizes' => ['A4']]);

        $this->assertSame(
            ['GLOBAL-FAP-8X10', 'GLOBAL-FAP-10X8', 'GLOBAL-FAP-12X12', 'GLOBAL-FAP-A4'],
            array_keys(CatalogueImporter::discoveryCandidates(['FAP']))
        );
    }

    public function test_admin_csv_upload_adds_found_products_as_inactive(): void
    {
        $this->catalogueProduct(['sku' => 'GLOBAL-CFPM-16X20']);
        $this->fakeKnown(['GLOBAL-FAP-16X24']);
        $file = UploadedFile::fake()->createWithContent('prices.csv', "SKU,Name\nGLOBAL-FAP-16X24,My fine art print\nGLOBAL-CFPM-16X20,Frame\nGLOBAL-NOPE-1,x\n");

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.import'), ['file' => $file])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Checking 2 SKUs'));

        $product = ProdigiProduct::where('sku', 'GLOBAL-FAP-16X24')->sole();
        $this->assertSame('My fine art print', $product->name);
        $this->assertSame('Fine art prints', $product->category);
        $this->assertFalse($product->is_active);
        $this->assertSame('10.00', $product->cost_item);

        $import = ProdigiImport::sole();
        $this->assertSame('prices.csv', $import->label);
        $this->assertSame([1, 1, 1], [$import->added, $import->skipped, $import->not_found]);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/orders'));
    }

    public function test_csv_with_no_skus_is_refused(): void
    {
        $file = UploadedFile::fake()->createWithContent('empty.csv', "Name,Price\nThing,1\n");

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.import'), ['file' => $file])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, ProdigiImport::count());
    }

    public function test_discover_queues_a_lookup_per_candidate_spread_out(): void
    {
        Queue::fake();
        config(['prints.discovery.inch_sizes' => ['8X10'], 'prints.discovery.paper_sizes' => ['A4'], 'prints.import_per_minute' => 60]);

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.discover'), ['families' => ['FAP', 'CAN']])
            ->assertSessionHas('success');

        Queue::assertPushed(ImportProdigiProduct::class, 6);
        Queue::assertPushed(ImportProdigiProduct::class, fn ($job) => $job->sku === 'GLOBAL-CAN-A4');
        $this->assertSame('Discover: FAP, CAN', ProdigiImport::sole()->label);

        $this->actingAs($this->admin())->get(route('admin.print-catalogue.index'))
            ->assertSee('Checking: 0 of 6');
    }

    public function test_discover_only_accepts_known_families_and_is_admin_only(): void
    {
        $this->actingAs($this->admin())->post(route('admin.print-catalogue.discover'), ['families' => ['EVIL']])
            ->assertSessionHasErrors('families.0');
        $this->actingAs(User::factory()->create())->post(route('admin.print-catalogue.discover'), ['families' => ['FAP']])
            ->assertForbidden();
    }

    public function test_a_job_that_cannot_reach_prodigi_retries_then_counts_as_failed(): void
    {
        Http::fake([self::BASE.'/products/*' => Http::response(['outcome' => 'Error'], 503)]);
        $import = ProdigiImport::create(['source' => 'paste', 'total' => 1]);
        $job = new ImportProdigiProduct($import->id, 'GLOBAL-FAP-16X24');

        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected the lookup to throw so the queue retries it.');
        } catch (\Exception) {
        }
        $job->failed(new \RuntimeException('HTTP 503'));

        $import->refresh();
        $this->assertSame(1, $import->failed);
        $this->assertSame(['GLOBAL-FAP-16X24'], $import->failed_skus);
        $this->assertTrue($import->isFinished());
        $this->assertSame(0, ProdigiProduct::count());
    }

    public function test_command_discovers_now_and_reports(): void
    {
        config(['prints.discovery.inch_sizes' => ['8X10'], 'prints.discovery.paper_sizes' => [], 'prints.import_per_minute' => 6000]);
        $this->fakeKnown(['GLOBAL-FAP-8X10']);

        $this->artisan('prodigi:import', ['--discover' => true, '--family' => ['fap'], '--now' => true])
            ->expectsOutputToContain('Added 1, already there 0, not on Prodigi 1, failed 0.')
            ->assertSuccessful();

        $this->assertTrue(ProdigiProduct::where('sku', 'GLOBAL-FAP-8X10')->exists());
        $this->artisan('prodigi:import', ['--discover' => true, '--family' => ['NOPE']])->assertFailed();
    }

    public function test_command_queues_a_csv(): void
    {
        Queue::fake();
        $path = $this->csv("SKU\nGLOBAL-FAP-16X24\nGLOBAL-CAN-16X20\n");

        $this->artisan('prodigi:import', ['file' => $path])->expectsOutputToContain('2 SKUs queued')->assertSuccessful();
        Queue::assertPushed(ImportProdigiProduct::class, 2);
    }
}

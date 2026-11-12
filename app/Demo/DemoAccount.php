<?php

namespace App\Demo;

use App\Jobs\ProcessImageUpload;
use App\Models\Collection;
use App\Models\Font;
use App\Models\GeneratedPrint;
use App\Models\Order;
use App\Models\Photo;
use App\Models\ProdigiProduct;
use App\Models\Set;
use App\Models\SubscriptionPlan;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The public demo photographer, showing every client-facing feature: a
 * branded portfolio, public collections with sets, a password-protected client
 * gallery, watermarking, free downloads with a design (template), photos for
 * sale (per photo and bundles) and the print shop.
 *
 * Seeding is idempotent: the account, collections and sets are updated in
 * place, photos are kept once fetched (keyed by their Unsplash id) and only
 * missing ones are added. The demo can't log in, is never charged, and its
 * checkouts and emails are switched off (see User::isDemo() checks).
 */
class DemoAccount
{
    /** @var callable(string): void */
    private $log;

    public function __construct()
    {
        $this->log = fn () => null;
    }

    public static function user(): ?User
    {
        return User::query()->where('is_demo', true)->first();
    }

    /**
     * Links for the demo's "things to try" panel.
     *
     * @return array{weddings: ?string, client: ?string, portraits: ?string, landscapes: ?string, credits: string}
     */
    public static function tryLinks(User $user): array
    {
        $byName = $user->collections()->get()->keyBy('name');
        $link = fn (string $name) => ($c = $byName->get($name))
            ? route('profile.collection', ['username' => $user->username, 'collection_id' => $c->id])
            : null;

        return [
            'weddings' => $link('Weddings'),
            'client' => $link('Harper & Jordan: client gallery'),
            'portraits' => $link('Portraits'),
            'landscapes' => $link('Lake District landscapes'),
            'credits' => route('profile.photo-credits', $user->username),
        ];
    }

    /**
     * @param  callable(string): void|null  $log
     */
    public function seed(?callable $log = null, bool $withPhotos = true): User
    {
        $this->log = $log ?? fn () => null;

        $user = $this->account();
        $this->logo($user);
        $collections = $this->collections($user);
        $this->resetActivity($user);

        if ($withPhotos) {
            $this->photos($user, $collections);
        }
        $this->covers($collections);
        $this->templates($user, $collections);
        $this->prints($user);

        return $user;
    }

    /** The demo user, on the top plan with no Stripe subscription. */
    private function account(): User
    {
        $plan = SubscriptionPlan::query()->where('active', true)->orderByDesc('price')->orderByDesc('sort_order')->first();
        $user = User::query()->where('is_demo', true)->first()
            ?? User::query()->where('username', config('demo.username'))->first()
            ?? new User;

        $user->forceFill([
            'name' => config('demo.name'),
            'username' => config('demo.username'),
            'email' => config('demo.email'),
            'is_demo' => true,
            // Nobody knows this password, and logins are refused for the demo anyway.
            'password' => $user->exists ? $user->password : bcrypt(Str::random(64)),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'subscription_plan_id' => $plan?->id,
            'is_admin' => false,
            'feature_override' => false,
            'bio' => config('demo.bio'),
            'portfolio_display_mode' => 'collections',
            'masonry_columns' => 3,
            'photos_per_page' => 24,
            'portfolio_font' => 'playfair',
            'portfolio_theme' => 'light',
            'portfolio_accent_color' => '#8a5a44',
            'portfolio_background_color' => '#faf7f2',
            'portfolio_text_color' => '#2b2622',
            'portfolio_heading_color' => null,
            'watermark_text' => 'Marlow & Finch',
            'copy_protection' => true,
            // Shows "Buy photos" and the print shop; checkouts are blocked for the demo.
            'stripe_connect_id' => 'acct_demo',
            'stripe_connect_enabled' => true,
            'digital_photo_price' => 4.00,
            'digital_bundles' => [['count' => 5, 'price' => 15.00], ['count' => 10, 'price' => 25.00]],
        ])->save();

        ($this->log)("Account @{$user->username} on the {$plan?->name} plan.");

        return $user;
    }

    /** A simple wordmark logo, drawn here (no external assets). */
    private function logo(User $user): void
    {
        if ($user->logo_path && Storage::disk('s3')->exists($user->logo_path)) {
            return;
        }
        $draw = new \ImagickDraw;
        $draw->setFillColor(new \ImagickPixel('#8a5a44'));
        $draw->setFontSize(64);
        $font = public_path('fonts/OpenSans-Bold.ttf');
        if (is_file($font)) {
            $draw->setFont($font);
        }
        $draw->setGravity(\Imagick::GRAVITY_CENTER);
        $im = new \Imagick;
        $im->newImage(720, 160, new \ImagickPixel('none'), 'png');
        $im->annotateImage($draw, 0, 0, 0, 'Marlow & Finch');
        $im->trimImage(0);
        $im->setImagePage(0, 0, 0, 0);
        Storage::disk('s3')->put('logos/demo-logo.png', $im->getImageBlob());
        $im->thumbnailImage(160, 0);
        Storage::disk('s3')->put('logos/thumb_demo-logo.png', $im->getImageBlob());
        $im->clear();

        $user->forceFill(['logo_path' => 'logos/demo-logo.png', 'logo_thumb_path' => 'logos/thumb_demo-logo.png'])->save();
    }

    /**
     * The collections and their sets, and which photo category fills each set.
     *
     * @return array<string, Collection>
     */
    private function collections(User $user): array
    {
        $definitions = [
            'weddings' => [
                'name' => 'Weddings', 'event_date' => now()->subMonths(2),
                'sets' => ['Ceremony', 'Reception'],
                // On sale: per photo, bundles and the whole collection.
                'attrs' => ['digital_sales' => true, 'digital_collection_price' => 30.00],
            ],
            'client' => [
                'name' => 'Harper & Jordan: client gallery', 'event_date' => now()->subWeeks(3),
                'sets' => ['Getting ready', 'The day'],
                'attrs' => ['private' => true, 'password' => bcrypt(config('demo.client_gallery_password')), 'hide_from_portfolio' => true, 'watermarked' => true],
            ],
            'portraits' => [
                'name' => 'Portraits', 'event_date' => now()->subMonths(4),
                'sets' => ['Studio', 'Outdoors'],
                // Free downloads, with a postcard design (template).
                'attrs' => [],
            ],
            'landscapes' => [
                'name' => 'Lake District landscapes', 'event_date' => now()->subMonths(6),
                'sets' => ['Fells', 'Lakes'],
                // Watermarked, prints and downloads for sale.
                'attrs' => ['watermarked' => true, 'digital_sales' => true],
            ],
            'events' => [
                'name' => 'Events', 'event_date' => now()->subMonth(),
                'sets' => ['Highlights'],
                'attrs' => [],
            ],
        ];

        $collections = [];
        foreach ($definitions as $key => $definition) {
            $collection = Collection::query()->firstOrNew(['user_id' => $user->id, 'name' => $definition['name']]);
            $collection->forceFill($definition['attrs'] + [
                'status' => 'Published',
                'favourites_enabled' => true,
                'event_date' => $definition['event_date'],
                'private' => false,
                'watermarked' => false,
                'hide_from_portfolio' => false,
                'digital_sales' => false,
                'digital_photo_price' => null,
                'digital_collection_price' => null,
            ])->save();
            foreach ($definition['sets'] as $name) {
                Set::query()->firstOrCreate(['collection_id' => $collection->id, 'name' => $name], ['hide_from_portfolio' => false]);
            }
            $collections[$key] = $collection->fresh('sets');
        }
        ($this->log)('Collections: '.implode(', ', array_map(fn ($c) => $c->name, $collections)).'.');

        return $collections;
    }

    /** Visitors' leftovers from before: generated designs and any stray orders. */
    private function resetActivity(User $user): void
    {
        $templateIds = Template::query()->where('user_id', $user->id)->pluck('id');
        GeneratedPrint::query()->whereIn('template_id', $templateIds)->get()->each(function (GeneratedPrint $print) {
            if ($print->output_path) {
                Storage::disk('s3')->delete($print->output_path);
            }
            $print->delete();
        });
        Order::query()->where('user_id', $user->id)->delete();
        \App\Models\FavouriteList::query()->whereIn('collection_id', $user->collections()->pluck('id'))->delete();
    }

    /**
     * Fills each category with Unsplash photos through the normal upload
     * pipeline (original stored, then ProcessImageUpload makes the thumbnail and
     * watermarked copy). Photos already fetched are kept.
     *
     * @param  array<string, Collection>  $collections
     */
    private function photos(User $user, array $collections): void
    {
        $key = config('demo.unsplash_key');
        if (! $key) {
            throw new RuntimeException('Set UNSPLASH_ACCESS_KEY (a free Unsplash developer key) to fetch the demo photos, or run with --skip-photos.');
        }

        // Which sets each category fills; weddings also fill the client gallery.
        $targets = [
            'weddings' => [...$collections['weddings']->sets->all(), ...$collections['client']->sets->all()],
            'portraits' => $collections['portraits']->sets->all(),
            'landscapes' => $collections['landscapes']->sets->all(),
            'events' => $collections['events']->sets->all(),
        ];

        foreach (config('demo.categories') as $category => $spec) {
            $sets = array_values($targets[$category] ?? []);
            if (! $sets) {
                continue;
            }
            $existing = Photo::query()->where('user_id', $user->id)->where('credit->category', $category)->orderBy('created_at')->get();
            $missing = $spec['count'] - $existing->count();
            $added = 0;

            if ($missing > 0) {
                $known = Photo::query()->where('user_id', $user->id)->whereNotNull('credit')->pluck('credit')->map(fn ($c) => $c['source_id'] ?? null)->filter()->all();
                foreach ($this->search($spec['query'], $key) as $result) {
                    if ($added >= $missing) {
                        break;
                    }
                    if (in_array($result['id'], $known, true) || ($result['premium'] ?? false) || ($result['plus'] ?? false)) {
                        continue;
                    }
                    $index = $existing->count() + $added;
                    $this->addPhoto($user, $result, $category, $sets[$index % count($sets)], $key);
                    $known[] = $result['id'];
                    $added++;
                }
            }

            // Spread the category's photos over its sets (stable order).
            $all = Photo::query()->where('user_id', $user->id)->where('credit->category', $category)->orderBy('created_at')->get();
            foreach ($all->values() as $i => $photo) {
                $set = $sets[$i % count($sets)];
                $photo->forceFill(['set_id' => $set->id, 'private' => (bool) $set->collection->private])->save();
            }
            ($this->log)(ucfirst($category).": {$all->count()} photos ({$added} new).");
        }
    }

    /** @return list<array> Unsplash search results, most relevant first. */
    private function search(string $query, string $key): array
    {
        $results = [];
        foreach ([1, 2] as $page) {
            $response = $this->unsplash($key)->get('https://api.unsplash.com/search/photos', [
                'query' => $query, 'per_page' => 30, 'page' => $page, 'content_filter' => 'high',
            ]);
            if (! $response->successful()) {
                throw new RuntimeException('Unsplash search failed (HTTP '.$response->status().'). Check UNSPLASH_ACCESS_KEY.');
            }
            $results = [...$results, ...($response->json('results') ?? [])];
        }

        return $results;
    }

    private function addPhoto(User $user, array $result, string $category, Set $set, string $key): void
    {
        // Unsplash's guidelines: record the download, then hotlink-free copy of the image.
        if ($location = data_get($result, 'links.download_location')) {
            $this->unsplash($key)->get($location);
        }
        $image = Http::timeout(60)->get(data_get($result, 'urls.raw').'&w=2400&q=85&fm=jpg');
        if (! $image->successful() || $image->body() === '') {
            throw new RuntimeException("Couldn't download Unsplash photo {$result['id']}.");
        }

        $storageKey = 'photos/'.$user->id.'/demo-unsplash-'.$result['id'].'.jpg';
        Storage::disk('s3')->put($storageKey, $image->body());

        $utm = config('demo.utm');
        $name = (string) data_get($result, 'user.name', 'Unsplash photographer');
        $alt = Str::ucfirst((string) (data_get($result, 'alt_description') ?: data_get($result, 'description') ?: Str::headline($category)));

        $photo = DB::transaction(fn () => Photo::query()->create([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'url' => $storageKey,
            'caption' => Str::limit($alt, 120, '…').' · Photo by '.$name.' on Unsplash',
            'private' => (bool) $set->collection->private,
            'size' => strlen($image->body()),
            'status' => Photo::PROCESSING,
        ]));
        $photo->forceFill(['credit' => [
            'name' => $name,
            'profile_url' => data_get($result, 'user.links.html').'?'.$utm,
            'source_url' => data_get($result, 'links.html').'?'.$utm,
            'licence' => 'Unsplash License',
            'source_id' => $result['id'],
            'category' => $category,
        ]])->save();

        (new ProcessImageUpload($photo->id))->handle();
    }

    private function unsplash(string $key): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(30)->withHeaders(['Authorization' => 'Client-ID '.$key, 'Accept-Version' => 'v1']);
    }

    /** @param  array<string, Collection>  $collections */
    private function covers(array $collections): void
    {
        foreach ($collections as $collection) {
            $first = $collection->photos()->ready()->orderBy('photos.created_at')->first();
            if ($first && ! $collection->photos()->whereKey($collection->cover_photo_id)->exists()) {
                $collection->forceFill(['cover_photo_id' => $first->id])->save();
            }
        }
    }

    /**
     * A postcard design: a custom font and an overlay drawn here, with a
     * caption field. Offered on the Portraits sets (free downloads).
     *
     * @param  array<string, Collection>  $collections
     */
    private function templates(User $user, array $collections): void
    {
        $fontPath = 'fonts/'.$user->id.'/demo-open-sans-bold.ttf';
        if (! Storage::disk('s3')->exists($fontPath) && is_file(public_path('fonts/OpenSans-Bold.ttf'))) {
            Storage::disk('s3')->put($fontPath, file_get_contents(public_path('fonts/OpenSans-Bold.ttf')));
        }
        $font = Font::query()->updateOrCreate(['user_id' => $user->id, 'name' => 'Open Sans Bold'], ['filename' => 'OpenSans-Bold.ttf', 'path' => $fontPath, 'active' => true]);

        $overlayPath = 'templates/'.$user->id.'/demo-postcard-overlay.png';
        if (! Storage::disk('s3')->exists($overlayPath)) {
            $overlay = new \Imagick;
            $overlay->newImage(1600, 1200, new \ImagickPixel('none'), 'png');
            $frame = new \ImagickDraw;
            $frame->setFillColor(new \ImagickPixel('none'));
            $frame->setStrokeColor(new \ImagickPixel('rgba(255,255,255,0.95)'));
            $frame->setStrokeWidth(36);
            $frame->rectangle(18, 18, 1582, 1182);
            $frame->setStrokeWidth(0);
            $frame->setFillColor(new \ImagickPixel('rgba(43,38,34,0.55)'));
            $frame->rectangle(36, 1000, 1564, 1164);
            $overlay->drawImage($frame);
            Storage::disk('s3')->put($overlayPath, $overlay->getImageBlob());
            $overlay->clear();
        }

        $template = Template::query()->updateOrCreate(['user_id' => $user->id, 'name' => 'Postcard'], [
            'description' => 'A white-bordered postcard with your own caption.',
            'overlay_path' => $overlayPath,
            'active' => true,
            'fields' => [[
                'key' => 'caption', 'label' => 'Your caption', 'x' => 50, 'y' => 90,
                'font_size' => 56, 'color' => '#ffffff', 'align' => 'center', 'font_id' => $font->id,
            ]],
        ]);
        foreach ($collections['portraits']->sets as $set) {
            $set->templates()->syncWithoutDetaching([$template->id]);
        }
    }

    /** Every active catalogue product, at the catalogue's default prices. */
    private function prints(User $user): void
    {
        $ids = ProdigiProduct::query()->active()->pluck('id');
        $user->prodigiProducts()->syncWithoutDetaching($ids->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true, 'retail_price' => null]])->all());
        ($this->log)("Print shop: {$ids->count()} products.");
    }
}

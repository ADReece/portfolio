<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Small, predictable data set for the browser accessibility checks
 * (tests/Browser). Creates a photographer "ava" (password: password) with a
 * public collection, a private collection (password: wedding) and a draft.
 * Photos point at keys that don't need to exist; the checks don't need images.
 */
class UxDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SubscriptionPlanSeeder::class);

        $user = User::updateOrCreate(['email' => 'ava@example.com'], [
            'name' => 'Ava Reid',
            'username' => 'ava',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => true,
            'bio' => '<p>Wedding and landscape photographer based in Leeds.</p>',
        ]);

        // A print catalogue product, so the catalogue and print settings pages have content.
        $print = \App\Models\ProdigiProduct::updateOrCreate(['sku' => 'GLOBAL-CFPM-16X20'], [
            'name' => 'Classic framed print 16×20',
            'category' => 'Framed',
            'description' => 'Museum-quality print in a classic wooden frame.',
            'size_label' => '16 × 20 in',
            'is_active' => true,
            'raw_payload' => ['sku' => 'GLOBAL-CFPM-16X20', 'attributes' => ['color' => ['black', 'white', 'natural']], 'printAreas' => ['default' => ['required' => true]]],
        ]);
        $print->forceFill(['cost_item' => 24.00, 'cost_shipping' => 6.95, 'cost_currency' => 'GBP', 'cost_quoted_at' => now(), 'refreshed_at' => now()])->save();

        $make = function (array $attrs, array $sets) use ($user) {
            $collection = Collection::updateOrCreate(
                ['user_id' => $user->id, 'name' => $attrs['name']],
                $attrs + ['status' => 'Published', 'event_date' => now()->subWeeks(2), 'private' => false, 'watermarked' => false, 'hide_from_portfolio' => false]
            );
            foreach ($sets as $name) {
                $set = Set::firstOrCreate(['collection_id' => $collection->id, 'name' => $name], ['hide_from_portfolio' => false]);
                for ($i = 1; $i <= 3; $i++) {
                    $photo = Photo::firstOrCreate(
                        ['set_id' => $set->id, 'url' => "photos/{$set->id}-{$i}.jpg"],
                        ['user_id' => $user->id, 'caption' => "$name $i", 'private' => false, 'size' => 1000, 'tags' => '']
                    );
                    $collection->cover_photo_id ??= $photo->id;
                }
            }
            $collection->save();

            return $collection;
        };

        $make(['name' => 'Coastal Landscapes'], ['Morning', 'Dusk']);
        $make(['name' => 'Harper & Jordan Wedding', 'private' => true, 'password' => bcrypt('wedding'), 'hide_from_portfolio' => true], ['Ceremony']);
        $make(['name' => 'Autumn Portraits', 'status' => 'Draft'], []);
    }
}

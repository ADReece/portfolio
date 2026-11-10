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

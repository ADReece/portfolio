<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_product_id' => null,
                'stripe_price_id' => null,
                'price' => 0.00,
                'annual_price' => 0.00,
                'annual_stripe_price_id' => null,
                'annual_discount_percent' => 0,
                'photo_limit' => 100,
                'collection_limit' => 5,
                'private_collections' => false,
                'watermarking' => false,
                'selling' => false,
                'video_upload' => false,
                'custom_templates' => false,
                'upload_logo' => false,
                'features' => ['Up to 100 photos', 'Up to 5 collections', 'Public portfolio', 'Basic templates'],
                'active' => true,
                'sort_order' => 1,
                'recommended' => false,
            ],
            [
                'name' => 'Photographer',
                'slug' => 'photographer',
                'stripe_product_id' => null,
                'stripe_price_id' => null,
                'price' => 3.99,
                'annual_price' => 39.90,
                'annual_stripe_price_id' => null,
                'annual_discount_percent' => 17,
                'photo_limit' => null,
                'collection_limit' => null,
                'private_collections' => true,
                'watermarking' => true,
                'selling' => true,
                'video_upload' => false,
                'custom_templates' => true,
                'upload_logo' => true,
                'features' => ['Unlimited photos', 'Unlimited collections', 'Private collections', 'Watermarking', 'Sell photos', 'Logo branding', 'Custom templates'],
                'active' => true,
                'sort_order' => 2,
                'recommended' => true,
            ],
            [
                'name' => 'Videographer',
                'slug' => 'videographer',
                'stripe_product_id' => null,
                'stripe_price_id' => null,
                'price' => 7.99,
                'annual_price' => 79.90,
                'annual_stripe_price_id' => null,
                'annual_discount_percent' => 17,
                'photo_limit' => null,
                'collection_limit' => null,
                'private_collections' => true,
                'watermarking' => true,
                'selling' => true,
                'video_upload' => true,
                'custom_templates' => true,
                'upload_logo' => true,
                'features' => ['Everything in Photographer', 'Video upload', 'Video galleries', 'HD streaming', 'Logo branding'],
                'active' => true,
                'sort_order' => 3,
                'recommended' => false,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(['slug' => $planData['slug']], $planData);
        }

        $this->command->info('✅ Subscription plans seeded successfully!');
    }
}

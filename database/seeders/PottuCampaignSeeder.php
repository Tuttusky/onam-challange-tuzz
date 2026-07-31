<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignTheme;
use App\Models\PottuImage;
use App\Models\PottuStyle;
use App\Services\Pottu\PottuSettingsService;
use Illuminate\Database\Seeder;

class PottuCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $theme = CampaignTheme::query()->first();

        $campaign = Campaign::query()->updateOrCreate(
            ['slug' => 'sundarikk-pottu-thodal'],
            [
                'name' => 'Sundarikk Pottu Thodal',
                'type' => Campaign::TYPE_POTTU,
                'description' => 'Place the pottu secretly, challenge your friend, and keep the viral chain going!',
                'status' => 'active',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth()->addDays(14),
                'max_questions' => 1,
                'max_friends' => 100,
                'share_message' => 'I placed a secret pottu! Can you find the exact spot? 🌼',
                'default_challenge_title' => '{friend_name}, find my pottu!',
                'campaign_theme_id' => $theme?->id,
                'is_featured' => true,
                'sort_order' => 0,
                'settings' => [
                    'pottu' => PottuSettingsService::defaults(),
                ],
            ]
        );

        $images = [
            [
                'title' => 'Onam Girl 1',
                'path' => 'https://plus.unsplash.com/premium_photo-1682089810582-f7b200217b67?fm=jpg&q=60&w=600&auto=format&fit=crop',
                'width' => 600,
                'height' => 900,
                'sort_order' => 1,
            ],
            [
                'title' => 'Onam Girl 2',
                'path' => 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?auto=format&fit=crop&w=600&q=80',
                'width' => 600,
                'height' => 900,
                'sort_order' => 2,
            ],
            [
                'title' => 'Onam Girl 3',
                'path' => 'https://images.unsplash.com/photo-1513885535751-8b9238bd345a?auto=format&fit=crop&w=600&q=80',
                'width' => 600,
                'height' => 900,
                'sort_order' => 3,
            ],
            [
                'title' => 'Onam Girl 4',
                'path' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=80',
                'width' => 600,
                'height' => 900,
                'sort_order' => 4,
            ],
        ];

        foreach ($images as $image) {
            PottuImage::query()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'title' => $image['title'],
                ],
                array_merge($image, ['is_active' => true])
            );
        }

        PottuStyle::query()->updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'name' => 'Classic Red Pottu',
            ],
            [
                'path' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Bindi.jpg/240px-Bindi.jpg',
                'type' => 'image',
                'default_size' => 52,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }
}

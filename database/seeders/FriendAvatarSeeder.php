<?php

namespace Database\Seeders;

use App\Models\FriendAvatar;
use Illuminate\Database\Seeder;

class FriendAvatarSeeder extends Seeder
{
    public function run(): void
    {
        $avatars = [
            ['name' => 'Onam Flower', 'slug' => 'onam-flower', 'path' => '/images/avatars/onam-flower.png', 'sort_order' => 1],
            ['name' => 'Boat Race', 'slug' => 'boat-race', 'path' => '/images/avatars/boat-race.png', 'sort_order' => 2],
            ['name' => 'Tiger Face', 'slug' => 'tiger-face', 'path' => '/images/avatars/tiger-face.png', 'sort_order' => 3],
            ['name' => 'Pookalam', 'slug' => 'pookalam', 'path' => '/images/avatars/pookalam.png', 'sort_order' => 4],
            ['name' => 'Dancer', 'slug' => 'dancer', 'path' => '/images/avatars/dancer.png', 'sort_order' => 5],
            ['name' => 'Drummer', 'slug' => 'drummer', 'path' => '/images/avatars/drummer.png', 'sort_order' => 6],
        ];

        foreach ($avatars as $avatar) {
            FriendAvatar::query()->updateOrCreate(
                ['slug' => $avatar['slug']],
                array_merge($avatar, ['is_active' => true])
            );
        }
    }
}

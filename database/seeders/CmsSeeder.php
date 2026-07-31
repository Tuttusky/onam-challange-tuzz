<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About',
                'slug' => 'about',
                'content' => '<h2>About Onam Dare Challenge</h2><p>A viral social dare game where you answer fun questions, share your challenge link, and see how well your friends know you.</p>',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => '<h2>Privacy Policy</h2><p>We collect only the information needed to run the challenge game. Names and answers are stored to generate results and leaderboards.</p>',
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'content' => '<h2>Terms of Service</h2><p>By using this platform you agree to play responsibly and not submit offensive content.</p>',
                'is_published' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => '<h2>FAQ</h2><p><strong>How does it work?</strong> Answer dare questions, get a link, share with friends.</p>',
                'is_published' => true,
                'sort_order' => 4,
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => '<h2>Contact</h2><p>Email us at support@onamdare.com</p>',
                'is_published' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::query()->updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}

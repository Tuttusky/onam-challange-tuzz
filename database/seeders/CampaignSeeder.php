<?php

namespace Database\Seeders;

use App\Models\AnalyticsSetting;
use App\Models\Badge;
use App\Models\Campaign;
use App\Models\CampaignTheme;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionOption;
use App\Models\ResultMessage;
use App\Models\SeoSetting;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $theme = CampaignTheme::query()->updateOrCreate(
            ['name' => 'Onam Festival Theme'],
            [
                'logo' => '/images/themes/onam/logo.png',
                'favicon' => '/images/themes/onam/favicon.ico',
                'primary_color' => '#6366f1',
                'secondary_color' => '#64748b',
                'background_image' => '/images/themes/onam/bg-pookalam.jpg',
                'background_gradient' => '#0f172a',
                'font_family' => 'Baloo 2',
                'animation_pack' => [
                    'entry' => 'fade-slide-up',
                    'question' => 'bounce-in',
                    'result' => 'confetti-burst',
                ],
                'sound_pack' => [
                    'tap' => '/sounds/onam/tap.mp3',
                    'correct' => '/sounds/onam/correct.mp3',
                    'wrong' => '/sounds/onam/wrong.mp3',
                    'result' => '/sounds/onam/cheer.mp3',
                ],
            ]
        );

        $campaign = Campaign::query()->updateOrCreate(
            ['slug' => 'onam-dare-challenge'],
            [
                'name' => 'Onam Dare Challenge',
                'type' => Campaign::TYPE_DARE_CHALLENGE,
                'description' => 'Create your dare, share with friends, and see who knows you best this Onam season!',
                'status' => 'active',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth()->addDays(7),
                'max_questions' => 10,
                'max_friends' => 50,
                'share_message' => 'I took the Onam Dare Challenge! Think you know me? Accept my dare and find out! 🌼',
                'default_challenge_title' => 'Hey {friend_name}, Can You Beat Me?',
                'campaign_theme_id' => $theme->id,
                'is_featured' => true,
                'sort_order' => 1,
                'settings' => [
                    'time_limit_sec' => 30,
                    'shuffle_options' => true,
                ],
            ]
        );

        $category = QuestionCategory::query()->updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'slug' => 'onam-dares',
            ],
            [
                'name' => 'Onam Dares',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->seedQuestions($campaign, $category);
        $this->seedBadges($campaign);
        $this->seedResultMessages($campaign);
        $this->seedSeo($campaign);
        $this->seedAnalytics();
    }

    protected function seedQuestions(Campaign $campaign, QuestionCategory $category): void
    {
        Question::query()->where('campaign_id', $campaign->id)->delete();

        $questions = [
            [
                'type' => 'yes_no',
                'title' => 'Would you eat a third round of Onam sadya?',
                'description' => 'Be honest — no judging!',
                'icon' => '🍛',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 1,
                'options' => [
                    ['label' => 'Yes, always!', 'value' => 'yes', 'icon' => '✅'],
                    ['label' => 'No way', 'value' => 'no', 'icon' => '❌'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Which Onam activity would you pick all day?',
                'icon' => '🎉',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 2,
                'options' => [
                    ['label' => 'Vallamkali (boat race)', 'value' => 'boat_race', 'icon' => '🚣'],
                    ['label' => 'Onam sadya feast', 'value' => 'sadya', 'icon' => '🍽️'],
                    ['label' => 'Pookalam making', 'value' => 'pookalam', 'icon' => '🌸'],
                    ['label' => 'Thiruvathira dance', 'value' => 'dance', 'icon' => '💃'],
                ],
            ],
            [
                'type' => 'emoji',
                'title' => 'Pick the emoji that matches your Onam vibe',
                'icon' => '🎭',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 3,
                'options' => [
                    ['label' => 'Party mode', 'value' => 'party', 'icon' => '🎉'],
                    ['label' => 'Food lover', 'value' => 'foodie', 'icon' => '😋'],
                    ['label' => 'Chill & relax', 'value' => 'chill', 'icon' => '😌'],
                    ['label' => 'Super competitive', 'value' => 'competitive', 'icon' => '🏆'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you sing "Maveli Nadu" at a family gathering?',
                'icon' => '🎤',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 4,
                'options' => [
                    ['label' => 'Yes, proudly!', 'value' => 'yes', 'icon' => '🎶'],
                    ['label' => 'Never', 'value' => 'no', 'icon' => '🙈'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Your ultimate Onam snack pick?',
                'icon' => '🥘',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 5,
                'options' => [
                    ['label' => 'Banana chips', 'value' => 'chips', 'icon' => '🍌'],
                    ['label' => 'Unniyappam', 'value' => 'unniyappam', 'icon' => '🥞'],
                    ['label' => 'Payasam', 'value' => 'payasam', 'icon' => '🍮'],
                    ['label' => 'Achappam', 'value' => 'achappam', 'icon' => '🍪'],
                ],
            ],
            [
                'type' => 'emoji',
                'title' => 'How competitive are you in a pookalam contest?',
                'icon' => '🌺',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 6,
                'options' => [
                    ['label' => 'Not at all', 'value' => 'zero', 'icon' => '😴'],
                    ['label' => 'A little', 'value' => 'little', 'icon' => '🙂'],
                    ['label' => 'Very', 'value' => 'very', 'icon' => '😤'],
                    ['label' => 'Win or nothing', 'value' => 'extreme', 'icon' => '🔥'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you wear traditional kasavu the entire Onam day?',
                'icon' => '👘',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 7,
                'options' => [
                    ['label' => 'Yes!', 'value' => 'yes', 'icon' => '✨'],
                    ['label' => 'Too hot for that', 'value' => 'no', 'icon' => '🥵'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'If you could only eat ONE sadya dish forever?',
                'icon' => '🍲',
                'difficulty' => 'hard',
                'points' => 3,
                'sort_order' => 8,
                'options' => [
                    ['label' => 'Avial', 'value' => 'avial', 'icon' => '🥗'],
                    ['label' => 'Sambar', 'value' => 'sambar', 'icon' => '🫕'],
                    ['label' => 'Parippu curry', 'value' => 'parippu', 'icon' => '🫘'],
                    ['label' => 'Pappadam', 'value' => 'pappadam', 'icon' => '🥙'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you skip breakfast just to save room for Onam lunch?',
                'icon' => '⏰',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 9,
                'options' => [
                    ['label' => 'Absolutely', 'value' => 'yes', 'icon' => '💪'],
                    ['label' => 'Breakfast is sacred', 'value' => 'no', 'icon' => '🥐'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Your dare: who should host the next Onam party?',
                'icon' => '⚡',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 10,
                'options' => [
                    ['label' => 'Me — I love hosting', 'value' => 'me', 'icon' => '🙋'],
                    ['label' => 'My best friend', 'value' => 'friend', 'icon' => '🤝'],
                    ['label' => 'The whole gang together', 'value' => 'group', 'icon' => '👨‍👩‍👧‍👦'],
                    ['label' => 'Whoever loses this challenge', 'value' => 'loser', 'icon' => '😈'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you dance in the rain during Onam celebrations?',
                'icon' => '🌧️',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 11,
                'options' => [
                    ['label' => 'Yes, why not!', 'value' => 'yes', 'icon' => '💃'],
                    ['label' => 'No, I stay dry', 'value' => 'no', 'icon' => '☔'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Best Onam movie marathon pick?',
                'icon' => '🎬',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 12,
                'options' => [
                    ['label' => 'Classic Mohanlal hits', 'value' => 'mohanlal', 'icon' => '🎭'],
                    ['label' => 'Comedy specials', 'value' => 'comedy', 'icon' => '😂'],
                    ['label' => 'Family dramas', 'value' => 'drama', 'icon' => '🎞️'],
                    ['label' => 'No movies — only sadya', 'value' => 'sadya_only', 'icon' => '🍛'],
                ],
            ],
            [
                'type' => 'emoji',
                'title' => 'Your mood on Thiruvonam morning?',
                'icon' => '🌅',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 13,
                'options' => [
                    ['label' => 'Excited', 'value' => 'excited', 'icon' => '🤩'],
                    ['label' => 'Sleepy', 'value' => 'sleepy', 'icon' => '😴'],
                    ['label' => 'Hungry already', 'value' => 'hungry', 'icon' => '🤤'],
                    ['label' => 'Stressed hosting', 'value' => 'stressed', 'icon' => '😰'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you help clean up after the Onam feast?',
                'icon' => '🧹',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 14,
                'options' => [
                    ['label' => 'Of course!', 'value' => 'yes', 'icon' => '👍'],
                    ['label' => 'I disappear quietly', 'value' => 'no', 'icon' => '🏃'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Pick your Onam flower for pookalam center',
                'icon' => '🌼',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 15,
                'options' => [
                    ['label' => 'Marigold', 'value' => 'marigold', 'icon' => '🌼'],
                    ['label' => 'Rose', 'value' => 'rose', 'icon' => '🌹'],
                    ['label' => 'Jasmine', 'value' => 'jasmine', 'icon' => '🤍'],
                    ['label' => 'Hibiscus', 'value' => 'hibiscus', 'icon' => '🌺'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you take 100 Onam challenge photos?',
                'icon' => '📸',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 16,
                'options' => [
                    ['label' => 'Yes, content creator mode', 'value' => 'yes', 'icon' => '📱'],
                    ['label' => 'One photo is enough', 'value' => 'no', 'icon' => '🙅'],
                ],
            ],
            [
                'type' => 'emoji',
                'title' => 'How do you feel about Onam shopping crowds?',
                'icon' => '🛍️',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 17,
                'options' => [
                    ['label' => 'Love the buzz', 'value' => 'love', 'icon' => '🛒'],
                    ['label' => 'Tolerate it', 'value' => 'tolerate', 'icon' => '😐'],
                    ['label' => 'Online only', 'value' => 'online', 'icon' => '💻'],
                    ['label' => 'Avoid completely', 'value' => 'avoid', 'icon' => '🏠'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Your ideal Onam evening plan?',
                'icon' => '🌙',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 18,
                'options' => [
                    ['label' => 'Family games', 'value' => 'games', 'icon' => '🎲'],
                    ['label' => 'Fireworks watch', 'value' => 'fireworks', 'icon' => '🎆'],
                    ['label' => 'Quiet payasam time', 'value' => 'payasam', 'icon' => '🍮'],
                    ['label' => 'Early sleep', 'value' => 'sleep', 'icon' => '🛏️'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you try making payasam from scratch?',
                'icon' => '👨‍🍳',
                'difficulty' => 'hard',
                'points' => 3,
                'sort_order' => 19,
                'options' => [
                    ['label' => 'Yes, chef mode', 'value' => 'yes', 'icon' => '🔥'],
                    ['label' => 'Buy it ready-made', 'value' => 'no', 'icon' => '🏪'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Which Onam game would you dominate?',
                'icon' => '🏅',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 20,
                'options' => [
                    ['label' => 'Uriyadi', 'value' => 'uriyadi', 'icon' => '🪅'],
                    ['label' => 'Tug of war', 'value' => 'tug', 'icon' => '🪢'],
                    ['label' => 'Card games', 'value' => 'cards', 'icon' => '🃏'],
                    ['label' => 'Quiz challenges', 'value' => 'quiz', 'icon' => '❓'],
                ],
            ],
            [
                'type' => 'emoji',
                'title' => 'Pick your Onam superpower',
                'icon' => '⚡',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 21,
                'options' => [
                    ['label' => 'Endless appetite', 'value' => 'appetite', 'icon' => '🍽️'],
                    ['label' => 'Perfect pookalam', 'value' => 'pookalam', 'icon' => '🌸'],
                    ['label' => 'Dance stamina', 'value' => 'dance', 'icon' => '💃'],
                    ['label' => 'Gift wrapping pro', 'value' => 'gifts', 'icon' => '🎁'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you wear a flower garland all day on Onam?',
                'icon' => '💐',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 22,
                'options' => [
                    ['label' => 'Absolutely!', 'value' => 'yes', 'icon' => '🌺'],
                    ['label' => 'Too itchy', 'value' => 'no', 'icon' => '😣'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'Your go-to Onam dessert after sadya?',
                'icon' => '🍨',
                'difficulty' => 'easy',
                'points' => 1,
                'sort_order' => 23,
                'options' => [
                    ['label' => 'Ada pradhaman', 'value' => 'ada', 'icon' => '🥣'],
                    ['label' => 'Palada payasam', 'value' => 'palada', 'icon' => '🥛'],
                    ['label' => 'Fruit salad', 'value' => 'fruit', 'icon' => '🍉'],
                    ['label' => 'Ice cream', 'value' => 'icecream', 'icon' => '🍦'],
                ],
            ],
            [
                'type' => 'yes_no',
                'title' => 'Would you wake up at 5 AM to finish pookalam?',
                'icon' => '⏰',
                'difficulty' => 'hard',
                'points' => 3,
                'sort_order' => 24,
                'options' => [
                    ['label' => 'Yes, dedication!', 'value' => 'yes', 'icon' => '🌅'],
                    ['label' => 'Sleep is sacred', 'value' => 'no', 'icon' => '😴'],
                ],
            ],
            [
                'type' => 'multiple_choice',
                'title' => 'If Maveli visited today, first thing you\'d offer?',
                'icon' => '👑',
                'difficulty' => 'medium',
                'points' => 2,
                'sort_order' => 25,
                'options' => [
                    ['label' => 'Fresh sadya plate', 'value' => 'sadya', 'icon' => '🍛'],
                    ['label' => 'Homemade payasam', 'value' => 'payasam', 'icon' => '🍮'],
                    ['label' => 'A warm hug', 'value' => 'hug', 'icon' => '🤗'],
                    ['label' => 'Show my pookalam', 'value' => 'pookalam', 'icon' => '🌸'],
                ],
            ],
        ];

        foreach ($questions as $data) {
            $options = $data['options'] ?? [];
            unset($data['options']);

            $question = Question::query()->create(
                array_merge($data, [
                    'campaign_id' => $campaign->id,
                    'category_id' => $category->id,
                    'is_active' => true,
                ])
            );

            foreach ($options as $optionIndex => $option) {
                QuestionOption::query()->create([
                    'question_id' => $question->id,
                    'label' => $option['label'],
                    'value' => $option['value'],
                    'icon' => $option['icon'] ?? null,
                    'sort_order' => $optionIndex + 1,
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function seedBadges(Campaign $campaign): void
    {
        $badges = [
            [
                'name' => 'Stranger',
                'slug' => 'stranger',
                'min_match_percent' => 0,
                'max_match_percent' => 29,
                'sort_order' => 1,
            ],
            [
                'name' => 'Acquaintance',
                'slug' => 'acquaintance',
                'min_match_percent' => 30,
                'max_match_percent' => 49,
                'sort_order' => 2,
            ],
            [
                'name' => 'Good Friend',
                'slug' => 'good-friend',
                'min_match_percent' => 50,
                'max_match_percent' => 69,
                'sort_order' => 3,
            ],
            [
                'name' => 'Best Buddy',
                'slug' => 'best-buddy',
                'min_match_percent' => 70,
                'max_match_percent' => 89,
                'sort_order' => 4,
            ],
            [
                'name' => 'Soulmate',
                'slug' => 'soulmate',
                'min_match_percent' => 90,
                'max_match_percent' => 100,
                'sort_order' => 5,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::query()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'slug' => $badge['slug'],
                ],
                array_merge($badge, [
                    'image' => '/images/badges/'.$badge['slug'].'.png',
                    'is_active' => true,
                ])
            );
        }
    }

    protected function seedResultMessages(Campaign $campaign): void
    {
        $messages = [
            [
                'min_match_percent' => 0,
                'max_match_percent' => 29,
                'message' => 'Oops! You barely know each other. Time for more Onam hangouts! 😅',
            ],
            [
                'min_match_percent' => 30,
                'max_match_percent' => 49,
                'message' => 'Getting there! You know a few things, but there\'s room to grow. 🌱',
            ],
            [
                'min_match_percent' => 50,
                'max_match_percent' => 69,
                'message' => 'Nice! You two are pretty good friends. Onam sadya buddies for sure! 🍛',
            ],
            [
                'min_match_percent' => 70,
                'max_match_percent' => 89,
                'message' => 'Wow! Best friend level unlocked! You know each other inside out! 🎉',
            ],
            [
                'min_match_percent' => 90,
                'max_match_percent' => 100,
                'message' => 'Mind blown! You are practically the same person! Soulmates! 💫',
            ],
        ];

        foreach ($messages as $index => $message) {
            ResultMessage::query()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'min_match_percent' => $message['min_match_percent'],
                ],
                array_merge($message, [
                    'is_active' => true,
                ])
            );
        }
    }

    protected function seedSeo(Campaign $campaign): void
    {
        SeoSetting::query()->updateOrCreate(
            [
                'seoable_type' => $campaign->getMorphClass(),
                'seoable_id' => $campaign->id,
            ],
            [
                'page_key' => 'campaign.onam-dare-challenge',
                'meta_title' => 'Onam Dare Challenge — Know Your Friends',
                'meta_description' => 'Take the Onam Dare Challenge, answer fun questions, share with friends, and discover how well you know each other!',
                'meta_keywords' => 'onam, dare challenge, friendship quiz, onam game, kerala festival',
                'og_title' => 'Onam Dare Challenge 🌼',
                'og_description' => 'Challenge your friends this Onam! Create dares, share links, compare results.',
                'og_image' => '/images/seo/onam-dare-og.jpg',
                'twitter_card' => 'summary_large_image',
                'robots' => 'index, follow',
                'schema_markup' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebApplication',
                    'name' => 'Onam Dare Challenge',
                    'applicationCategory' => 'GameApplication',
                ],
            ]
        );
    }

    protected function seedAnalytics(): void
    {
        $settings = [
            ['key' => 'tracking_enabled', 'value' => 'true'],
            ['key' => 'google_analytics_id', 'value' => ''],
            ['key' => 'facebook_pixel_id', 'value' => ''],
            ['key' => 'custom_head_scripts', 'value' => ''],
            ['key' => 'custom_body_scripts', 'value' => ''],
        ];

        foreach ($settings as $setting) {
            AnalyticsSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'من نحن',
                'icon' => 'heroicon-o-information-circle',
                'fallback' => '<h2>من نحن</h2><p>محتوى افتراضي.</p>',
            ],
            [
                'slug' => 'terms',
                'title' => 'الشروط والأحكام',
                'icon' => 'heroicon-o-document-text',
                'fallback' => '<h2>الشروط والأحكام</h2><p>محتوى افتراضي.</p>',
            ],
            [
                'slug' => 'privacy',
                'title' => 'سياسة الخصوصية',
                'icon' => 'heroicon-o-shield-check',
                'fallback' => '<h2>سياسة الخصوصية</h2><p>محتوى افتراضي.</p>',
            ],
        ];

        $settingMap = [
            'about' => 'page_about',
            'terms' => 'page_terms',
            'privacy' => 'page_privacy',
        ];

        foreach ($pages as $data) {
            $existing = Page::where('slug', $data['slug'])->first();
            $content = $existing?->content;

            // Migrate from the old settings.page_* row if no content exists yet.
            if (!$content && isset($settingMap[$data['slug']])) {
                $legacy = Setting::where('key', $settingMap[$data['slug']])->first();
                $content = $legacy?->value;
            }

            Page::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'icon' => $data['icon'],
                    'content' => $content ?: $data['fallback'],
                    'is_published' => true,
                    'is_locked' => true,
                ]
            );
        }

        $this->command?->info('Seeded ' . count($pages) . ' CMS pages.');
    }
}

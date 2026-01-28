<?php

namespace Database\Seeders;

use App\Models\QuestionType;
use Illuminate\Database\Seeder;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'مقارنة',
                'def_answers' => json_encode([
                    ['text' => 'القيمة الأولي أكبر', 'order' => 1],
                    ['text' => 'القيمة الثانية أكبر', 'order' => 2],
                    ['text' => 'القيمتان متساويتان', 'order' => 3],
                    ['text' => 'المعطيات غير كافية', 'order' => 4],
                ]),
            ],
            [
                'name' => 'نصي',
                'def_answers' => null,
            ],
            [
                'name' => 'صوري',
                'def_answers' => null,
            ],
        ];

        foreach ($types as $type) {
            QuestionType::firstOrCreate(
                ['name' => $type['name']],
                ['def_answers' => $type['def_answers']]
            );
        }
    }
}

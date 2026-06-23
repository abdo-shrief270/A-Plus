<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Question answer cost
    |--------------------------------------------------------------------------
    | Flat number of wallet points deducted the first time a student answers a
    | given question. Re-answering the same question is free (charged once).
    | Students with an active subscription answer with no charge. Set to 0 to
    | make answering free for everyone.
    */
    'question_answer_cost' => (int) env('QUESTION_ANSWER_COST', 1),

    /*
    |--------------------------------------------------------------------------
    | Free-trial content sample
    |--------------------------------------------------------------------------
    | Trial users only access a small fixed sample: the first N study-plan
    | lessons (by Lesson.order) and the first category of the exam.
    */
    'trial_lesson_count' => (int) env('TRIAL_LESSON_COUNT', 3),
    'trial_unlock_first_category' => (bool) env('TRIAL_UNLOCK_FIRST_CATEGORY', true),
];

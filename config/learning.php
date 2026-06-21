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
];
